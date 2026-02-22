<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryCustomerMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->post("/inquiries/{$inquiry->id}/customer-message", [
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_顧客メッセージが正常に作成される(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/customer-message", [
            'subject' => '顧客からの件名',
            'body' => '顧客からの本文',
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry->id));
        $this->assertDatabaseHas('inquiry_messages', [
            'inquiry_id' => $inquiry->id,
            'staff_id' => null,
            'message_type' => 'customer_reply',
            'subject' => '顧客からの件名',
            'body' => '顧客からの本文',
        ]);
    }

    public function test_件名未入力で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/customer-message", [
            'subject' => '',
            'body' => '顧客からの本文',
        ]);

        $response->assertSessionHasErrors('subject');
    }

    public function test_本文未入力で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->post("/inquiries/{$inquiry->id}/customer-message", [
            'subject' => '顧客からの件名',
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }
}
