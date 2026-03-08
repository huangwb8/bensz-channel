<?php

namespace App\Services\Auth;

use App\Contracts\Auth\OtpAuthGateway;
use App\Support\OtpLoginBroker;

class LegacyOtpGateway implements OtpAuthGateway
{
    public function __construct(private readonly OtpLoginBroker $broker)
    {
    }

    public function issue(string $channel, string $target): ?string
    {
        return $this->broker->issue($channel, $target);
    }

    public function consume(string $channel, string $target, string $code, ?string $name = null): array
    {
        $user = $this->broker->consume($channel, $target, $code, $name);

        return [
            'id' => (string) $user->getKey(),
            'email' => $user->email,
            'phone' => $user->phone,
            'name' => $user->name,
            'image' => $user->avatar_url,
            'emailVerified' => $user->email_verified_at !== null,
            'phoneVerified' => $user->phone_verified_at !== null,
        ];
    }
}
