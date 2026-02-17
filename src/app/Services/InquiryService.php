<?php

namespace App\Services;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InquiryService
{
    private const MAX_RETRIES = 5;

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
