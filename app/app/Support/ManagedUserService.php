<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagedUserService
{
    public function delete(User $user): void
    {
        $this->guardDeletable($user);

        $affectedArticleIds = $this->affectedArticleIdsForCommentRecount($user);

        DB::transaction(function () use ($user, $affectedArticleIds): void {
            $this->deleteRuntimeArtifacts($user);

            $user->delete();

            $this->refreshCommentCounts($affectedArticleIds);
        });
    }

    public function guardDeletable(User $user): void
    {
        if (! $user->isAdmin()) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => '管理员账号不可直接删除，请先完成权限交接。',
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function affectedArticleIdsForCommentRecount(User $user): Collection
    {
        return Comment::query()
            ->where('user_id', $user->id)
            ->whereHas('article', function ($query) use ($user): void {
                $query->where('author_id', '!=', $user->id);
            })
            ->distinct()
            ->pluck('article_id');
    }

    private function deleteRuntimeArtifacts(User $user): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        if (blank($user->email)) {
            return;
        }

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();
    }

    /**
     * @param  Collection<int, int>  $articleIds
     */
    private function refreshCommentCounts(Collection $articleIds): void
    {
        if ($articleIds->isEmpty()) {
            return;
        }

        Article::query()
            ->whereIn('id', $articleIds->all())
            ->get()
            ->each(function (Article $article): void {
                $article->update([
                    'comment_count' => $article->allComments()->count(),
                ]);
            });
    }
}
