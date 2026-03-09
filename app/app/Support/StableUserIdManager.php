<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StableUserIdManager
{
    private const SYSTEM_ADMIN_USER_ID = 0;

    private const MEMBER_USER_ID_START = 101;

    private const SEQUENCE_KEY = 'stable_user_id';

    public function ensureAssigned(User $user): void
    {
        if ($user->user_id !== null) {
            return;
        }

        $user->user_id = $this->shouldUseSystemAdminUserId($user)
            ? self::SYSTEM_ADMIN_USER_ID
            : $this->nextMemberUserId();
    }

    private function shouldUseSystemAdminUserId(User $user): bool
    {
        if ($user->role !== User::ROLE_ADMIN) {
            return false;
        }

        $email = is_string($user->email) ? Str::lower(trim($user->email)) : null;
        $configuredAdminEmail = Str::lower(trim((string) config('community.admin.email')));

        if ($email === null || $email === '' || $email !== $configuredAdminEmail) {
            return false;
        }

        return ! User::query()->where('user_id', self::SYSTEM_ADMIN_USER_ID)->exists();
    }

    private function nextMemberUserId(): int
    {
        return DB::transaction(function (): int {
            $sequence = DB::table('user_id_sequences')
                ->where('key', self::SEQUENCE_KEY)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                DB::table('user_id_sequences')->insert([
                    'key' => self::SEQUENCE_KEY,
                    'next_value' => self::MEMBER_USER_ID_START + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return self::MEMBER_USER_ID_START;
            }

            $nextValue = max((int) $sequence->next_value, self::MEMBER_USER_ID_START);

            DB::table('user_id_sequences')
                ->where('key', self::SEQUENCE_KEY)
                ->update([
                    'next_value' => $nextValue + 1,
                    'updated_at' => now(),
                ]);

            return $nextValue;
        }, 3);
    }
}
