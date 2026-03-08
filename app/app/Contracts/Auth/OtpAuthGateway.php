<?php

namespace App\Contracts\Auth;

interface OtpAuthGateway
{
    public function issue(string $channel, string $target): ?string;

    /**
     * @return array<string, mixed>
     */
    public function consume(string $channel, string $target, string $code, ?string $name = null): array;
}
