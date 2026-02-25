<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/password');

        $response->assertRedirect('/login');
    }

    public function test_パスワード変更画面が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/password');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Password/Edit'));
    }

    public function test_正しい現在のパスワードでパスワード変更できる(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user)->patch('/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('password.edit'));
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_パスワード変更後にtemporary_password_expires_atがnullになる(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)->patch('/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $user->refresh();
        $this->assertNull($user->temporary_password_expires_at);
    }

    public function test_現在のパスワードが間違っていると変更できない(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->actingAs($user)->patch('/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_パスワード確認が一致しないと変更できない(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user)->patch('/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_一時パスワードユーザーはダッシュボードからパスワード変更画面にリダイレクトされる(): void
    {
        $user = User::factory()->create([
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('password.edit'));
    }

    public function test_一時パスワードユーザーはパスワード変更画面にはアクセスできる(): void
    {
        $user = User::factory()->create([
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/password');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Password/Edit')
                ->where('requiresPasswordChange', true)
            );
    }

    public function test_一時パスワード期限切れユーザーはログアウトされる(): void
    {
        $user = User::factory()->create([
            'temporary_password_expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_非管理者もパスワードを変更できる(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->patch('/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('password.edit'));
    }
}
