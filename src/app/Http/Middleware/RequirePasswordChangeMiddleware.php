<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChangeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user && $user->temporary_password_expires_at) {
            if ($user->temporary_password_expires_at->isPast()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login_id' => '一時パスワードの有効期限が切れています。管理者に連絡してください。',
                ]);
            }

            if (! $request->routeIs('password.edit', 'password.update', 'logout')) {
                return redirect()->route('password.edit');
            }
        }

        return $next($request);
    }
}
