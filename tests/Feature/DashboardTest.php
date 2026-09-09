<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page_for_dashboard_pages(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('projects.index'))->assertRedirect(route('login'));
        $this->get(route('issues.index'))->assertRedirect(route('login'));
        $this->get(route('issues.report'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_authenticated_users_can_visit_the_dashboard_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.index'))
            ->assertOk();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('issues.report'))
            ->assertOk();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('issues.index'))
            ->assertOk();
    }
}
