<?php

namespace App\Contracts\Auth;

use App\Data\SocialAuthIdentity;

interface SocialOAuthProvider
{
    public function authorizationUrl(string $state): string;

    public function resolveIdentity(string $code): SocialAuthIdentity;
}
