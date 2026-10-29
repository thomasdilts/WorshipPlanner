<?php

namespace Tests\Feature\Events;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\AssignmentResponseStatus;
use App\Enums\EventPlanState;
use App\Enums\FieldUsage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\EventPlanStateResolver;
use App\Services\MaterialAssignmentChange;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Messaging\Services\EventMessageService;
use Modules\Messaging\Support\MessagingAvailability;
use Tests\TestCase;

class EventPlanStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_starts_draft_becomes_shared_on_send_and_changed_on_material_edit(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, MessagingAvailability::MODULE_EMAIL, [
            'email' => [
                'host' => 'smtp.test',
                'port' => 587,
                'username' => 'church@test',
                'password' => 'secret',
                'encryption' => 'tls',
                'from_address' => 'church@test',
                'from_name' => 'Church',
            ],
        ]);
        app(CurrentChurch::class)->set($church);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');
        $assignee = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'assignee@example.test',
        ]);

        $event = Event::factory()->create(['church_id' => $church->id]);
        $template = TaskTemplate::factory()->create([
            'church_id' => $church->id,
            'person_usage' => FieldUsage::Required,
            'team_usage' => FieldUsage::NotUsed,
        ]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'assigned_user_id' => $assignee->id,
            'duration' => 10,
            'name' => 'Welcome',
        ]);

        $resolver = app(EventPlanStateResolver::class);
        $this->assertSame(EventPlanState::Draft, $resolver->for($event));

        app(EventMessageService::class)->create(
            event: $event,
            author: $admin,
            body: 'Please join us',
            subject: 'Invitation',
            recipientUserIds: [$assignee->id],
            sendNow: true,
        );

        $event->refresh();
        $this->assertSame(EventPlanState::Shared, $resolver->for($event));
        $this->assertNotNull($event->shared_at);

        EventTaskResponse::factory()->create([
            'church_id' => $church->id,
            'event_task_id' => $task->id,
            'assignee_user_id' => $assignee->id,
            'status' => AssignmentResponseStatus::Confirmed,
            'occurred_at' => now(),
        ]);

        $before = app(MaterialAssignmentChange::class)->snapshot($event->fresh(['tasks.template', 'tasks.responses']));
        $task->update(['duration' => 25]);
        app(MaterialAssignmentChange::class)->applyAfterRecalculate(
            $event->fresh(['tasks.template', 'tasks.responses', 'tasks.event']),
            $before,
        );

        $event->refresh();
        $this->assertSame(EventPlanState::ChangedSinceShared, $resolver->for($event));
        $this->assertNotNull($event->plan_changed_at);
    }

    public function test_draft_material_edit_does_not_set_changed_marker(): void
    {
        $church = Church::factory()->create();
        $event = Event::factory()->create(['church_id' => $church->id]);
        $template = TaskTemplate::factory()->create(['church_id' => $church->id]);
        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'duration' => 10,
        ]);

        $before = app(MaterialAssignmentChange::class)->snapshot($event->fresh(['tasks.template', 'tasks.responses']));
        $task->update(['duration' => 20]);
        app(MaterialAssignmentChange::class)->applyAfterRecalculate(
            $event->fresh(['tasks.template', 'tasks.responses', 'tasks.event']),
            $before,
        );

        $event->refresh();
        $this->assertSame(EventPlanState::Draft, app(EventPlanStateResolver::class)->for($event));
        $this->assertNull($event->plan_changed_at);
    }
}
