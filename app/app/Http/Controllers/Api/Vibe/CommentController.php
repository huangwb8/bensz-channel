<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Comment;
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

        if ($request->filled('visible')) {
            $query->where('is_visible', (bool) $request->input('visible'));
        }

        $comments = $query->latest()->paginate(50);

        return response()->json($comments);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $comment->update($validated);

        return response()->json(['comment' => $comment->fresh()]);
    }
}
