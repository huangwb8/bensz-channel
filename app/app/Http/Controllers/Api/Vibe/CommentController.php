<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Support\CommentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()->with(['user:id,name', 'article:id,title,slug']);

        if ($request->filled('article_id')) {
            $query->where('article_id', $request->integer('article_id'));
        }

        if ($request->has('visible')) {
            $query->where('is_visible', $request->boolean('visible'));
        }

        $comments = $query->latest()->paginate(50);

        return response()->json($comments);
    }

    public function destroy(Comment $comment, CommentModerationService $commentModerationService): JsonResponse
    {
        $commentModerationService->delete($comment);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Comment $comment, CommentModerationService $commentModerationService): JsonResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $comment = $commentModerationService->updateVisibility($comment, (bool) $validated['is_visible']);

        return response()->json(['comment' => $comment]);
    }
}
