<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $login_id
 * @property string $name
 * @property string $password
 * @property bool $is_active
 * @property bool $is_admin
 * @property \Carbon\Carbon|null $temporary_password_expires_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'login_id',
        'name',
        'password',
        'is_active',
        'is_admin',
        'temporary_password_expires_at',
    ];

    /**
     * テーブル定義にremember_tokenカラムがないため無効化
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
        ];
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'staff_id');
    }

    public function inquiryMessages(): HasMany
    {
        return $this->hasMany(InquiryMessage::class, 'staff_id');
    }
}
