<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->get("/inquiries/{$inquiry->id}");

        $response->assertRedirect('/login');
    }

    public function test_ログイン後に詳細ページが表示される(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->get("/inquiries/{$inquiry->id}");

        $response->assertStatus(200);
    }

    public function test_問い合わせデータがpropsに含まれる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create([
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->get("/inquiries/{$inquiry->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Inquiry/Show')
            ->where('inquiry.customer_name', 'テスト顧客')
            ->where('inquiry.customer_email', 'test@example.com')
            ->where('inquiry.inquiry_number', $inquiry->inquiry_number)
        );
    }

    public function test_メッセージがpropsに含まれる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();
        InquiryMessage::factory()->create([
            'inquiry_id' => $inquiry->id,
            'message_type' => 'initial_inquiry',
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
        ]);

        $response = $this->actingAs($user)->get("/inquiries/{$inquiry->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Inquiry/Show')
            ->has('inquiry.messages', 1)
            ->where('inquiry.messages.0.subject', 'テスト件名')
            ->where('inquiry.messages.0.body', 'テスト本文')
        );
    }

    public function test_存在しない問い合わせは404を返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inquiries/99999');

        $response->assertStatus(404);
    }
}
