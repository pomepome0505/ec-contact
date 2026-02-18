{{ $inquiry->customer_name }} 様

この度はお問い合わせいただき、ありがとうございます。
以下の内容で受け付けいたしました。担当者より順次ご回答いたしますので、しばらくお待ちください。

受付番号: {{ $inquiry->inquiry_number }}

──────────────────────────────────

カテゴリ: {{ $inquiry->category->label() }}
@if($inquiry->order_number)
注文番号: {{ $inquiry->order_number }}
@endif
件名: {{ $initialMessage->subject }}

【お問い合わせ内容】
{{ $initialMessage->body }}

──────────────────────────────────

受付日時: {{ $inquiry->created_at->format('Y年m月d日 H:i') }}

ご不明な点がございましたら、お気軽にお問い合わせください。

※このメールは自動送信されています。このメールに直接返信することはできません。

株式会社ライフスタイルマート カスタマーサポート
