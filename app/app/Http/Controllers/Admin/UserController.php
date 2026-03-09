<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Support\ManagedUserService;
use App\Support\StaticPageBuilder;
use App\Support\UserAccountManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->string('q')),
            'role' => (string) $request->string('role_filter'),
        ];

        $userQuery = User::query()
            ->withCount(['articles', 'comments'])
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $keyword = $filters['q'];

                $query->where(function ($userQuery) use ($keyword): void {
                    $userQuery
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");

                    if (ctype_digit($keyword)) {
                        $userQuery->orWhere('user_id', (int) $keyword);
                    }
                });
            })
            ->when(in_array($filters['role'], [User::ROLE_ADMIN, User::ROLE_MEMBER], true), function ($query) use ($filters): void {
                $query->where('role', $filters['role']);
            })
            ->orderByRaw('case when role = ? then 0 else 1 end', [User::ROLE_ADMIN])
            ->latest('last_seen_at')
            ->latest('id');

        return view('admin.users.index', [
            'filters' => $filters,
            'users' => (clone $userQuery)
                ->paginate(12)
                ->withQueryString(),
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'members' => User::query()->where('role', User::ROLE_MEMBER)->count(),
                'recent' => User::query()->where('last_seen_at', '>=', now()->subDays(7))->count(),
            ],
            'dashboard' => $this->buildDashboard(),
        ]);
    }

    public function bulkDestroy(
        Request $request,
        ManagedUserService $managedUserService,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $selectedUserIds = collect($request->input('selected_user_ids', []))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($selectedUserIds->isEmpty()) {
            return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', '请先选择要删除的普通用户。');
        }

        $selectedUsers = User::query()
            ->whereIn('id', $selectedUserIds->all())
            ->get();

        $deletableUsers = $selectedUsers
            ->filter(fn (User $user): bool => ! $user->isAdmin())
            ->values();

        if ($deletableUsers->isEmpty()) {
            return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', '未删除任何用户：管理员账号会自动跳过。');
        }

        $deletedCount = $managedUserService->deleteMany($deletableUsers);
        $skippedAdminCount = $selectedUsers->count() - $deletedCount;

        $staticPageBuilder->buildAll();

        $message = "已删除 {$deletedCount} 位普通用户。";

        if ($skippedAdminCount > 0) {
            $message .= "已自动跳过 {$skippedAdminCount} 位管理员账号。";
        }

        return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', $message);
    }

    public function update(
        Request $request,
        User $user,
        StaticPageBuilder $staticPageBuilder,
        UserAccountManager $userAccountManager,
    ): RedirectResponse {
        $request->merge($userAccountManager->normalizeProfileInput($request->only([
            'name',
            'email',
            'phone',
            'avatar_url',
            'bio',
        ])));

        $validated = $request->validate([
            ...$userAccountManager->profileValidationRules($user),
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_MEMBER])],
        ]);

        $userAccountManager->assertHasLoginIdentifier($validated);

        $this->guardAdminInvariant($user, $validated['role']);

        $userAccountManager->fillProfile($user, $validated);
        $user->role = $validated['role'];
        $user->save();

        $staticPageBuilder->buildAll();

        if ($request->user()?->is($user) && $validated['role'] === User::ROLE_MEMBER) {
            return to_route('home')->with('status', '你的角色已调整为成员，后台权限已同步更新。');
        }

        return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', '用户信息已更新。');
    }

    public function destroy(
        Request $request,
        User $user,
        ManagedUserService $managedUserService,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $managedUserService->delete($user);

        $staticPageBuilder->buildAll();

        return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', '普通用户已删除。');
    }

    private function guardAdminInvariant(User $user, string $nextRole): void
    {
        if (! $user->isAdmin() || $nextRole === User::ROLE_ADMIN) {
            return;
        }

        $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();

        if ($adminCount > 1) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => '至少需要保留 1 位管理员，不能降级最后一位管理员。',
        ]);
    }

    /**
     * @return array{
     *     cards: array<int, array{label: string, value: int, helper: string}>,
     *     chart_max: int,
     *     series: array<int, array{label: string, full_label: string, login: int, comments: int, articles: int}>
     * }
     */
    private function buildDashboard(): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $offset) => now()->subDays($offset)->startOfDay())
            ->values();

        $startDate = $days->first();

        $loginCounts = User::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $startDate)
            ->selectRaw('date(last_seen_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $commentCounts = Comment::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('date(created_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $articleCounts = Article::query()
            ->published()
            ->where('published_at', '>=', $startDate)
            ->selectRaw('date(published_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $series = $days
            ->map(function ($day) use ($loginCounts, $commentCounts, $articleCounts): array {
                $dateKey = $day->toDateString();

                return [
                    'label' => $day->format('m/d'),
                    'full_label' => $day->format('m月d日'),
                    'login' => (int) ($loginCounts[$dateKey] ?? 0),
                    'comments' => (int) ($commentCounts[$dateKey] ?? 0),
                    'articles' => (int) ($articleCounts[$dateKey] ?? 0),
                ];
            })
            ->values();

        $chartMax = max(
            1,
            (int) $series->reduce(function (int $carry, array $item): int {
                return max($carry, $item['login'], $item['comments'], $item['articles']);
            }, 0)
        );

        return [
            'cards' => [
                [
                    'label' => '当前在线',
                    'value' => DB::table('sessions')
                        ->whereNotNull('user_id')
                        ->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
                        ->distinct()
                        ->count('user_id'),
                    'helper' => '15 分钟内存在会话',
                ],
                [
                    'label' => '7 日登录 / 活跃',
                    'value' => $series->sum('login'),
                    'helper' => '按最后活跃时间统计',
                ],
                [
                    'label' => '7 日评论',
                    'value' => $series->sum('comments'),
                    'helper' => '最近 7 天新增评论',
                ],
                [
                    'label' => '7 日发文',
                    'value' => $series->sum('articles'),
                    'helper' => '最近 7 天已发布文章',
                ],
            ],
            'chart_max' => $chartMax,
            'series' => $series->all(),
        ];
    }
}
