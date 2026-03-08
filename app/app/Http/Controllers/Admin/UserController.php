<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function update(Request $request, User $user, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $this->normalizeInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_MEMBER])],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        if (blank($validated['email'] ?? null) && blank($validated['phone'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => '邮箱和手机号至少保留一个，避免用户失去登录标识。',
                'phone' => '邮箱和手机号至少保留一个，避免用户失去登录标识。',
            ]);
        }

        $this->guardAdminInvariant($user, $validated['role']);

        $user->update($validated);

        $staticPageBuilder->buildAll();

        if ($request->user()?->is($user) && $validated['role'] === User::ROLE_MEMBER) {
            return to_route('home')->with('status', '你的角色已调整为成员，后台权限已同步更新。');
        }

        return to_route('admin.users.index', $request->only(['q', 'role_filter']))->with('status', '用户信息已更新。');
    }

    private function normalizeInput(Request $request): void
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => $this->normalizeOptionalEmail($request->input('email')),
            'phone' => $this->normalizeOptionalPhone($request->input('phone')),
            'bio' => Str::of((string) $request->input('bio'))->trim()->value() ?: null,
        ]);
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

    private function normalizeOptionalEmail(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $email = Str::lower(trim($value));

        return $email !== '' ? $email : null;
    }

    private function normalizeOptionalPhone(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $value) ?: '';

        return $phone !== '' ? $phone : null;
    }
}
