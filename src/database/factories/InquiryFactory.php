<?php

namespace Database\Factories;

use App\Enums\InquiryCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inquiry>
 */
class InquiryFactory extends Factory
{
    private const CUSTOMER_NAMES = [
        '田中 太郎', '佐藤 花子', '鈴木 一郎', '高橋 美咲', '伊藤 健太',
        '渡辺 由美', '山本 大輔', '中村 あかり', '小林 誠', '加藤 裕子',
        '吉田 翔太', '山田 さくら', '松本 拓也', '井上 真理', '木村 隆',
        '林 恵美', '斎藤 直人', '清水 陽菜', '山口 和也', '阿部 麻衣',
    ];

    private const INTERNAL_NOTES = [
        '顧客から電話でも同様の問い合わせあり。急ぎで対応が必要。',
        '過去にも同じ商品でクレームあり。要注意顧客。',
        '返品処理済み。返金は経理部門に依頼中。',
        '在庫確認中。倉庫チームに問い合わせ済み。',
        '配送業者に調査依頼済み。回答待ち。',
        'VIP顧客のため優先対応。上長に報告済み。',
        '技術的な問題のため、開発チームにエスカレーション中。',
        '同様の問い合わせが複数件あり。システム障害の可能性。',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-3 months', 'now');
        $seq = fake()->unique()->numberBetween(1, 9999);

        return [
            'inquiry_number' => 'INQ-'.$date->format('Ymd').'-'.str_pad($seq, 4, '0', STR_PAD_LEFT),
            'staff_id' => fake()->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [null]),
            'order_number' => fake()->optional(0.6)->numerify('ORD-########'),
            'category' => fake()->randomElement(InquiryCategory::cases()),
            'customer_name' => fake()->randomElement(self::CUSTOMER_NAMES),
            'customer_email' => fake()->safeEmail(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'resolved', 'closed']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'internal_notes' => fake()->optional(0.3)->randomElement(self::INTERNAL_NOTES),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
