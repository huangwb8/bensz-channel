<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentMentionedNotification;
use Illuminate\Support\Facades\Notification;

class CommentMentionNotifier
{
    public function send(Comment $comment): void
    {
        $comment->loadMissing(['article.channel', 'user']);

        $recipients = User::query()
            ->whereNotNull('email')
            ->whereKeyNot($comment->user_id)
            ->with('notificationPreference')
            ->get()
            ->filter(fn (User $user) => $user->wantsMentionEmails())
            ->filter(fn (User $user) => $this->wasMentioned($comment->markdown_body, $user->name))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CommentMentionedNotification($comment));
    }

    private function wasMentioned(string $body, string $userName): bool
    {
        return (bool) preg_match('/@'.preg_quote($userName, '/').'(?![\p{L}\p{N}_-])/u', $body);
    }
}
