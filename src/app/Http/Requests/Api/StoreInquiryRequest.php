<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:inquiry_categories,id'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_email' => ['required', 'email', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'カテゴリ',
            'order_number' => '注文番号',
            'subject' => '件名',
            'body' => 'お問い合わせ内容',
            'customer_name' => 'お名前',
            'customer_email' => 'メールアドレス',
        ];
    }
}
