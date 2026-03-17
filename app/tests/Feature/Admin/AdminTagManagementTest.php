<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_tag_management(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.tags.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_tag(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.tags.store'), [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'Laravel 相关文章',
            ])
            ->assertRedirect(route('admin.tags.index'));

        $tag = Tag::query()->where('slug', 'laravel')->firstOrFail();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Laravel',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Laravel 12',
                'slug' => 'laravel-12',
                'description' => '最新 Laravel 技术文章',
            ])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Laravel 12',
            'slug' => 'laravel-12',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.tags.destroy', $tag->fresh()))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }
}
