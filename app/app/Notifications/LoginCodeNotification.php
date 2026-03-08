<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expiresInMinutes,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bensz Channel 登录验证码')
            ->greeting('你好')
            ->line('你的登录验证码如下：')
            ->line("**{$this->code}**")
            ->line("验证码 {$this->expiresInMinutes} 分钟内有效，请勿泄露给他人。");
    }
}
