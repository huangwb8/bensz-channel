<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\DevtoolsConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json(['ok' => true, 'serverTime' => now()->toIso8601String()]);
    }

    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clientName' => ['nullable', 'string', 'max:120'],
            'clientVersion' => ['nullable', 'string', 'max:40'],
            'machine' => ['nullable', 'string', 'max:120'],
            'workdir' => ['nullable', 'string', 'max:512'],
        ]);

        $apiKey = $request->attributes->get('devtools_api_key');

        $connection = DevtoolsConnection::query()->create([
            'key_id' => $apiKey->id,
            'client_name' => $validated['clientName'] ?? null,
            'client_version' => $validated['clientVersion'] ?? null,
            'machine' => $validated['machine'] ?? null,
            'workdir' => $validated['workdir'] ?? null,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'connectionId' => $connection->id,
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connectionId' => ['required', 'string'],
            'lastError' => ['nullable', 'string', 'max:2000'],
        ]);

        $apiKey = $request->attributes->get('devtools_api_key');

        $connection = DevtoolsConnection::query()
            ->where('id', $validated['connectionId'])
            ->where('key_id', $apiKey->id)
            ->whereNull('terminated_at')
            ->first();

        if (! $connection) {
            return response()->json(['error' => 'connection_not_found'], 404);
        }

        $connection->update([
            'last_seen_at' => now(),
            'last_error' => $validated['lastError'] ?? null,
        ]);

        $terminate = $connection->terminateRequested();

        return response()->json(['ok' => true, 'terminate' => $terminate]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connectionId' => ['required', 'string'],
        ]);

        $apiKey = $request->attributes->get('devtools_api_key');

        $connection = DevtoolsConnection::query()
            ->where('id', $validated['connectionId'])
            ->where('key_id', $apiKey->id)
            ->whereNull('terminated_at')
            ->first();

        if (! $connection) {
            return response()->json(['error' => 'connection_not_found'], 404);
        }

        $connection->update(['terminated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
