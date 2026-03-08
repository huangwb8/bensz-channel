<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemBootstrapSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => config('community.admin.email'),
        ], [
            'name' => config('community.admin.name'),
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make(config('community.admin.password')),
            'email_verified_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->command?->info('系统管理员账号已完成初始化：'.config('community.admin.email'));
    }
}
