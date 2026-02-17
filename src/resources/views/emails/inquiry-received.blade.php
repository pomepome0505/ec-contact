<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; background-color: #F5F7FA; font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F5F7FA; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 8px; overflow: hidden;">
                    {{-- ヘッダー --}}
                    <tr>
                        <td style="background-color: #2563EB; padding: 24px 32px; color: #FFFFFF;">
                            <h1 style="margin: 0; font-size: 18px; font-weight: bold;">お問い合わせを受け付けました</h1>
                            <p style="margin: 8px 0 0; font-size: 13px; opacity: 0.9;">株式会社ライフスタイルマート カスタマーサポート</p>
                        </td>
                    </tr>

                    {{-- 本文 --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px; font-size: 14px; color: #0F172A;">
                                {{ $inquiry->customer_name }} 様
                            </p>
                            <p style="margin: 0 0 24px; font-size: 14px; color: #0F172A; line-height: 1.6;">
                                この度はお問い合わせいただき、ありがとうございます。<br>
                                以下の内容で受け付けいたしました。担当者より順次ご回答いたしますので、しばらくお待ちください。
                            </p>

                            {{-- 受付番号 --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #EFF6FF; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 24px; text-align: center;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #64748B;">受付番号</p>
                                        <p style="margin: 0; font-size: 20px; font-weight: bold; color: #2563EB;">{{ $inquiry->inquiry_number }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- 問い合わせ内容 --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #64748B; width: 120px;">カテゴリ</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; color: #0F172A;">{{ $inquiry->category->label() }}</td>
                                </tr>
                                @if($inquiry->order_number)
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #64748B;">注文番号</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; color: #0F172A;">{{ $inquiry->order_number }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #64748B;">件名</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; color: #0F172A;">{{ $initialMessage->subject }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #F8FAFC; font-size: 13px; color: #64748B; vertical-align: top;">お問い合わせ内容</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #0F172A; line-height: 1.6;">{!! nl2br(e($initialMessage->body)) !!}</td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 0; font-size: 13px; color: #64748B; line-height: 1.6;">
                                受付日時: {{ $inquiry->created_at->format('Y年m月d日 H:i') }}
                            </p>
                        </td>
                    </tr>

                    {{-- フッター --}}
                    <tr>
                        <td style="padding: 24px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0;">
                            <p style="margin: 0; font-size: 12px; color: #94A3B8; text-align: center;">
                                このメールは自動送信されています。このメールに直接返信することはできません。<br>
                                &copy; {{ date('Y') }} 株式会社ライフスタイルマート
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
