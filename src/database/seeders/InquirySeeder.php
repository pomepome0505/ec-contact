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
            $this->createMessage($inquiry, 'initial_inquiry', null, $inquiry->created_at);

            // 追加のやり取り（0〜3件）
            $replyCount = fake()->numberBetween(0, 3);
            $lastDate = $inquiry->created_at;

            for ($i = 0; $i < $replyCount; $i++) {
                $lastDate = fake()->dateTimeBetween($lastDate, 'now');

                if ($i % 2 === 0) {
                    $staffId = $inquiry->staff_id ?? fake()->randomElement($staffIds);
                    $this->createMessage($inquiry, 'staff_reply', $staffId, $lastDate);
                } else {
                    $this->createMessage($inquiry, 'customer_reply', null, $lastDate);
                }
            }
        }
    }

    private function createMessage(Inquiry $inquiry, string $messageType, ?int $staffId, mixed $date): void
    {
        InquiryMessage::factory()->forType($messageType)->create([
            'inquiry_id' => $inquiry->id,
            'staff_id' => $staffId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
