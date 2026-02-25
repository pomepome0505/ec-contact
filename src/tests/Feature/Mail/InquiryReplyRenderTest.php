<?php

namespace Tests\Feature\Mail;

use App\Mail\InquiryReply;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryReplyRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_返信メールが正常にレンダリングされる(): void
    {
        $staff = User::factory()->create();
        $inquiry = Inquiry::factory()->create([
            'customer_name' => 'テスト太郎',
            'inquiry_number' => 'INQ-20260221-0001',
        ]);

        $message = InquiryMessage::factory()->create([
            'inquiry_id' => $inquiry->id,
            'staff_id' => $staff->id,
            'message_type' => 'staff_reply',
            'subject' => '回答件名',
            'body' => '回答本文です。',
        ]);

        $mailable = new InquiryReply($inquiry, $message);
        $rendered = $mailable->render();

        $this->assertStringContainsString('テスト太郎', $rendered);
        $this->assertStringContainsString('INQ-20260221-0001', $rendered);
        $this->assertStringContainsString('回答本文です。', $rendered);
    }
}
