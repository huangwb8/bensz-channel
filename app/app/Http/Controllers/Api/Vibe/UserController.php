<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'bio', 'avatar_url', 'last_seen_at', 'created_at']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($qb) use ($q): void {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderByRaw("role = 'admin' DESC")->orderByDesc('last_seen_at')->paginate(20);

        return response()->json($users);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_MEMBER])],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        // At least one contact method required
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'error' => 'validation_failed',
                'message' => '邮箱和手机号至少需保留一个。',
            ], 422);
        }

        // Prevent demoting the last admin
        if ($validated['role'] === User::ROLE_MEMBER && $user->isAdmin()) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'error' => 'last_admin',
                    'message' => '不能降级最后一位管理员。',
                ], 422);
            }
        }

        $user->update($validated);

        return response()->json(['user' => $user->fresh(['id', 'name', 'email', 'phone', 'role', 'bio'])]);
    }
}
