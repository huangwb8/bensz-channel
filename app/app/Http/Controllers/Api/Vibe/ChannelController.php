<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Support\StaticPageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    public function index(): JsonResponse
    {
        $channels = Channel::query()->ordered()->get(['id', 'name', 'slug', 'description', 'icon', 'accent_color', 'sort_order', 'is_public', 'created_at', 'updated_at']);

        return response()->json(['channels' => $channels]);
    }

    public function store(Request $request, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $validated = $this->validateChannel($request);

        $channel = Channel::query()->create($validated);

        $staticPageBuilder->buildAll();

        return response()->json(['channel' => $channel], 201);
    }

    public function update(Request $request, Channel $channel, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $validated = $this->validateChannel($request, $channel);

        $channel->update($validated);

        $staticPageBuilder->buildAll();

        return response()->json(['channel' => $channel->fresh()]);
    }

    public function destroy(Channel $channel, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $channel->delete();

        $staticPageBuilder->buildAll();

        return response()->json(['ok' => true]);
    }

    private function validateChannel(Request $request, ?Channel $channel = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('channels', 'slug')->ignore($channel?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_public'] = true;

        return $validated;
    }
}
