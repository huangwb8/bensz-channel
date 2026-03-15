<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Support\CommentSubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentSubscriptionController extends Controller
{
    public function store(
        Request $request,
        Comment $comment,
        CommentSubscriptionManager $commentSubscriptionManager,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $commentSubscriptionManager->subscribe($user, $comment);

        return to_route('articles.show', [$comment->article->channel, $comment->article])
            ->with('status', '已恢复这条评论的后续提醒。');
    }

    public function destroy(
        Request $request,
        Comment $comment,
        CommentSubscriptionManager $commentSubscriptionManager,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $commentSubscriptionManager->unsubscribe($user, $comment);

        return to_route('articles.show', [$comment->article->channel, $comment->article])
            ->with('status', '已暂停这条评论的后续提醒。');
    }
}
