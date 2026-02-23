<?php

namespace App\Models;

use App\Enums\InquiryChannel;
use App\Enums\InquiryPriority;
use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read InquiryCategory|null $category
 */
class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_number',
        'staff_id',
        'order_number',
        'category_id',
        'channel',
        'customer_name',
        'customer_email',
        'status',
        'priority',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'channel' => InquiryChannel::class,
            'status' => InquiryStatus::class,
            'priority' => InquiryPriority::class,
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InquiryCategory::class, 'category_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InquiryMessage::class);
    }
}
