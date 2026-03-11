<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasExpiredBan()) {
            $user->clearBan();
            $user->save();

            return $next($request);
        }

        if (! $user->isBanned()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $user->activeBanMessage() ?? '该账号已被封禁，请联系管理员。',
            ], 423);
        }

        return redirect()
            ->route('login')
            ->withErrors(['login_method' => $user->activeBanMessage() ?? '该账号已被封禁，请联系管理员。']);
    }
}
