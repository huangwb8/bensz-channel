<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Support\MailSettingsManager;
use App\Support\SmtpConnectivityTester;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

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

        $validated = $this->validateMailSettings($request, $request->boolean('enabled'));

        $mailSettingsManager->save($validated);
        $mailSettingsManager->applyConfiguredSettings();

        return to_route('settings.subscriptions.edit')->with('status', 'SMTP 配置已保存。');
    }

    public function testMailSettings(
        Request $request,
        MailSettingsManager $mailSettingsManager,
        SmtpConnectivityTester $smtpConnectivityTester,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user?->isAdmin(), 403);

        $validated = $this->validateMailSettings($request, true);
        $runtimeConfig = $mailSettingsManager->runtimeConfigFor($validated);
        $recipient = $this->resolveTestRecipient($user->email, $runtimeConfig['from_address']);

        try {
            $smtpConnectivityTester->sendTestMessage($runtimeConfig, $recipient);
        } catch (Throwable $exception) {
            if (! app()->runningUnitTests()) {
                report($exception);
            }

            return to_route('settings.subscriptions.edit')
                ->withInput()
                ->withErrors([
                    'smtp_test' => 'SMTP 测试失败：'.$this->formatSmtpTestError($exception),
                ]);
        }

        return to_route('settings.subscriptions.edit')
            ->with('status', "SMTP 测试成功，已向 {$recipient} 发送测试邮件。请检查收件箱或 Mailpit。");
    }

    private function validateMailSettings(Request $request, bool $requireFields): array
    {
        return $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'smtp_scheme' => ['nullable', Rule::in(['tls', 'ssl', 'smtp', 'smtps'])],
            'smtp_host' => [Rule::requiredIf($requireFields), 'nullable', 'string', 'max:255'],
            'smtp_port' => [Rule::requiredIf($requireFields), 'nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'from_address' => [Rule::requiredIf($requireFields), 'nullable', 'email', 'max:255'],
            'from_name' => [Rule::requiredIf($requireFields), 'nullable', 'string', 'max:255'],
        ]);
    }

    private function resolveTestRecipient(?string $preferredEmail, ?string $fallbackEmail): string
    {
        $recipient = filter_var((string) $preferredEmail, FILTER_VALIDATE_EMAIL)
            ? (string) $preferredEmail
            : (string) $fallbackEmail;

        return Str::lower(trim($recipient));
    }

    private function formatSmtpTestError(Throwable $exception): string
    {
        $message = trim((string) preg_replace('/\s+/', ' ', $exception->getMessage()));

        return $message !== ''
            ? Str::limit($message, 180)
            : '无法建立连接或完成认证，请检查服务器地址、端口、加密方式与账号密码。';
    }
}
