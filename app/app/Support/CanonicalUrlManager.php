<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class CanonicalUrlManager
{
    public function apply(): void
    {
        $appUrl = trim((string) config('app.url'));
        $assetUrl = trim((string) config('app.asset_url'));

        if ($appUrl === '') {
            return;
        }

        URL::forceRootUrl(rtrim($appUrl, '/'));
        URL::useAssetOrigin($assetUrl !== '' ? $assetUrl : null);

        $scheme = parse_url($appUrl, PHP_URL_SCHEME);

        if (is_string($scheme) && in_array($scheme, ['http', 'https'], true)) {
            URL::forceScheme($scheme);
        }
    }
}
