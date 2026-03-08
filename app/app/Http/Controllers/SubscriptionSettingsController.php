<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $preference = $user->ensureNotificationPreference();

        return view('settings.subscriptions', [
            'pageTitle' => '订阅设置',
            'preference' => $preference,
            'channels' => Channel::query()->ordered()->get(),
            'selectedChannelIds' => $user->emailChannelSubscriptions()->pluck('channel_id')->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'email_all_articles' => ['nullable', 'boolean'],
            'email_mentions' => ['nullable', 'boolean'],
            'channel_ids' => ['nullable', 'array'],
            'channel_ids.*' => ['integer', 'exists:channels,id'],
        ]);

        $preference = $user->ensureNotificationPreference();

        $preference->update([
            'email_all_articles' => (bool) ($validated['email_all_articles'] ?? false),
            'email_mentions' => (bool) ($validated['email_mentions'] ?? false),
        ]);

        $channelIds = collect($validated['channel_ids'] ?? [])->map(fn (mixed $id) => (int) $id)->unique()->values()->all();

        $user->emailChannelSubscriptions()->delete();

        if ($channelIds !== []) {
            $user->emailChannelSubscriptions()->createMany(
                collect($channelIds)->map(fn (int $channelId) => ['channel_id' => $channelId])->all(),
            );
        }

        return to_route('settings.subscriptions.edit')->with('status', '订阅设置已保存。');
    }
}
