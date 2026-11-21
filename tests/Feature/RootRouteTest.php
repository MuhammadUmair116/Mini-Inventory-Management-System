<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RootRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_redirects_guests_to_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_root_route_redirects_authenticated_users_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_guests_following_the_root_redirect_reach_the_login_page(): void
    {
        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_authenticated_users_following_the_root_redirect_reach_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Index'));
    }
}
