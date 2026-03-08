<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DevtoolsApiKey;
use App\Models\DevtoolsConnection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DevtoolsController extends Controller
{
    public function index(Request $request): View
    {
        $keys = DevtoolsApiKey::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $connections = DevtoolsConnection::query()
            ->whereIn('key_id', $keys->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (DevtoolsConnection $conn) use ($keys) {
                $conn->key_name = $keys->firstWhere('id', $conn->key_id)?->name ?? '-';

                return $conn;
            });

        return view('admin.devtools.index', [
            'keys' => $keys,
            'connections' => $connections,
            'newKeyValue' => session('new_key_value'),
        ]);
    }

    public function createKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        [, $rawKey] = DevtoolsApiKey::generate($request->user()->id, $validated['name'] ?: 'default');

        return redirect()->route('admin.devtools.index')->with('new_key_value', $rawKey);
    }

    public function revokeKey(Request $request, string $id): RedirectResponse
    {
        $key = DevtoolsApiKey::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->firstOrFail();

        $key->update(['revoked_at' => now()]);

        return back()->with('status', '密钥已撤销。');
    }

    public function terminateConnection(Request $request, string $id): RedirectResponse
    {
        $key_ids = DevtoolsApiKey::query()
            ->where('user_id', $request->user()->id)
            ->pluck('id');

        $connection = DevtoolsConnection::query()
            ->where('id', $id)
            ->whereIn('key_id', $key_ids)
            ->whereNull('terminated_at')
            ->firstOrFail();

        $connection->update(['terminate_requested_at' => now()]);

        return back()->with('status', '已发送终止请求。');
    }
}
