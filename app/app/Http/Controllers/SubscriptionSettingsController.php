<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Support\MailSettingsManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionSettingsController extends Controller
{
    public function edit(Request $request, MailSettingsManager $mailSettingsManager): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $preference = $user->ensureNotificationPreference();

        $viewData = [
            'pageTitle' => '订阅设置',
            'preference' => $preference,
            'channels' => Channel::query()->ordered()->get(),
            'selectedChannelIds' => $user->emailChannelSubscriptions()->pluck('channel_id')->all(),
        ];

        if ($user->isAdmin()) {
            $viewData = array_merge($viewData, [
                'mailSettingForm' => $mailSettingsManager->formData(),
                'mailSettingUsingCustomConfig' => $mailSettingsManager->usingCustomSettings(),
                'mailSettingHasPassword' => $mailSettingsManager->hasStoredPassword(),
            ]);
        }

        return view('settings.subscriptions', $viewData);
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

    public function updateMailSettings(Request $request, MailSettingsManager $mailSettingsManager): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isAdmin(), 403);

        $enabled = $request->boolean('enabled');

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'smtp_scheme' => ['nullable', Rule::in(['tls', 'ssl'])],
            'smtp_host' => [Rule::requiredIf($enabled), 'nullable', 'string', 'max:255'],
            'smtp_port' => [Rule::requiredIf($enabled), 'nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'from_address' => [Rule::requiredIf($enabled), 'nullable', 'email', 'max:255'],
            'from_name' => [Rule::requiredIf($enabled), 'nullable', 'string', 'max:255'],
        ]);

        $mailSettingsManager->save($validated);
        $mailSettingsManager->applyConfiguredSettings();

        return to_route('settings.subscriptions.edit')->with('status', 'SMTP 配置已保存。');
    }
}
