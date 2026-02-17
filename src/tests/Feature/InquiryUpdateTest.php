<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインだとログイン画面にリダイレクトされる(): void
    {
        $inquiry = Inquiry::factory()->create();

        $response = $this->patch("/inquiries/{$inquiry->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_ステータスを変更できる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry));
        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_優先度を変更できる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['priority' => 'medium']);

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'priority' => 'urgent',
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry));
        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'priority' => 'urgent',
        ]);
    }

    public function test_担当者を割り当てられる(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['staff_id' => null]);

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'staff_id' => $staff->id,
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry));
        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'staff_id' => $staff->id,
        ]);
    }

    public function test_担当者を未割当に戻せる(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create();
        $inquiry = Inquiry::factory()->create(['staff_id' => $staff->id]);

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'staff_id' => null,
        ]);

        $response->assertRedirect(route('inquiries.show', $inquiry));
        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'staff_id' => null,
        ]);
    }

    public function test_不正なステータス値で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_存在しない担当者で422エラーになる(): void
    {
        $user = User::factory()->create();
        $inquiry = Inquiry::factory()->create();

        $response = $this->actingAs($user)->patch("/inquiries/{$inquiry->id}", [
            'staff_id' => 99999,
        ]);

        $response->assertSessionHasErrors('staff_id');
    }
}
