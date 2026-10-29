<?php

namespace Tests\Feature\Teams;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\RelationManagers\MessagesRelationManager;
use App\Models\Church;
use App\Models\OutboundMessage;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamMessagesTabTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $admin;

    protected User $member;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($this->church, 'teams');
        app(ChurchModuleManager::class)->enable($this->church, 'messaging');
        app(CurrentChurch::class)->set($this->church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->admin, 'Church Admin');

        $this->member = User::factory()->create(['church_id' => $this->church->id, 'display_name' => 'Anna']);
        $this->team = Team::factory()->create(['church_id' => $this->church->id, 'name' => 'Band']);
        $this->team->members()->attach($this->member->id, ['is_leader' => true]);

        $this->actingAs($this->admin);
    }

    public function test_lists_only_messages_sent_within_this_team(): void
    {
        $toMember = $this->outbound($this->member, 'sms', 'sent');
        $failedToMember = $this->outbound($this->member, 'email', 'failed', error: 'SMTP login failed');
        $eventInvitation = $this->outbound($this->member, 'email', 'sent', teamId: null);
        $otherTeam = $this->outbound($this->member, 'email', 'sent', teamId: Team::factory()->create(['church_id' => $this->church->id])->id);

        $this->messagesTab()
            ->assertCanSeeTableRecords([$toMember, $failedToMember])
            ->assertCanNotSeeTableRecords([$eventInvitation, $otherTeam])
            ->assertTableColumnFormattedStateSet('channel', 'SMS', $toMember)
            ->assertTableColumnFormattedStateSet('status', __('Failed'), $failedToMember);
    }

    public function test_preview_shows_the_sent_message_and_why_it_failed(): void
    {
        $message = $this->outbound($this->member, 'email', 'failed', error: 'SMTP login failed', body: '<p>Hi Anna, see you Sunday</p>');

        $this->messagesTab()
            ->mountAction(TestAction::make('preview')->table($message))
            ->assertMountedActionModalSee(['Hi Anna, see you Sunday', 'SMTP login failed', __('Email')]);
    }

    public function test_tab_is_hidden_when_no_messaging_module_is_enabled(): void
    {
        app(ChurchModuleManager::class)->disable($this->church, 'messaging');

        $this->assertFalse(MessagesRelationManager::canViewForRecord($this->team, EditTeam::class));
    }

    public function test_a_member_can_message_another_member_from_the_tab(): void
    {
        Mail::fake();
        app(ChurchModuleManager::class)->updateSettings($this->church, 'messaging', [
            'email' => ['host' => 'smtp.test', 'from_address' => 'church@test'],
        ]);

        $writer = User::factory()->create(['church_id' => $this->church->id, 'display_name' => 'Writer']);
        RolesAndPermissionsSeeder::assignDefaultRoles($writer, 'Member');
        $this->team->members()->attach($writer->id, ['is_leader' => false]);

        $this->actingAs($writer);

        $this->assertTrue(MessagesRelationManager::canViewForRecord($this->team, EditTeam::class));

        $this->messagesTab()
            ->assertActionVisible(TestAction::make('messageTeam')->table())
            ->callAction(TestAction::make('messageTeam')->table(), data: [
                'recipient_ids' => [$this->member->id],
                'subject' => 'Practice',
                'body' => 'See you at 18:00',
            ])
            ->assertHasNoActionErrors();

        $message = OutboundMessage::query()->sole();

        $this->assertSame($this->member->id, $message->user_id);
        $this->assertSame($this->team->id, $message->meta['team_id']);
        $this->assertSame($writer->id, $message->meta['from_user_id']);

        $this->messagesTab()->assertCanSeeTableRecords([$message]);
    }

    protected function outbound(User $user, string $channel, string $status, ?string $error = null, string $body = 'Hello', ?int $teamId = -1): OutboundMessage
    {
        return OutboundMessage::query()->create([
            'meta' => $teamId === null ? [] : ['team_id' => $teamId === -1 ? $this->team->id : $teamId, 'from_user_id' => $this->admin->id],
            'church_id' => $this->church->id,
            'user_id' => $user->id,
            'channel' => $channel,
            'subject' => 'Note',
            'body' => $body,
            'status' => $status,
            'error' => $error,
        ]);
    }

    protected function messagesTab(): mixed
    {
        return Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $this->team,
            'pageClass' => EditTeam::class,
        ]);
    }
}
