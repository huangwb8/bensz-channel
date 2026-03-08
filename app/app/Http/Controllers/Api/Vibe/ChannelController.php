<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Models\ChannelEmailSubscription;
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

    public function update(Request $request, string $channel, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $channel = $this->resolveChannel($channel);

        if ($this->isReservedChannel($channel)) {
            return response()->json([
                'message' => '系统保留频道不可编辑。',
            ], 422);
        }

        $validated = $this->validateChannel($request, $channel);

        $channel->update($validated);

        $staticPageBuilder->buildAll();

        return response()->json(['channel' => $channel->fresh()]);
    }

    public function destroy(string $channel, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $channel = $this->resolveChannel($channel);

        if ($this->isReservedChannel($channel)) {
            return response()->json([
                'message' => '系统保留频道不可删除。',
            ], 422);
        }

        $uncategorized = $this->ensureUncategorizedChannel();

        Article::query()
            ->where('channel_id', $channel->id)
            ->update(['channel_id' => $uncategorized->id]);

        ChannelEmailSubscription::query()
            ->where('channel_id', $channel->id)
            ->delete();

        $channel->delete();

        $staticPageBuilder->buildAll();

        return response()->json(['ok' => true]);
    }

    private function validateChannel(Request $request, ?Channel $channel = null): array
    {
        $isUpdate = $channel !== null;

        $validated = $request->validate([
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:40'],
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('channels', 'slug')->ignore($channel?->id)],
            'description' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:500'],
            'accent_color' => [$isUpdate ? 'sometimes' : 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:8'],
            'sort_order' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if (array_key_exists('slug', $validated) || array_key_exists('name', $validated) || ! $isUpdate) {
            $validated['slug'] = $this->makeChannelSlug(
                $validated['slug'] ?? $validated['name'] ?? null,
                $channel,
            );
        }

        if (array_key_exists('sort_order', $validated)) {
            $validated['sort_order'] = (int) $validated['sort_order'];
        } elseif (! $isUpdate) {
            $validated['sort_order'] = 0;
        }

        if (! $isUpdate) {
            $validated['is_public'] = true;
        }

        return $validated;
    }

    private function resolveChannel(string $identifier): Channel
    {
        return Channel::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    private function makeChannelSlug(?string $source, ?Channel $channel = null): string
    {
        $slug = Str::slug((string) $source);

        if ($slug !== '') {
            return $slug;
        }

        if ($channel instanceof Channel && $channel->slug !== '') {
            return $channel->slug;
        }

        return 'channel-' . Str::lower(Str::random(8));
    }

    private function ensureUncategorizedChannel(): Channel
    {
        $channel = Channel::query()->where('slug', 'uncategorized')->first();

        if ($channel instanceof Channel) {
            return $channel;
        }

        return Channel::query()->create([
            'name' => '未分类',
            'slug' => 'uncategorized',
            'description' => '系统自动归类的文章将汇总在此。',
            'accent_color' => '#64748b',
            'icon' => '📦',
            'sort_order' => 999,
            'is_public' => true,
        ]);
    }

    private function isReservedChannel(Channel $channel): bool
    {
        return in_array($channel->slug, ['all', 'uncategorized'], true)
            || in_array($channel->name, ['全部', '未分类'], true);
    }
}
