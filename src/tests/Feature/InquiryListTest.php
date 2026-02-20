<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/inquiries');

        $response->assertRedirect('/login');
    }

    public function test_ログイン後に一覧ページが表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inquiries');

        $response->assertStatus(200);
    }

    public function test_一覧に問い合わせデータが含まれる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create([
            'customer_name' => 'テスト顧客',
        ]);

        $response = $this->actingAs($user)->get('/inquiries');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Inquiry/Index')
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.customer_name', 'テスト顧客')
            );
    }

    public function test_ページネーションが機能する(): void
    {
        $user = User::factory()->create();
        Inquiry::factory()->count(20)->create();

        $response = $this->actingAs($user)->get('/inquiries');

        $response->assertInertia(fn ($page) => $page
            ->has('inquiries.data', 15)
            ->where('inquiries.last_page', 2)
        );

        $response2 = $this->actingAs($user)->get('/inquiries?page=2');

        $response2->assertInertia(fn ($page) => $page
            ->has('inquiries.data', 5)
        );
    }

    public function test_一覧にステータスとカテゴリの選択肢が含まれる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/inquiries');

        $response->assertInertia(fn ($page) => $page
            ->has('categories', 6)
            ->has('statuses', 4)
            ->has('priorities', 4)
        );
    }
}
