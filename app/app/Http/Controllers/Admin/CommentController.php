<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Support\CommentModerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->string('q')),
            'visibility' => (string) $request->string('visibility'),
        ];

        $commentsQuery = Comment::query()
            ->with(['user', 'article.channel'])
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $keyword = $filters['q'];

                $query->where(function ($commentQuery) use ($keyword): void {
                    $commentQuery
                        ->where('markdown_body', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('article', fn ($articleQuery) => $articleQuery->where('title', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['visibility'] === 'visible', fn ($query) => $query->where('is_visible', true))
            ->when($filters['visibility'] === 'hidden', fn ($query) => $query->where('is_visible', false))
            ->latest();

        return view('admin.comments.index', [
            'filters' => $filters,
            'comments' => (clone $commentsQuery)->paginate(12)->withQueryString(),
            'stats' => [
                'total' => Comment::query()->count(),
                'visible' => Comment::query()->where('is_visible', true)->count(),
                'hidden' => Comment::query()->where('is_visible', false)->count(),
                'recent' => Comment::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function updateVisibility(
        Request $request,
        Comment $comment,
        CommentModerationService $commentModerationService,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $commentModerationService->updateVisibility($comment, (bool) $validated['is_visible']);

        return to_route('admin.comments.index', $this->filters($request))->with(
            'status',
            (bool) $validated['is_visible'] ? '评论已恢复显示。' : '评论已隐藏。'
        );
    }

    public function destroy(
        Request $request,
        Comment $comment,
        CommentModerationService $commentModerationService,
    ): RedirectResponse {
        $commentModerationService->delete($comment);

        return to_route('admin.comments.index', $this->filters($request))->with('status', '评论已删除。');
    }

    /**
     * @return array{q?: string, visibility?: string}
     */
    private function filters(Request $request): array
    {
        return array_filter([
            'q' => trim((string) $request->string('q')),
            'visibility' => (string) $request->string('visibility'),
        ], fn (string $value): bool => $value !== '');
    }
}
