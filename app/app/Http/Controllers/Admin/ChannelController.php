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
        $this->ensureUncategorizedChannel();

        return view('admin.channels.index', [
            'channels' => Channel::query()->ordered()->get(),
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

        return back()->with('status', '频道已更新。');
    }

    public function destroy(Channel $channel, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        if ($this->isReservedChannel($channel)) {
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('channels', 'slug')->ignore($channel?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if ($channel && $this->isReservedChannel($channel)) {
            throw ValidationException::withMessages([
                'channel' => '系统保留频道不可编辑。',
            ]);
        }

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_public'] = true;

        return $validated;
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
