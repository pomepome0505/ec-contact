<?php

namespace App\Enums;

enum InquiryChannel: string
{
    case Form = 'form';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Form => 'フォーム',
            self::Phone => '電話',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Form => 'primary',
            self::Phone => 'success',
        };
    }
}
