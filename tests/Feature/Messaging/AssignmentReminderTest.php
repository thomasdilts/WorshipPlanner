<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\AssignmentResponseStatus;
use App\Enums\AssignmentState;
use App\Enums\FieldUsage;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use App\Models\OutboundMessage;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\AssignmentReminderDispatcher;
use App\Services\AssignmentStateResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Messaging\Support\MessagingAvailability;
use Tests\TestCase;

class AssignmentReminderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{church: Church, assignee: User, task: EventTask}
     */
    private function fixture(array $reminderSettings, string $startsAt): array
    {
        $church = Church::factory()->create(['timezone' => 'Europe/Stockholm']);
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
            'reminders' => $reminderSettings,
        ]);
        app(CurrentChurch::class)->set($church);

        $assignee = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'assignee@example.test',
        ]);

        $event = Event::factory()->create([
            'church_id' => $church->id,
            'name' => 'Sunday Service',
            'starts_at' => $startsAt,
            'ends_at' => Carbon::parse($startsAt)->addHours(2),
        ]);

        $template = TaskTemplate::factory()->create([
            'church_id' => $church->id,
            'person_usage' => FieldUsage::Required,
            'team_usage' => FieldUsage::NotUsed,
        ]);

        $task = EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'assigned_user_id' => $assignee->id,
            'name' => 'Welcome',
        ]);

        EventTaskResponse::factory()->create([
            'church_id' => $church->id,
            'event_task_id' => $task->id,
            'assignee_user_id' => $assignee->id,
            'status' => AssignmentResponseStatus::Confirmed,
            'occurred_at' => now()->subDay(),
        ]);

        return compact('church', 'assignee', 'task');
    }

    public function test_sends_reminder_on_target_local_day_and_is_idempotent(): void
    {
        Carbon::setTestNow('2026-10-29 08:00:00');

        $fixture = $this->fixture([
            'enabled' => true,
            'days_before' => 3,
        ], '2026-11-01 10:00:00');

        $dispatcher = app(AssignmentReminderDispatcher::class);

        $first = $dispatcher->runForChurch($fixture['church']);
        $this->assertSame(1, $first['sent']);
        $this->assertDatabaseHas('outbound_messages', [
            'user_id' => $fixture['assignee']->id,
            'template_key' => 'assignment_reminder',
            'status' => 'sent',
        ]);
        $this->assertSame(
            AssignmentState::Confirmed,
            app(AssignmentStateResolver::class)->for($fixture['task']->fresh(['responses']))
        );

        $second = $dispatcher->runForChurch($fixture['church']);
        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, OutboundMessage::query()->where('template_key', 'assignment_reminder')->count());
        $this->assertSame(
            1,
            EventTaskResponse::query()
                ->where('legacy_source', AssignmentReminderDispatcher::LEGACY_SOURCE)
                ->count()
        );
    }

    public function test_skips_when_disabled_or_wrong_day_or_declined(): void
    {
        Carbon::setTestNow('2026-10-29 08:00:00');

        $disabled = $this->fixture([
            'enabled' => false,
            'days_before' => 3,
        ], '2026-11-01 10:00:00');

        $this->assertSame(0, app(AssignmentReminderDispatcher::class)->runForChurch($disabled['church'])['sent']);

        $wrongDay = $this->fixture([
            'enabled' => true,
            'days_before' => 3,
        ], '2026-11-05 10:00:00');

        $this->assertSame(0, app(AssignmentReminderDispatcher::class)->runForChurch($wrongDay['church'])['sent']);

        $declined = $this->fixture([
            'enabled' => true,
            'days_before' => 3,
        ], '2026-11-01 10:00:00');

        EventTaskResponse::factory()->create([
            'church_id' => $declined['church']->id,
            'event_task_id' => $declined['task']->id,
            'assignee_user_id' => $declined['assignee']->id,
            'status' => AssignmentResponseStatus::Declined,
            'occurred_at' => now(),
        ]);

        $this->assertSame(0, app(AssignmentReminderDispatcher::class)->runForChurch($declined['church'])['sent']);
        $this->assertSame(0, OutboundMessage::query()->where('template_key', 'assignment_reminder')->count());
    }

    public function test_artisan_command_runs_successfully(): void
    {
        Carbon::setTestNow('2026-10-29 08:00:00');

        $this->fixture([
            'enabled' => true,
            'days_before' => 3,
        ], '2026-11-01 10:00:00');

        $this->artisan('assignments:send-reminders')
            ->assertSuccessful();

        $this->assertSame(1, OutboundMessage::query()->where('template_key', 'assignment_reminder')->count());
    }
}
