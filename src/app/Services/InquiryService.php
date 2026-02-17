<?php

namespace App\Services;

use App\Enums\InquiryCategory;
use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
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
        $paginator = Inquiry::with('staff')
            ->latest()
            ->paginate(15);

        $paginator->getCollection()->transform(function (Inquiry $inquiry) {
            /** @var InquiryCategory $category */
            $category = $inquiry->category;
            /** @var InquiryStatus $status */
            $status = $inquiry->status;
            /** @var InquiryPriority $priority */
            $priority = $inquiry->priority;
            /** @var User|null $staff */
            $staff = $inquiry->staff;

            return [
                'id' => $inquiry->id,
                'inquiry_number' => $inquiry->inquiry_number,
                'category' => $category->value,
                'category_label' => $category->label(),
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
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function getEnumOptions(): array
    {
        return [
            'categories' => collect(InquiryCategory::cases())->map(fn (InquiryCategory $c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
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
                        'category' => $validated['category'],
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
