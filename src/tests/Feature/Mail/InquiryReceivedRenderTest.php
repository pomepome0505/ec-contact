<?php

namespace Tests\Feature\Mail;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryReceivedRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_受付完了メールが正常にレンダリングされる(): void
    {
        $inquiry = Inquiry::factory()->create([
            'customer_name' => 'テスト太郎',
            'inquiry_number' => 'INQ-20260221-0001',
            'order_number' => 'ORD-12345678',
        ]);
        $inquiry->load('category');

        InquiryMessage::factory()->create([
            'inquiry_id' => $inquiry->id,
            'message_type' => 'initial_inquiry',
            'subject' => 'テスト件名',
            'body' => 'テスト本文です。',
        ]);

        $mailable = new InquiryReceived($inquiry);
        $rendered = $mailable->render();

        $this->assertStringContainsString('テスト太郎', $rendered);
        $this->assertStringContainsString('INQ-20260221-0001', $rendered);
        $this->assertStringContainsString($inquiry->category->name, $rendered);
        $this->assertStringContainsString('ORD-12345678', $rendered);
        $this->assertStringContainsString('テスト件名', $rendered);
        $this->assertStringContainsString('テスト本文です。', $rendered);
    }

    public function test_注文番号なしでもレンダリングされる(): void
    {
        $inquiry = Inquiry::factory()->create([
            'order_number' => null,
        ]);
        $inquiry->load('category');

        InquiryMessage::factory()->create([
            'inquiry_id' => $inquiry->id,
            'message_type' => 'initial_inquiry',
            'subject' => 'テスト件名',
            'body' => 'テスト本文です。',
        ]);

        $mailable = new InquiryReceived($inquiry);
        $rendered = $mailable->render();

        $this->assertStringNotContainsString('注文番号:', $rendered);
    }
}
