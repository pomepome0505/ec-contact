<?php

namespace Tests\Feature\Api;

use App\Mail\InquiryReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'category' => 'product',
            'order_number' => 'ORD-12345678',
            'subject' => 'テスト問い合わせ',
            'body' => '商品について質問があります。',
            'customer_name' => 'テスト太郎',
            'customer_email' => 'test@example.com',
        ], $overrides);
    }

    public function test_問い合わせを正常に受け付ける(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/inquiries', $this->validData());

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'inquiry_number']);
    }

    public function test_送信するとデータベースにレコードが作成される(): void
    {
        Mail::fake();

        $this->postJson('/api/inquiries', $this->validData());

        $this->assertDatabaseHas('inquiries', [
            'category' => 'product',
            'customer_name' => 'テスト太郎',
            'customer_email' => 'test@example.com',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('inquiry_messages', [
            'message_type' => 'initial_inquiry',
            'subject' => 'テスト問い合わせ',
            'body' => '商品について質問があります。',
        ]);
    }

    public function test_送信すると受付完了メールが送信される(): void
    {
        Mail::fake();

        $this->postJson('/api/inquiries', $this->validData());

        Mail::assertSent(InquiryReceived::class, function (InquiryReceived $mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_受付番号が所定の形式で生成される(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/inquiries', $this->validData());

        $inquiryNumber = $response->json('inquiry_number');
        $today = now()->format('Ymd');
        $this->assertMatchesRegularExpression("/^INQ-{$today}-\d{4}$/", $inquiryNumber);
    }

    public function test_連続送信で受付番号が連番になる(): void
    {
        Mail::fake();

        $response1 = $this->postJson('/api/inquiries', $this->validData());
        $response2 = $this->postJson('/api/inquiries', $this->validData([
            'customer_email' => 'test2@example.com',
        ]));

        $today = now()->format('Ymd');
        $this->assertEquals("INQ-{$today}-0001", $response1->json('inquiry_number'));
        $this->assertEquals("INQ-{$today}-0002", $response2->json('inquiry_number'));
    }

    public function test_必須項目が未入力だと422エラーになる(): void
    {
        $response = $this->postJson('/api/inquiries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'category',
                'subject',
                'body',
                'customer_name',
                'customer_email',
            ]);
    }

    public function test_不正なカテゴリだと422エラーになる(): void
    {
        $response = $this->postJson('/api/inquiries', $this->validData([
            'category' => 'invalid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    public function test_不正なメールアドレス形式だと422エラーになる(): void
    {
        $response = $this->postJson('/api/inquiries', $this->validData([
            'customer_email' => 'not-email',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_件名が200文字を超えると422エラーになる(): void
    {
        $response = $this->postJson('/api/inquiries', $this->validData([
            'subject' => str_repeat('あ', 201),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_注文番号は任意で送信できる(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/inquiries', $this->validData([
            'order_number' => null,
        ]));

        $response->assertStatus(201);
    }
}
