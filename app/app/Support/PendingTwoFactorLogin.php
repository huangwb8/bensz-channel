<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class PendingTwoFactorLogin
{
    private const SESSION_KEY = 'auth.two_factor.pending';

    public function __construct(private readonly TwoFactorAuthenticationManager $twoFactorAuthenticationManager) {}

    public function start(Request $request, User $user, bool $remember = true): bool
    {
        if (! $this->twoFactorAuthenticationManager->hasEnabledTwoFactor($user)) {
            Auth::login($user, $remember);
            $request->session()->regenerate();

            return false;
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->getKey(),
            'remember' => $remember,
        ]);

        return true;
    }

    public function pendingUser(Request $request): ?User
    {
        $userId = Arr::get($request->session()->get(self::SESSION_KEY, []), 'user_id');

        if (! is_numeric($userId)) {
            return null;
        }

        $user = User::query()->find((int) $userId);

        if ($user !== null) {
            return $user;
        }

        $this->clear($request);

        return null;
    }

    public function complete(Request $request): ?User
    {
        $payload = $request->session()->pull(self::SESSION_KEY, []);
        $userId = Arr::get($payload, 'user_id');
        $remember = (bool) Arr::get($payload, 'remember', true);

        if (! is_numeric($userId)) {
            return null;
        }

        $user = User::query()->find((int) $userId);

        if ($user === null) {
            return null;
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $user;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
