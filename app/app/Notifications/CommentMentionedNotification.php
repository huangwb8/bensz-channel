<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $article = $this->comment->article;
        $commenter = $this->comment->user;
        $articleUrl = route('articles.show', [$article->channel, $article]).'#comments';

        return (new MailMessage)
            ->subject('有人在评论中提到了你：'.$article->title)
            ->greeting('你好，'.$notifiable->name.'！')
            ->line($commenter->name.' 在《'.$article->title.'》的评论中提到了你。')
            ->line('评论内容：'.$this->comment->markdown_body)
            ->action('查看评论', $articleUrl)
            ->line('如不想继续接收此类提醒，可在“订阅设置”中关闭 @ 评论邮件提醒。');
    }
}
