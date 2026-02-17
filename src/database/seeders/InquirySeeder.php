<?php

namespace Database\Seeders;

use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InquirySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffIds = User::pluck('id')->toArray();

        // 問い合わせ 30件
        $inquiries = Inquiry::factory()
            ->count(30)
            ->sequence(fn ($sequence) => [
                'staff_id' => fake()->optional(0.7)->randomElement($staffIds),
            ])
            ->create();

        // 各問い合わせにメッセージを紐付け
        foreach ($inquiries as $inquiry) {
            // 最初の問い合わせメッセージ（必ず1件）
            InquiryMessage::factory()->create([
                'inquiry_id' => $inquiry->id,
                'staff_id' => null,
                'message_type' => 'initial_inquiry',
                'created_at' => $inquiry->created_at,
                'updated_at' => $inquiry->created_at,
            ]);

            // 追加のやり取り（0〜3件）
            $replyCount = fake()->numberBetween(0, 3);
            $lastDate = $inquiry->created_at;

            for ($i = 0; $i < $replyCount; $i++) {
                $lastDate = fake()->dateTimeBetween($lastDate, 'now');

                if ($i % 2 === 0) {
                    // 担当者からの返信
                    InquiryMessage::factory()->create([
                        'inquiry_id' => $inquiry->id,
                        'staff_id' => $inquiry->staff_id ?? fake()->randomElement($staffIds),
                        'message_type' => 'staff_reply',
                        'created_at' => $lastDate,
                        'updated_at' => $lastDate,
                    ]);
                } else {
                    // 顧客からの返信
                    InquiryMessage::factory()->create([
                        'inquiry_id' => $inquiry->id,
                        'staff_id' => null,
                        'message_type' => 'customer_reply',
                        'created_at' => $lastDate,
                        'updated_at' => $lastDate,
                    ]);
                }
            }
        }
    }
}
