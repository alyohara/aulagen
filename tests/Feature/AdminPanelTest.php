<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_teachers_cannot_access_admin(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_view_dashboard_and_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/usuarios')->assertOk();
        $this->actingAs($admin)->get('/admin/generaciones')->assertOk();
    }

    public function test_admin_can_change_a_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->student()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), ['role' => 'teacher'])
            ->assertRedirect();

        $this->assertSame('teacher', $user->fresh()->role->value);
    }

    public function test_only_admins_can_change_roles(): void
    {
        $teacher = User::factory()->teacher()->create();
        $user = User::factory()->student()->create();

        $this->actingAs($teacher)
            ->patch(route('admin.users.update', $user), ['role' => 'admin'])
            ->assertForbidden();
    }
}
