<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    public function index(): View
    {
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
