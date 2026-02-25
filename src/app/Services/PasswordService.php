<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PasswordService
{
    public function changePassword(User $user, string $newPassword, string $currentSessionId): void
    {
        $user->update([
            'password' => $newPassword,
            'temporary_password_expires_at' => null,
        ]);

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
