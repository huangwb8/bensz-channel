<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management_routes(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_member_profile_and_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create([
            'name' => '原始成员',
            'email' => 'member@example.com',
            'phone' => '13800000000',
            'role' => User::ROLE_MEMBER,
            'bio' => 'old bio',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'name' => '运营负责人',
                'email' => 'ops@example.com',
                'phone' => '13900000000',
                'role' => User::ROLE_ADMIN,
                'bio' => '负责社区运营与用户支持',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => '运营负责人',
            'email' => 'ops@example.com',
            'phone' => '13900000000',
            'role' => User::ROLE_ADMIN,
            'bio' => '负责社区运营与用户支持',
        ]);
    }

    public function test_last_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => User::ROLE_MEMBER,
                'bio' => $admin->bio,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
