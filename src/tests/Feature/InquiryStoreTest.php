<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/inquiries/create');

        $response->assertRedirect('/login');
    }

    public function test_問い合わせ作成画面が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inquiries/create');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Inquiry/Create')
                ->has('categories')
                ->has('channels')
                ->has('statuses')
                ->has('priorities')
                ->has('staffs')
            );
    }

    public function test_電話問い合わせが正常に作成される(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'phone',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => '',
            'order_number' => 'ORD-12345678',
            'internal_notes' => '電話にて商品不良の問い合わせ。折り返し希望。',
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $inquiry = Inquiry::latest()->first();
        $response->assertRedirect(route('inquiries.show', $inquiry->id));

        $this->assertDatabaseHas('inquiries', [
            'channel' => 'phone',
            'customer_name' => 'テスト顧客',
            'customer_email' => null,
            'order_number' => 'ORD-12345678',
            'internal_notes' => '電話にて商品不良の問い合わせ。折り返し希望。',
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $this->assertDatabaseMissing('inquiry_messages', [
            'inquiry_id' => $inquiry->id,
        ]);
    }

    public function test_フォーム問い合わせが正常に作成される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'form',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $inquiry = Inquiry::latest()->first();
        $response->assertRedirect(route('inquiries.show', $inquiry->id));

        $this->assertDatabaseHas('inquiries', [
            'channel' => 'form',
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('inquiry_messages', [
            'inquiry_id' => $inquiry->id,
            'message_type' => 'initial_inquiry',
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
        ]);
    }

    public function test_顧客名未入力で422エラー(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'phone',
            'category_id' => 1,
            'customer_name' => '',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('customer_name');
    }

    public function test_フォーム経由でメールアドレス未入力だと422エラー(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'form',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => '',
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('customer_email');
    }

    public function test_電話問い合わせでメールアドレスなしで作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'phone',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => '',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $inquiry = Inquiry::latest()->first();
        $response->assertRedirect(route('inquiries.show', $inquiry->id));

        $this->assertDatabaseHas('inquiries', [
            'channel' => 'phone',
            'customer_name' => 'テスト顧客',
            'customer_email' => null,
        ]);
    }

    public function test_フォーム経由で件名未入力だと422エラー(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'form',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
            'subject' => '',
            'body' => 'テスト本文',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('subject');
    }

    public function test_フォーム経由で本文未入力だと422エラー(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'form',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
            'subject' => 'テスト件名',
            'body' => '',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('body');
    }

    public function test_電話問い合わせでは件名と本文がなくても作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'phone',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => '',
            'subject' => '',
            'body' => '',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $inquiry = Inquiry::latest()->first();
        $response->assertRedirect(route('inquiries.show', $inquiry->id));
    }

    public function test_不正なchannel値で422エラー(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/inquiries', [
            'channel' => 'invalid',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => 'test@example.com',
            'subject' => 'テスト件名',
            'body' => 'テスト本文',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('channel');
    }

    public function test_電話問い合わせ作成時にメールが送信されない(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/inquiries', [
            'channel' => 'phone',
            'category_id' => 1,
            'customer_name' => 'テスト顧客',
            'customer_email' => '',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        Mail::assertNothingSent();
    }
}
