<?php

namespace App\Models;

use App\Enums\InquiryCategory;
use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_number',
        'staff_id',
        'order_number',
        'category',
        'customer_name',
        'customer_email',
        'status',
        'priority',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => InquiryCategory::class,
            'status' => InquiryStatus::class,
            'priority' => InquiryPriority::class,
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InquiryMessage::class);
    }
}
