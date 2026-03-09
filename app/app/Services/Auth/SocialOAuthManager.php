<?php

namespace App\Services\Auth;

use App\Contracts\Auth\SocialOAuthProvider;
use App\Services\Auth\Social\QqOAuthProvider;
use App\Services\Auth\Social\WeChatOAuthProvider;
use InvalidArgumentException;

class SocialOAuthManager
{
    public function driver(string $provider): SocialOAuthProvider
    {
        return match ($provider) {
            'wechat' => app(WeChatOAuthProvider::class),
            'qq' => app(QqOAuthProvider::class),
            default => throw new InvalidArgumentException("Unsupported social provider [{$provider}]."),
        };
    }

    public function mode(string $provider): string
    {
        $mode = strtolower((string) config("community.auth.social_providers.{$provider}.mode", 'demo'));

        return in_array($mode, ['demo', 'oauth'], true) ? $mode : 'demo';
    }

    public function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    public function isReadyForOAuth(string $provider): bool
    {
        return $this->mode($provider) === 'oauth' && $this->isConfigured($provider);
    }

    public function presentation(string $provider): array
    {
        $mode = $this->mode($provider);

        if ($mode === 'oauth' && $this->isConfigured($provider)) {
            return [
                'mode' => 'oauth',
                'action_label' => $provider === 'wechat' ? '前往微信扫码登录' : '前往 QQ 扫码登录',
                'helper_text' => $provider === 'wechat'
                    ? '已接入官方微信开放平台，点击后进入微信扫码授权页。'
                    : '已接入 QQ 互联，点击后进入 QQ 官方扫码授权页。',
                'available' => true,
            ];
        }

        if ($mode === 'oauth') {
            return [
                'mode' => 'oauth',
                'action_label' => $provider === 'wechat' ? '微信扫码配置不完整' : 'QQ 扫码配置不完整',
                'helper_text' => '后台已切换到真实 OAuth，但尚未填完 AppID / Secret。',
                'available' => false,
            ];
        }

        return [
            'mode' => 'demo',
            'action_label' => $provider === 'wechat' ? '生成微信演示二维码' : '生成 QQ 演示二维码',
            'helper_text' => '当前为内置演示模式；填完开放平台配置后可切换到真实扫码登录。',
            'available' => true,
        ];
    }
}
