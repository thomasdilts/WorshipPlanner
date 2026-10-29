<?php

namespace Tests\Feature\Events;

use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCreateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_church_admin_can_open_event_create_page(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $this->actingAs($admin)
            ->get('/admin/events/create')
            ->assertOk();
    }

    public function test_platform_admin_cannot_create_church_events(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->get('/admin/events/create')
            ->assertForbidden();
    }

    public function test_event_planner_can_open_event_create_page(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $planner = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($planner, 'Event Planner');

        $this->actingAs($planner)
            ->get('/admin/events/create')
            ->assertOk();
    }
}
