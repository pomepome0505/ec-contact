<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\InquiryCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/employees');

        $response->assertRedirect('/login');
    }

    public function test_非管理者は従業員一覧にアクセスできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(403);
    }

    public function test_非管理者は従業員作成にアクセスできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertStatus(403);
    }

    public function test_管理者でログイン後に一覧ページが表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Employee/Index')
                ->has('employees', 1)
            );
    }

    public function test_従業員作成画面が表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Employee/Create'));
    }

    public function test_従業員を作成できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/employees', [
            'login_id' => 'new_user',
            'name' => '新規ユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'login_id' => 'new_user',
            'name' => '新規ユーザー',
            'is_active' => true,
        ]);
    }

    public function test_パスワード確認が一致しないと作成できない(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/employees', [
            'login_id' => 'new_user',
            'name' => '新規ユーザー',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_重複するログイン_idでは作成できない(): void
    {
        $user = User::factory()->create(['login_id' => 'existing', 'is_admin' => true]);

        $response = $this->actingAs($user)->post('/employees', [
            'login_id' => 'existing',
            'name' => '重複ユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('login_id');
    }

    public function test_従業員編集画面が表示される(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['name' => '編集対象']);

        $response = $this->actingAs($user)->get("/employees/{$target->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Employee/Edit')
                ->where('employee.name', '編集対象')
            );
    }

    public function test_従業員を編集できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['name' => '変更前']);

        $response = $this->actingAs($user)->patch("/employees/{$target->id}", [
            'name' => '変更後',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => '変更後',
        ]);
    }

    public function test_編集時にログインidは変更できない(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['login_id' => 'original_id']);

        $this->actingAs($user)->patch("/employees/{$target->id}", [
            'login_id' => 'changed_id',
            'name' => '更新名',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'login_id' => 'original_id',
            'name' => '更新名',
        ]);
    }

    public function test_従業員を無効化できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->patch("/employees/{$target->id}/toggle-active");

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }

    public function test_無効化された従業員を有効化できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->patch("/employees/{$target->id}/toggle-active");

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }

    public function test_自分自身は無効化できない(): void
    {
        $user = User::factory()->create(['is_active' => true, 'is_admin' => true]);

        $response = $this->actingAs($user)->patch("/employees/{$user->id}/toggle-active");

        $response->assertRedirect();
        $response->assertSessionHasErrors('toggleActive');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_管理者権限を設定して従業員を作成できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->post('/employees', [
            'login_id' => 'admin_user',
            'name' => '管理者ユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'login_id' => 'admin_user',
            'is_admin' => true,
        ]);
    }

    public function test_管理者が他ユーザーのパスワードをリセットできる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/employees/{$target->id}/reset-password");

        $response->assertStatus(200)
            ->assertJsonStructure(['password']);

        $password = $response->json('password');
        $this->assertEquals(12, strlen($password));

        $target->refresh();
        $this->assertNotNull($target->temporary_password_expires_at);
    }

    public function test_自分自身のパスワードリセットは409(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->postJson("/employees/{$user->id}/reset-password");

        $response->assertStatus(409);
    }

    public function test_非管理者はパスワードリセットできない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $response = $this->actingAs($user)->postJson("/employees/{$target->id}/reset-password");

        $response->assertStatus(403);
    }

    public function test_従業員を削除できる(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $response = $this->actingAs($user)->delete("/employees/{$target->id}");

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_自分自身は削除できない(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($user)->delete("/employees/{$user->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_問い合わせが紐づく従業員は削除できない(): void
    {
        $this->seed(InquiryCategorySeeder::class);

        $user = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        Inquiry::factory()->create(['staff_id' => $target->id]);

        $response = $this->actingAs($user)->delete("/employees/{$target->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_非管理者は従業員を削除できない(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $response = $this->actingAs($user)->delete("/employees/{$target->id}");

        $response->assertStatus(403);
    }
}
