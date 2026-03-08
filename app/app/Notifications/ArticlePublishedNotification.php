<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArticlePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Article $article,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $articleUrl = route('articles.show', [$this->article->channel, $this->article]);

        return (new MailMessage)
            ->subject('【'.$this->article->channel->name.'】有新文章：'.$this->article->title)
            ->greeting('你好，'.$notifiable->name.'！')
            ->line('你订阅的版块有新文章发布。')
            ->line($this->article->title)
            ->line($this->article->excerpt ?: '点击查看完整内容。')
            ->action('查看文章', $articleUrl)
            ->line('如需调整邮件提醒，可在站内“订阅设置”中随时关闭。');
    }
}
