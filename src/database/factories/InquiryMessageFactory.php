<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InquiryMessage>
 */
class InquiryMessageFactory extends Factory
{
    private const SUBJECTS = [
        'initial_inquiry' => [
            '商品が届きません',
            '注文した商品と違うものが届きました',
            '商品の返品・交換について',
            '注文のキャンセルをお願いしたいです',
            '配送状況を教えてください',
            '商品に破損がありました',
            '請求金額が間違っています',
            '会員情報の変更について',
            'ポイントが反映されていません',
            '届いた商品に不具合があります',
            '配送先の住所を変更したいです',
            '領収書の発行をお願いします',
        ],
        'customer_reply' => [
            'ご回答ありがとうございます',
            '追加で質問があります',
            '確認しましたが改善されません',
            '写真を添付します',
            '別の方法を試しましたが解決しません',
            '了解しました。よろしくお願いします',
        ],
        'staff_reply' => [
            'お問い合わせの件について',
            'ご注文状況のご報告',
            '返品・交換手続きのご案内',
            '調査結果のご報告',
            '対応状況のお知らせ',
            'ご確認のお願い',
        ],
    ];

    private const BODIES = [
        'initial_inquiry' => [
            "先日注文した商品（注文番号は問い合わせに記載）が、予定日を過ぎても届きません。\n配送状況を確認していただけますでしょうか。\nよろしくお願いいたします。",
            "本日届いた商品を確認したところ、注文したものと異なる商品が入っていました。\n正しい商品を再送していただけますでしょうか。\n届いた商品については返送の手順を教えてください。",
            "購入した商品のサイズが合わなかったため、交換をお願いしたいです。\n未使用・タグ付きの状態です。\n交換の手続き方法を教えてください。",
            "間違えて注文してしまったため、キャンセルをお願いしたいです。\nまだ発送前であれば対応いただけると助かります。",
            "商品が届いたのですが、外箱に凹みがあり、中の商品にも傷がついていました。\n交換または返金の対応をお願いできますでしょうか。",
            "クレジットカードの明細を確認したところ、注文金額と請求額が異なっています。\n注文時の金額は3,980円でしたが、4,980円が請求されています。\n確認をお願いいたします。",
            "先月購入した商品を使用したところ、電源が入らなくなりました。\n保証期間内だと思いますので、修理または交換の対応をお願いします。",
            "会員登録時のメールアドレスを変更したいのですが、マイページから変更できません。\n手続き方法を教えてください。",
        ],
        'customer_reply' => [
            "ご回答ありがとうございます。\n確認いたしました。指示に従って対応いたします。",
            "ご連絡ありがとうございます。\n追加で確認したいことがあるのですが、返送の送料はどちらの負担になりますでしょうか。",
            "教えていただいた方法を試しましたが、状況が改善されません。\n他に対応方法があれば教えてください。",
            "承知いたしました。\n必要な書類を準備のうえ、改めてご連絡いたします。\nよろしくお願いいたします。",
            "ご対応ありがとうございます。\n返金の処理にはどのくらいの期間がかかりますでしょうか。",
        ],
        'staff_reply' => [
            "お問い合わせいただきありがとうございます。\n\n確認いたしましたところ、ご注文の商品は現在配送中でございます。\n追跡番号をお送りいたしますので、配送状況をご確認ください。\n\n追跡番号: 1234-5678-9012\n\nご不明な点がございましたら、お気軽にお問い合わせください。",
            "ご不便をおかけし、大変申し訳ございません。\n\n返品・交換の手続きをご案内いたします。\n以下の手順で商品をご返送ください。\n\n1. 商品を元の梱包材に入れてください\n2. 同梱の返品用伝票をご使用ください\n3. お近くの配送業者にお持ちください\n\n返送料は弊社負担でございます。",
            "お問い合わせの件、調査いたしました。\n\nご指摘の通り、請求金額に誤りがございました。\n差額の返金処理を進めさせていただきます。\nクレジットカードへの返金は5〜10営業日程度でございます。\n\n重ねてお詫び申し上げます。",
            "ご連絡いただきありがとうございます。\n\nご注文のキャンセル処理が完了いたしました。\n返金はご利用の決済方法に応じて、数日以内に処理されます。\n\nまたのご利用をお待ちしております。",
            "お問い合わせいただきありがとうございます。\n\n商品の不具合について確認いたしました。\n保証期間内のため、無償で交換対応をさせていただきます。\n代替品を本日中に発送いたします。\n\n不具合品につきましては、同梱の返送用伝票でご返送ください。",
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messageType = fake()->randomElement(['initial_inquiry', 'customer_reply', 'staff_reply']);
        $staffId = $messageType === 'staff_reply'
            ? User::inRandomOrder()->value('id')
            : null;

        return [
            'inquiry_id' => Inquiry::factory(),
            'staff_id' => $staffId,
            'message_type' => $messageType,
            'subject' => fake()->randomElement(self::SUBJECTS[$messageType]),
            'body' => fake()->randomElement(self::BODIES[$messageType]),
        ];
    }

    public function forType(string $messageType): static
    {
        return $this->state(fn () => [
            'message_type' => $messageType,
            'subject' => fake()->randomElement(self::SUBJECTS[$messageType]),
            'body' => fake()->randomElement(self::BODIES[$messageType]),
        ]);
    }
}
