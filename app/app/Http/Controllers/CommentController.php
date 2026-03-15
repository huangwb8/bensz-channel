<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Support\AdminActivityNotifier;
use App\Support\CommentMentionNotifier;
use App\Support\CommentReplyNotifier;
use App\Support\CommentSubscriptionManager;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function store(
        Request $request,
        Article $article,
        AdminActivityNotifier $adminActivityNotifier,
        CommentMentionNotifier $commentMentionNotifier,
        CommentReplyNotifier $commentReplyNotifier,
        CommentSubscriptionManager $commentSubscriptionManager,
        MarkdownRenderer $markdownRenderer,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        abort_unless($request->user() !== null, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ], [
            'body.required' => '评论内容不能为空。',
        ]);

        $parent = null;

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            $parent = Comment::query()
                ->whereKey($validated['parent_id'])
                ->where('is_visible', true)
                ->first();

            if (! $parent instanceof Comment || $parent->article_id !== $article->id) {
                throw ValidationException::withMessages([
                    'parent_id' => '请选择当前文章中的可见评论进行回复。',
                ]);
            }
        }

        $comment = DB::transaction(function () use (
            $article,
            $commentSubscriptionManager,
            $markdownRenderer,
            $parent,
            $request,
            $validated,
        ): Comment {
            $comment = Comment::query()->create([
                'article_id' => $article->id,
                'user_id' => $request->user()->id,
                'parent_id' => $parent?->id,
                'root_id' => null,
                'markdown_body' => $validated['body'],
                'html_body' => $markdownRenderer->toHtml($validated['body']),
                'is_visible' => true,
            ]);

            $comment->forceFill([
                'root_id' => $parent?->root_id ?: $parent?->id ?: $comment->id,
            ])->save();

            $commentSubscriptionManager->syncAfterCommentCreated($request->user(), $comment, $parent);
            $article->refreshCommentCount();

            return $comment;
        });

        $commentMentionNotifier->send($comment);
        $commentReplyNotifier->send($comment);
        $adminActivityNotifier->sendCommentPosted($comment);
        $article->refreshCommentCount();

        $staticPageBuilder->rebuildAfterComment($article->fresh(['channel']));

        return to_route('articles.show', [$article->channel, $article])->with('status', '评论已发布。');
    }
}
