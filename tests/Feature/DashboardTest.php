<?php

namespace Tests\Feature;

use App\Models\Ebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_shows_authenticated_users_content_only(): void
    {
        $admin = User::factory()->create();
        $uploader = User::factory()->create(['name' => 'Content Admin']);

        Ebook::factory()->create([
            'user_id' => $uploader->id,
            'title' => 'Community Export Handbook',
            'status' => 'published',
        ]);

        Ebook::factory()->create([
            'user_id' => $admin->id,
            'title' => 'My Export Handbook',
            'status' => 'published',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My Content');
        $response->assertSee('Recent Uploads');
        $response->assertSee('My Export Handbook');
        $response->assertDontSee('Community Export Handbook');
        $response->assertDontSee('Content Admin');
    }
}
