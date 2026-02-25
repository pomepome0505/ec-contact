<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Services\PasswordService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwordService,
    ) {}

    public function edit(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return Inertia::render('Password/Edit', [
            'requiresPasswordChange' => $user->temporary_password_expires_at !== null,
        ]);
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->passwordService->changePassword(
            $user,
            $request->validated('password'),
            $request->session()->getId(),
        );

        return redirect()->route('password.edit')->with('success', 'パスワードを変更しました。');
    }
}
