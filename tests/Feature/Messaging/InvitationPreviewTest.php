<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\AssignmentState;
use App\Enums\FieldUsage;
use App\Enums\MessagePreference;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\OutboundMessage;
use App\Models\TaskTemplate;
use App\Models\Team;
use App\Models\User;
use App\Services\AssignmentStateResolver;
use App\Services\InvitationDeliveryPreview;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Messaging\Services\EventMessageService;
use Tests\TestCase;

class InvitationPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_resolves_people_and_team_leaders_with_channels(): void
    {
        $church = Church::factory()->create();
        app(ChurchModuleManager::class)->enable($church, 'messaging');
        app(CurrentChurch::class)->set($church);

        $person = User::factory()->create([
            'church_id' => $church->id,
            'display_name' => 'Pat',
            'message_preference' => MessagePreference::Email,
        ]);
        $leader = User::factory()->create([
            'church_id' => $church->id,
            'display_name' => 'Lee',
            'message_preference' => MessagePreference::Email,
        ]);
        $team = Team::factory()->create(['church_id' => $church->id, 'name' => 'Band']);
        $leader->teams()->attach($team->id, ['is_leader' => true]);

        $event = Event::factory()->create(['church_id' => $church->id]);
        $personTemplate = TaskTemplate::factory()->create([
            'church_id' => $church->id,
            'person_usage' => FieldUsage::Required,
            'team_usage' => FieldUsage::NotUsed,
        ]);
        $teamTemplate = TaskTemplate::factory()->create([
            'church_id' => $church->id,
            'person_usage' => FieldUsage::NotUsed,
            'team_usage' => FieldUsage::Required,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $personTemplate->id,
            'assigned_user_id' => $person->id,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $teamTemplate->id,
            'assigned_team_id' => $team->id,
        ]);

        $rows = app(InvitationDeliveryPreview::class)->resolve(
            $event,
            [$person->id],
            [$team->id],
        );

        $this->assertCount(2, $rows);
        $names = collect($rows)->pluck('name')->all();
        $this->assertContains('Pat', $names);
        $this->assertContains('Lee', $names);
        $this->assertTrue(collect($rows)->contains(
            fn (array $row): bool => $row['name'] === 'Lee' && $row['via_team'] === 'Band'
        ));
    }

    public function test_saving_a_draft_does_not_send_or_invite(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'messaging');
        app(CurrentChurch::class)->set($church);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');
        $assignee = User::factory()->create(['church_id' => $church->id]);

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
        ]);

        $message = app(EventMessageService::class)->create(
            event: $event,
            author: $admin,
            body: 'Please join us',
            subject: 'Invitation',
            recipientUserIds: [$assignee->id],
        )->sole();

        $this->assertSame('draft', $message->status);
        $this->assertNull($message->sent_at);
        $this->assertSame(AssignmentState::Draft, app(AssignmentStateResolver::class)->for($task->fresh()));
        $this->assertDatabaseCount('outbound_messages', 0);
    }

    public function test_sending_marks_assignees_invited_and_records_delivery(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'messaging', [
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
        ]);

        $message = app(EventMessageService::class)->create(
            event: $event,
            author: $admin,
            body: 'Please join us',
            subject: 'Invitation',
            recipientUserIds: [$assignee->id],
            sendNow: true,
        )->sole();

        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame(AssignmentState::Invited, app(AssignmentStateResolver::class)->for($task->fresh()));
        $this->assertDatabaseHas('outbound_messages', [
            'user_id' => $assignee->id,
            'event_message_id' => $message->id,
            'status' => 'sent',
        ]);

        $outboundCount = OutboundMessage::query()->count();
        app(EventMessageService::class)->send($message->fresh(['recipients']));
        $this->assertSame($outboundCount, OutboundMessage::query()->count());
        $this->assertSame(1, $task->responses()->count());
    }

    public function test_each_person_gets_their_own_message_and_retry_sends_only_the_failed_one(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'messaging', [
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
        $ok = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'ok@example.test',
        ]);
        $bad = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'bad@example.test',
            'display_name' => 'No Email',
        ]);
        // Column is NOT NULL; blank still fails EmailChannel::filled().
        $bad->forceFill(['email' => ''])->saveQuietly();

        $event = Event::factory()->create(['church_id' => $church->id]);
        $template = TaskTemplate::factory()->create([
            'church_id' => $church->id,
            'person_usage' => FieldUsage::Required,
            'team_usage' => FieldUsage::NotUsed,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'assigned_user_id' => $ok->id,
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'assigned_user_id' => $bad->id,
        ]);

        $service = app(EventMessageService::class);
        $messages = $service->create(
            event: $event,
            author: $admin,
            body: 'Please join us',
            subject: 'Invitation',
            recipientUserIds: [$ok->id, $bad->id],
            sendNow: true,
        );

        $this->assertCount(2, $messages);

        $okMessage = $messages->first(fn ($message): bool => $message->recipients->first()->is($ok));
        $badMessage = $messages->first(fn ($message): bool => $message->recipients->first()->is($bad));

        $this->assertSame('sent', $okMessage->status);
        $this->assertSame('failed', $badMessage->status);

        $before = OutboundMessage::query()->count();
        $bad->update(['email' => 'fixed@example.test']);
        $service->send($okMessage->fresh(['recipients']));
        $service->send($badMessage->fresh(['recipients']));

        $this->assertSame('sent', $badMessage->fresh()->status);
        $this->assertSame($before + 1, OutboundMessage::query()->count());
    }
}
