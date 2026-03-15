<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\AdminCommentPostedNotification;
use App\Notifications\AdminNewUserRegisteredNotification;
use Illuminate\Support\Facades\Notification;

class AdminActivityNotifier
{
    public function sendUserRegistered(User $user, string $source): void
    {
        $adminEmail = trim((string) config('community.admin.email'));

        if ($adminEmail === '') {
            return;
        }

        Notification::route('mail', $adminEmail)
            ->notify(new AdminNewUserRegisteredNotification($user, $source));
    }

    public function sendCommentPosted(Comment $comment): void
    {
        $adminEmail = trim((string) config('community.admin.email'));

        if ($adminEmail === '') {
            return;
        }

        $comment->loadMissing(['article.channel', 'user']);

        Notification::route('mail', $adminEmail)
            ->notify(new AdminCommentPostedNotification($comment));
    }
}
