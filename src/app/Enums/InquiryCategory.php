<?php

namespace App\Enums;

enum InquiryCategory: string
{
    case Product = 'product';
    case Order = 'order';
    case Shipping = 'shipping';
    case Return = 'return';
    case System = 'system';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Product => '商品について',
            self::Order => '注文について',
            self::Shipping => '配送について',
            self::Return => '返品・交換について',
            self::System => 'システムについて',
            self::Other => 'その他',
        };
    }
}
