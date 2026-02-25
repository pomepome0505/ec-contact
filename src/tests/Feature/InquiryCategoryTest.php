<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\InquiryCategory;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InquiryCategorySeeder::class);
    }

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/categories');

        $response->assertRedirect('/login');
    }

    public function test_非管理者はカテゴリ一覧にアクセスできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertStatus(403);
    }

    public function test_非管理者はカテゴリ作成にアクセスできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/categories/create');

        $response->assertStatus(403);
    }

    public function test_非管理者はカテゴリ編集にアクセスできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->get("/categories/{$category->id}/edit");

        $response->assertStatus(403);
    }

    public function test_非管理者はカテゴリを更新できない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->patch("/categories/{$category->id}", [
            'name' => '不正な更新',
        ]);

        $response->assertStatus(403);
    }

    public function test_非管理者はカテゴリを無効化できない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->patch("/categories/{$category->id}/toggle-active");

        $response->assertStatus(403);
    }

    public function test_管理者でログイン後に一覧ページが表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Category/Index')
                ->has('categories', 6)
            );
    }

    public function test_カテゴリ作成画面が表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get('/categories/create');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Category/Create'));
    }

    public function test_カテゴリを作成できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'テストカテゴリ',
            'display_order' => 10,
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('inquiry_categories', [
            'name' => 'テストカテゴリ',
            'display_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_カテゴリ名が空だと422エラーになる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/categories', [
            'name' => '',
            'display_order' => 1,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_カテゴリ編集画面が表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->get("/categories/{$category->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Category/Edit')
                ->where('category.name', $category->name)
            );
    }

    public function test_カテゴリを編集できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->patch("/categories/{$category->id}", [
            'name' => '更新されたカテゴリ',
            'display_order' => 99,
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('inquiry_categories', [
            'id' => $category->id,
            'name' => '更新されたカテゴリ',
            'display_order' => 99,
        ]);
    }

    public function test_カテゴリを無効化できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->patch("/categories/{$category->id}/toggle-active");

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('inquiry_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_無効化されたカテゴリを有効化できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::first();
        $category->update(['is_active' => false]);

        $response = $this->actingAs($user)->patch("/categories/{$category->id}/toggle-active");

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('inquiry_categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_カテゴリ取得apiで有効なカテゴリのみ返される(): void
    {
        $category = InquiryCategory::first();
        $category->update(['is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id')->toArray();
        $this->assertNotContains($category->id, $ids);
    }

    public function test_カテゴリを削除できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::factory()->create();

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('inquiry_categories', ['id' => $category->id]);
    }

    public function test_問い合わせが紐づくカテゴリは削除できない(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $category = InquiryCategory::first();

        Inquiry::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('inquiry_categories', ['id' => $category->id]);
    }

    public function test_非管理者はカテゴリを削除できない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $category = InquiryCategory::first();

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertStatus(403);
    }
}
