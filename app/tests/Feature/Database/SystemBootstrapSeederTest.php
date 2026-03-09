<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\SystemBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_bootstrap_seeder_only_creates_admin_baseline(): void
    {
        $this->seed(SystemBootstrapSeeder::class);

        $admin = User::query()->where('email', config('community.admin.email'))->first();

        $this->assertNotNull($admin);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(0, $admin->user_id);
        $this->assertTrue(Hash::check(config('community.admin.password'), (string) $admin->password));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('channels', 2);
        $this->assertDatabaseHas('channels', [
            'slug' => 'featured',
            'name' => '精华',
            'is_public' => true,
        ]);
        $this->assertDatabaseHas('channels', [
            'slug' => 'uncategorized',
            'name' => '未分类',
            'is_public' => true,
        ]);
        $this->assertDatabaseCount('articles', 0);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_system_bootstrap_seeder_is_idempotent(): void
    {
        $this->seed(SystemBootstrapSeeder::class);
        $this->seed(SystemBootstrapSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('channels', 2);
        $this->assertDatabaseHas('users', [
            'email' => config('community.admin.email'),
            'role' => User::ROLE_ADMIN,
            'user_id' => 0,
        ]);
    }
}
