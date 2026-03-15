<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentReplyNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CommentReplyNotifier
{
    public function send(Comment $comment): void
    {
        if ($comment->parent_id === null) {
            return;
        }

        $comment->loadMissing(['article.channel', 'user', 'parent']);

        $ancestorIds = $this->ancestorIds($comment);

        if ($ancestorIds->isEmpty()) {
            return;
        }

        $recipients = User::query()
            ->whereNotNull('email')
            ->whereKeyNot($comment->user_id)
            ->with('notificationPreference')
            ->whereHas('commentSubscriptions', function ($query) use ($ancestorIds): void {
                $query
                    ->where('is_active', true)
                    ->whereIn('comment_id', $ancestorIds->all());
            })
            ->get()
            ->filter(fn (User $user) => $user->wantsCommentReplyEmails())
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CommentReplyNotification($comment));
    }

    /**
     * @return Collection<int, int>
     */
    private function ancestorIds(Comment $comment): Collection
    {
        $ids = collect();
        $cursor = $comment->parent;

        while ($cursor instanceof Comment) {
            $ids->push($cursor->id);
            $cursor = $cursor->parent_id === null
                ? null
                : Comment::query()->find($cursor->parent_id);
        }

        return $ids->unique()->values();
    }
}
