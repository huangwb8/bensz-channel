<?php

namespace App\Http\Middleware;

use App\Models\DevtoolsApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVibeApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-Devtools-Key');

        if (! $rawKey) {
            return response()->json(['error' => 'missing_api_key'], 401);
        }

        $apiKey = DevtoolsApiKey::findByRaw($rawKey);

        if (! $apiKey) {
            return response()->json(['error' => 'invalid_or_revoked_api_key'], 401);
        }

        // Attach the resolved API key to the request for downstream use.
        $request->attributes->set('devtools_api_key', $apiKey);

        return $next($request);
    }
}
