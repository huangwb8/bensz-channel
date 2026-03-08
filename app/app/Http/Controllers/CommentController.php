<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use App\Support\CommentMentionNotifier;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(
        Request $request,
        Article $article,
        CommentMentionNotifier $commentMentionNotifier,
        MarkdownRenderer $markdownRenderer,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        abort_unless($request->user() !== null, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => '评论内容不能为空。',
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'markdown_body' => $validated['body'],
            'html_body' => $markdownRenderer->toHtml($validated['body']),
            'is_visible' => true,
        ]);

        $commentMentionNotifier->send($comment);

        $article->update([
            'comment_count' => $article->allComments()->count(),
        ]);

        $staticPageBuilder->buildAll();

        return to_route('articles.show', [$article->channel, $article])->with('status', '评论已发布。');
    }
}
