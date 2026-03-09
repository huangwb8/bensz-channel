<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Models\ChannelEmailSubscription;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChannelController extends Controller
{
    public function index(): View
    {
        $this->ensureSystemChannels();

        return view('admin.channels.index', [
            'channels' => Channel::query()
                ->ordered()
                ->get(),
        ]);
    }

    public function store(Request $request, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $validated = $this->validateChannel($request);

        Channel::query()->create($validated);

        $staticPageBuilder->buildAll();

        return back()->with('status', '频道已创建。');
    }

    public function update(Request $request, Channel $channel, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $validated = $this->validateChannel($request, $channel);

        $channel->update($validated);

        $staticPageBuilder->buildAll();

        return back()->with('status', $channel->isReserved() ? '系统频道设置已更新。' : '频道已更新。');
    }

    public function destroy(Channel $channel, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        if ($channel->isReserved()) {
            throw ValidationException::withMessages([
                'channel' => '系统保留频道不可删除。',
            ]);
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

        return back()->with('status', '频道已删除，原有文章已归入未分类。');
    }

    private function validateChannel(Request $request, ?Channel $channel = null): array
    {
        if ($channel?->isReserved()) {
            return [
                'show_in_top_nav' => $request->boolean('show_in_top_nav'),
            ];
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('channels', 'slug')->ignore($channel?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'show_in_top_nav' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_public'] = true;
        $validated['show_in_top_nav'] = $request->boolean('show_in_top_nav');

        return $validated;
    }

    private function ensureSystemChannels(): void
    {
        $this->ensureFeaturedChannel();
        $this->ensureUncategorizedChannel();
    }

    private function ensureFeaturedChannel(): Channel
    {
        $channel = Channel::query()->where('slug', Channel::SLUG_FEATURED)->first();

        if ($channel instanceof Channel) {
            if ($channel->show_in_top_nav === null) {
                $channel->forceFill(['show_in_top_nav' => true])->save();
            }

            return $channel;
        }

        return Channel::query()->create([
            'name' => '精华',
            'slug' => Channel::SLUG_FEATURED,
            'description' => '站内精选内容与重点沉淀。',
            'accent_color' => '#f59e0b',
            'icon' => '⭐',
            'sort_order' => 0,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
    }

    private function ensureUncategorizedChannel(): Channel
    {
        $channel = Channel::query()->where('slug', Channel::SLUG_UNCATEGORIZED)->first();

        if ($channel instanceof Channel) {
            if ($channel->show_in_top_nav === null) {
                $channel->forceFill(['show_in_top_nav' => false])->save();
            }

            return $channel;
        }

        return Channel::query()->create([
            'name' => '未分类',
            'slug' => Channel::SLUG_UNCATEGORIZED,
            'description' => '系统自动归类的文章将汇总在此。',
            'accent_color' => '#64748b',
            'icon' => '📦',
            'sort_order' => 999,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);
    }
}
