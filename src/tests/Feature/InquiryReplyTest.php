<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->post("/inquiries/{$inquiry->id}/reply", [
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_返信を送信するとメッセージが作成される(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/reply", [
            'subject' => '回答件名',
            'body' => '回答本文',
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry->id));
        $this->assertDatabaseHas('inquiry_messages', [
            'inquiry_id' => $inquiry->id,
            'staff_id' => $user->id,
            'message_type' => 'staff_reply',
            'subject' => '回答件名',
            'body' => '回答本文',
        ]);
    }

    public function test_返信を送信するとメールが送信される(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);

        $this->actingAs($user)->post("/inquiries/{$inquiry->id}/reply", [
            'subject' => '回答件名',
            'body' => '回答本文',
        ]);

        Mail::assertSent(\App\Mail\InquiryReply::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    public function test_件名未入力で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/reply", [
            'subject' => '',
            'body' => '回答本文',
        ]);

        $response->assertSessionHasErrors('subject');
    }

    public function test_本文未入力で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/reply", [
            'subject' => '回答件名',
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }
}
