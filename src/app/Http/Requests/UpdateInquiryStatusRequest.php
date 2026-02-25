<?php

namespace App\Http\Requests;

use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInquiryStatusRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(InquiryStatus::class)],
            'priority' => ['sometimes', Rule::enum(InquiryPriority::class)],
            'staff_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'internal_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'ステータス',
            'priority' => '優先度',
            'staff_id' => '担当者',
            'internal_notes' => '社内メモ',
        ];
    }
}
