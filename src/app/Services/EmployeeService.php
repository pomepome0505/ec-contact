<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmployeeService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getList(): Collection
    {
        return User::orderBy('id') // @phpstan-ignore return.type
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'login_id' => $user->login_id,
                'name' => $user->name,
                'is_active' => $user->is_active,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->format('Y/m/d H:i'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function store(array $validated): User
    {
        return User::create($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(User $user, array $validated): User
    {
        unset($validated['login_id'], $validated['password']);

        $user->update($validated);

        return $user->refresh();
    }

    public function resetPassword(User $user, int $currentUserId): string
    {
        if ($user->id === $currentUserId) {
            throw new \LogicException('自分自身のパスワードはリセットできません。');
        }

        $password = $this->generateTemporaryPassword();

        $user->update([
            'password' => $password,
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        return $password;
    }

    private function generateTemporaryPassword(): string
    {
        $upper = Str::random(1);
        $lower = strtolower(Str::random(1));
        $digit = (string) random_int(0, 9);
        $symbols = '!@#$%^&*';
        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];

        $remaining = Str::random(8);
        $password = str_shuffle($upper.$lower.$digit.$symbol.$remaining);

        return $password;
    }

    public function delete(User $user, int $currentUserId): void
    {
        if ($user->id === $currentUserId) {
            throw new \LogicException('自分自身のアカウントは削除できません。');
        }

        if ($user->inquiries()->exists() || $user->inquiryMessages()->exists()) {
            throw new \LogicException('関連する問い合わせデータがあるため削除できません。');
        }

        $user->delete();
    }

    public function toggleActive(User $user, int $currentUserId): User
    {
        if ($user->id === $currentUserId) {
            throw new \LogicException('自分自身のアカウントは無効化できません。');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $user->refresh();
    }
}
