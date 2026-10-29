<?php

namespace Tests\Feature;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\FieldUsage;
use App\Filament\Pages\MyTasks;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ViewEvent;
use App\Filament\Resources\Events\RelationManagers\TasksRelationManager;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\TaskTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventTaskViewActionTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected Event $event;

    protected EventTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($this->church, 'bible_verses', [
            'base_url' => 'http://bible.test',
            'api_key' => app(ChurchModuleManager::class)->encryptSecret('test-key'),
            'default_bible' => 'kjv',
        ]);
        Http::fake([
            'http://bible.test/api/bibles' => Http::response(['data' => [['id' => 'kjv', 'name' => 'King James Version']]]),
            'http://bible.test/api/bibles/kjv/passages/*' => Http::response(['data' => ['reference' => 'John 3:16', 'content' => 'For God so loved the world.', 'verses' => []]]),
        ]);

        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'bible_usage' => FieldUsage::Optional,
            'name' => 'Reading',
        ]);

        $this->event = Event::factory()->create(['church_id' => $this->church->id]);
        $this->task = EventTask::factory()->create([
            'event_id' => $this->event->id,
            'task_template_id' => $template->id,
            'name' => 'Reading',
            'bible_passages' => [
                ['bible' => 'kjv', 'ref' => 'JOHN.3.16', 'label' => 'John 3:16'],
            ],
        ]);

        app(CurrentChurch::class)->set($this->church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
    }

    public function test_member_can_view_task_but_not_edit_on_event_view(): void
    {
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->actingAs($member);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $this->event,
            'pageClass' => ViewEvent::class,
        ])
            ->assertTableActionVisible('view', $this->task)
            ->assertTableActionHidden('edit', $this->task)
            ->assertTableActionHidden('delete', $this->task)
            ->assertTableActionHidden('create')
            ->callTableAction('view', $this->task)
            ->assertHasNoTableActionErrors();
    }

    public function test_admin_has_edit_without_a_redundant_view_action_on_service_order(): void
    {
        $admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->actingAs($admin);

        Livewire::test(TasksRelationManager::class, [
            'ownerRecord' => $this->event,
            'pageClass' => EditEvent::class,
        ])
            ->assertTableActionDoesNotExist('view')
            ->assertTableActionVisible('edit', $this->task);
    }

    public function test_assigned_member_can_view_task_from_my_tasks(): void
    {
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->task->update(['assigned_user_id' => $member->id]);

        $this->actingAs($member);

        Livewire::test(MyTasks::class)
            ->assertSuccessful()
            ->assertTableActionVisible('view', $this->task)
            ->callTableAction('view', $this->task)
            ->assertHasNoTableActionErrors();
    }
}
