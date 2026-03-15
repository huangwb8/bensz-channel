<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminCommentPostedNotification extends Notification
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
            ->subject('有新评论：'.$article->title)
            ->greeting('你好，管理员！')
            ->line($commenter->name.' 刚刚在《'.$article->title.'》下发布了一条评论。')
            ->line('频道：'.$article->channel->name)
            ->line('评论摘要：'.Str::limit(trim($this->comment->markdown_body), 120))
            ->action('查看评论', $articleUrl)
            ->line('如需处理该评论，可前往后台“评论管理”执行隐藏或删除。');
    }
}
