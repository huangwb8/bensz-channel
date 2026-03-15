<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Support\CommentModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentManagementController extends Controller
{
    public function destroy(
        Request $request,
        Comment $comment,
        CommentModerationService $commentModerationService,
    ): RedirectResponse {
        abort_unless($comment->canBeManagedBy($request->user()), 403);

        $article = $comment->article()->with('channel')->firstOrFail();

        $commentModerationService->delete($comment);

        if ($request->headers->has('referer')) {
            return redirect()->back()->with('status', '评论已删除。');
        }

        return to_route('articles.show', [$article->channel, $article])->with('status', '评论已删除。');
    }
}
