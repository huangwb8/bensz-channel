<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $user,
        private readonly string $source,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('有新用户注册：'.$this->user->name)
            ->greeting('你好，管理员！')
            ->line('社区刚刚新增了一位注册用户。')
            ->line('昵称：'.$this->user->name)
            ->line('邮箱：'.($this->user->email ?: '未提供'))
            ->line('手机号：'.($this->user->phone ?: '未提供'))
            ->line('注册方式：'.$this->source)
            ->line('稳定用户 ID：'.(string) $this->user->user_id)
            ->action('进入用户管理', route('admin.users.index'))
            ->line('你可以前往后台继续查看资料、调整角色或安排后续运营动作。');
    }
}
