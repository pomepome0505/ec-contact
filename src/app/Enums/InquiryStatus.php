<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未対応',
            self::InProgress => '対応中',
            self::Resolved => '対応完了',
            self::Closed => 'クローズ',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'info',
            self::Resolved => 'success',
            self::Closed => 'secondary',
        };
    }
}
