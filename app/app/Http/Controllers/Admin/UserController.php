<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StaticPageBuilder;
use App\Support\UserAccountManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.users.index', [
            'filters' => $filters,
            'users' => User::query()
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
                ->latest('id')
                ->paginate(12)
                ->withQueryString(),
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'members' => User::query()->where('role', User::ROLE_MEMBER)->count(),
                'recent' => User::query()->where('last_seen_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
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
}
