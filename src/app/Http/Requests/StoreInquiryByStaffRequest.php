<?php

namespace App\Http\Requests;

use App\Enums\InquiryChannel;
use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreInquiryByStaffRequest extends FormRequest
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
        $isForm = $this->input('channel') === 'form';

        return [
            'channel' => ['required', new Enum(InquiryChannel::class)],
            'category_id' => ['required', 'exists:inquiry_categories,id'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_email' => [$isForm ? 'required' : 'nullable', 'email', 'max:100'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'subject' => [$isForm ? 'required' : 'nullable', 'string', 'max:200'],
            'body' => [$isForm ? 'required' : 'nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', new Enum(InquiryStatus::class)],
            'priority' => ['required', new Enum(InquiryPriority::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'channel' => '受付区分',
            'category_id' => 'カテゴリ',
            'customer_name' => '顧客名',
            'customer_email' => 'メールアドレス',
            'order_number' => '注文番号',
            'subject' => '件名',
            'body' => '本文',
            'internal_notes' => '社内メモ',
            'staff_id' => '担当者',
            'status' => 'ステータス',
            'priority' => '優先度',
        ];
    }
}
