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
        $this->deleteMany(collect([$user]));
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function deleteMany(Collection $users): int
    {
        $managedUsers = $users
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->values();

        if ($managedUsers->isEmpty()) {
            return 0;
        }

        $this->guardDeletableUsers($managedUsers);

        $userIds = $managedUsers->pluck('id')->values();
        $userEmails = $managedUsers->pluck('email')->filter()->values();
        $affectedArticleIds = $this->affectedArticleIdsForCommentRecount($userIds);

        DB::transaction(function () use ($userIds, $userEmails, $affectedArticleIds): void {
            $this->deleteRuntimeArtifacts($userIds, $userEmails);

            User::query()
                ->whereIn('id', $userIds->all())
                ->delete();

            $this->refreshCommentCounts($affectedArticleIds);
        });

        return $userIds->count();
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
     * @param  Collection<int, User>  $users
     */
    private function guardDeletableUsers(Collection $users): void
    {
        $users->each(fn (User $user) => $this->guardDeletable($user));
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, int>
     */
    private function affectedArticleIdsForCommentRecount(Collection $userIds): Collection
    {
        return Comment::query()
            ->whereIn('user_id', $userIds->all())
            ->whereHas('article', function ($query) use ($userIds): void {
                $query->whereNotIn('author_id', $userIds->all());
            })
            ->distinct()
            ->pluck('article_id');
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @param  Collection<int, string>  $userEmails
     */
    private function deleteRuntimeArtifacts(Collection $userIds, Collection $userEmails): void
    {
        DB::table('sessions')
            ->whereIn('user_id', $userIds->all())
            ->delete();

        if ($userEmails->isEmpty()) {
            return;
        }

        DB::table('password_reset_tokens')
            ->whereIn('email', $userEmails->all())
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
                $article->refreshCommentCount();
            });
    }
}
