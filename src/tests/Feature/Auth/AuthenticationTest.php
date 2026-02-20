<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_無効化されたユーザーはログインできない(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login_id');
    }

    public function test_一時パスワード期限切れでログインできない(): void
    {
        $user = User::factory()->create([
            'temporary_password_expires_at' => now()->subDay(),
        ]);

        $response = $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login_id');
    }

    public function test_一時パスワード期限内でログインできる(): void
    {
        $user = User::factory()->create([
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        $response = $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login_id' => $user->login_id,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
