<?php

namespace Database\Seeders;

use App\Models\Channel;
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

        Channel::query()->updateOrCreate([
            'slug' => Channel::SLUG_FEATURED,
        ], [
            'name' => '精华',
            'description' => '站内精选内容与重点沉淀。',
            'accent_color' => '#f59e0b',
            'icon' => '⭐',
            'sort_order' => 0,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        Channel::query()->updateOrCreate([
            'slug' => Channel::SLUG_UNCATEGORIZED,
        ], [
            'name' => '未分类',
            'description' => '系统自动归类的文章将汇总在此。',
            'accent_color' => '#64748b',
            'icon' => '📦',
            'sort_order' => 999,
            'is_public' => true,
            'show_in_top_nav' => false,
        ]);

        $this->command?->info('系统管理员账号已完成初始化：'.config('community.admin.email'));
    }
}
