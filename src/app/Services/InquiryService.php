<?php

namespace App\Services;

use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use App\Mail\InquiryReceived;
use App\Mail\InquiryReply;
use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\InquiryMessage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InquiryService
{
    private const MAX_RETRIES = 5;

    public function getList(): LengthAwarePaginator
    {
        $paginator = Inquiry::with(['staff', 'category'])
            ->latest()
            ->paginate(15);

        $paginator->getCollection()->transform(function (mixed $inquiry) {
            /** @var Inquiry $inquiry */
            /** @var InquiryStatus $status */
            $status = $inquiry->status;
            /** @var InquiryPriority $priority */
            $priority = $inquiry->priority;
            /** @var User|null $staff */
            $staff = $inquiry->staff;

            return [
                'id' => $inquiry->id,
                'inquiry_number' => $inquiry->inquiry_number,
                'category' => $inquiry->category_id,
                'category_label' => $inquiry->category?->name,
                'status' => $status->value,
                'status_label' => $status->label(),
                'status_color' => $status->color(),
                'priority' => $priority->value,
                'priority_label' => $priority->label(),
                'priority_color' => $priority->color(),
                'customer_name' => $inquiry->customer_name,
                'staff_name' => $staff?->name,
                'created_at' => $inquiry->created_at?->format('Y/m/d H:i'),
            ];
        });

        return $paginator;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(Inquiry $inquiry): array
    {
        $inquiry->load(['staff', 'category', 'messages' => fn ($q) => $q->latest()->with('staff')]);

        /** @var InquiryStatus $status */
        $status = $inquiry->status;
        /** @var InquiryPriority $priority */
        $priority = $inquiry->priority;
        /** @var User|null $staff */
        $staff = $inquiry->staff;

        return [
            'id' => $inquiry->id,
            'inquiry_number' => $inquiry->inquiry_number,
            'category_label' => $inquiry->category?->name,
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_color' => $status->color(),
            'priority' => $priority->value,
            'priority_label' => $priority->label(),
            'priority_color' => $priority->color(),
            'customer_name' => $inquiry->customer_name,
            'customer_email' => $inquiry->customer_email,
            'order_number' => $inquiry->order_number,
            'staff_id' => $inquiry->staff_id,
            'staff_name' => $staff?->name,
            'internal_notes' => $inquiry->internal_notes,
            'created_at' => $inquiry->created_at?->format('Y/m/d H:i'),
            'updated_at' => $inquiry->updated_at?->format('Y/m/d H:i'),
            'messages' => $this->formatMessages($inquiry),
        ];
    }

    /**
     * @return array<string, array<int, array{value: int|string, label: string}>>
     */
    public function getSelectOptions(): array
    {
        return [
            'categories' => InquiryCategory::active()->ordered()
                ->get(['id', 'name'])
                ->map(fn (InquiryCategory $c) => ['value' => $c->id, 'label' => $c->name])
                ->all(),
            'statuses' => collect(InquiryStatus::cases())->map(fn (InquiryStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
            'priorities' => collect(InquiryPriority::cases())->map(fn (InquiryPriority $p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateStatus(Inquiry $inquiry, array $validated): Inquiry
    {
        $inquiry->update($validated);

        return $inquiry->refresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function reply(int $inquiryId, array $validated, int $staffId): InquiryMessage
    {
        $inquiry = Inquiry::findOrFail($inquiryId);

        /** @var InquiryMessage $message */
        $message = $inquiry->messages()->create([
            'staff_id' => $staffId,
            'message_type' => 'staff_reply',
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        Mail::to($inquiry->customer_email)->send(new InquiryReply($inquiry, $message));

        return $message;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getStaffList(): array
    {
        return User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatMessages(Inquiry $inquiry): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, InquiryMessage> $messages */
        $messages = $inquiry->messages;

        return $messages->map(function (InquiryMessage $message) {
            /** @var User|null $messageStaff */
            $messageStaff = $message->staff;

            return [
                'id' => $message->id,
                'message_type' => $message->message_type,
                'subject' => $message->subject,
                'body' => $message->body,
                'staff_name' => $messageStaff?->name,
                'created_at' => $message->created_at?->format('Y/m/d H:i'),
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function store(array $validated): Inquiry
    {
        $inquiry = $this->createInquiryWithRetry($validated);

        Mail::to($inquiry->customer_email)->send(new InquiryReceived($inquiry));

        return $inquiry;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createInquiryWithRetry(array $validated): Inquiry
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return DB::transaction(function () use ($validated) {
                    $inquiryNumber = $this->generateInquiryNumber();

                    $inquiry = Inquiry::create([
                        'inquiry_number' => $inquiryNumber,
                        'category_id' => $validated['category_id'],
                        'order_number' => $validated['order_number'] ?? null,
                        'customer_name' => $validated['customer_name'],
                        'customer_email' => $validated['customer_email'],
                        'status' => 'pending',
                        'priority' => 'medium',
                    ]);

                    $inquiry->messages()->create([
                        'message_type' => 'initial_inquiry',
                        'subject' => $validated['subject'],
                        'body' => $validated['body'],
                    ]);

                    return $inquiry;
                });
            } catch (QueryException $e) {
                if ($attempt === self::MAX_RETRIES || ! str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('受付番号の生成に失敗しました。');
    }

    private function generateInquiryNumber(): string
    {
        $today = Carbon::today()->format('Ymd');
        $prefix = "INQ-{$today}-";

        $lastNumber = Inquiry::where('inquiry_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->max('inquiry_number');

        $sequence = $lastNumber ? (int) substr($lastNumber, -4) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
