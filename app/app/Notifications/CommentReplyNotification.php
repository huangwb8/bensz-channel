<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommentReplyNotification extends Notification
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
        $replier = $this->comment->user;
        $articleUrl = route('articles.show', [$article->channel, $article]).'#comment-'.$this->comment->id;

        return (new MailMessage)
            ->subject('你关注的评论有了新回复：'.$article->title)
            ->greeting('你好，'.$notifiable->name.'！')
            ->line($replier->name.' 回复了你关注的评论线程。')
            ->line('回复内容：'.Str::limit(trim($this->comment->markdown_body), 180))
            ->action('查看回复', $articleUrl)
            ->line('如果不想继续接收这条评论的后续提醒，可在评论区点击“暂停此评论后续提醒”，或在订阅设置中关闭评论回复邮件。');
    }
}
