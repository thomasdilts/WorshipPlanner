<?php

namespace Tests\Feature\Events;

use App\Core\Tenancy\CurrentChurch;
use App\Models\Church;
use App\Models\Event;
use App\Models\TaskTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventAndTaskTemplateAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $admin;

    protected User $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->admin, 'Church Admin');

        $this->planner = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->planner, 'Event Planner');
    }

    public function test_admin_can_manage_task_templates_planner_cannot(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->assertTrue($this->admin->can('task_templates.manage'));
        $this->assertFalse($this->planner->can('task_templates.manage'));
        $this->assertTrue($this->planner->can('task_templates.view'));
    }

    public function test_planner_can_manage_events(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->assertTrue($this->planner->can('events.create'));
        $this->assertTrue($this->planner->can('events.update'));
    }

    public function test_events_and_templates_are_scoped_to_church(): void
    {
        $other = Church::factory()->create();

        $mine = Event::factory()->create(['church_id' => $this->church->id]);
        $theirs = Event::factory()->create(['church_id' => $other->id]);

        app(CurrentChurch::class)->set($this->church);

        $ids = Event::query()->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);

        $template = TaskTemplate::factory()->create(['church_id' => $this->church->id]);
        $otherTemplate = TaskTemplate::factory()->create(['church_id' => $other->id]);

        $templateIds = TaskTemplate::query()->pluck('id')->all();
        $this->assertContains($template->id, $templateIds);
        $this->assertNotContains($otherTemplate->id, $templateIds);
    }
}
