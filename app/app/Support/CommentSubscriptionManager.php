<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\CommentSubscription;
use App\Models\User;

class CommentSubscriptionManager
{
    public function subscribe(User $user, Comment $comment): CommentSubscription
    {
        return CommentSubscription::query()->updateOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
            ],
            [
                'is_active' => true,
            ],
        );
    }

    public function unsubscribe(User $user, Comment $comment): CommentSubscription
    {
        return CommentSubscription::query()->updateOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
            ],
            [
                'is_active' => false,
            ],
        );
    }

    public function syncAfterCommentCreated(User $author, Comment $comment, ?Comment $parent = null): void
    {
        $this->subscribe($author, $comment);

        if ($parent instanceof Comment) {
            $this->subscribe($author, $parent);
        }
    }
}
