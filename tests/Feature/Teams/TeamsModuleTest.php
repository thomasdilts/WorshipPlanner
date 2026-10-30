<?php

namespace Tests\Feature\Teams;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\MessageTemplate;
use App\Models\TaskTemplate;
use App\Models\Team;
use App\Models\TeamType;
use App\Models\User;
use App\Support\SeedsDefaultTeamTypes;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Messaging\Services\EventMessageService;
use Modules\Teams\Services\TeamMessageService;
use Modules\Teams\Services\TeamRosterService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($this->church, 'teams');
        app(ChurchModuleManager::class)->enable($this->church, 'messaging');
        SeedsDefaultTeamTypes::for($this->church);

        $this->admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->admin, 'Church Admin');

        app(CurrentChurch::class)->set($this->church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
    }

    protected function musicType(): TeamType
    {
        return TeamType::query()
            ->where('church_id', $this->church->id)
            ->where('name', 'Music team')
            ->firstOrFail();
    }

    public function test_team_requires_at_least_one_leader(): void
    {
        $team = Team::factory()->create(['church_id' => $this->church->id, 'name' => 'Band']);
        $member = User::factory()->create(['church_id' => $this->church->id]);

        $this->expectException(\InvalidArgumentException::class);

        app(TeamRosterService::class)->syncRoster($team, [$member->id], []);
    }

    public function test_creating_team_assigns_leader_role(): void
    {
        $leader = User::factory()->create(['church_id' => $this->church->id]);
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $this->actingAs($this->admin);

        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Choir',
                'team_type_id' => $this->musicType()->id,
                'member_ids' => [$leader->id, $member->id],
                'leader_ids' => [$leader->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $team = Team::query()->where('name', 'Choir')->first();
        $this->assertNotNull($team);
        $this->assertSame($this->musicType()->id, $team->team_type_id);
        $this->assertTrue($team->isLeader($leader));
        $this->assertTrue($team->isMember($member));
        $this->assertFalse($team->isLeader($member));

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
        $this->assertTrue($leader->fresh()->hasRole(TeamRosterService::LEADER_ROLE));
    }

    public function test_leader_can_add_member_regular_member_cannot_edit(): void
    {
        $leader = User::factory()->create(['church_id' => $this->church->id]);
        $member = User::factory()->create(['church_id' => $this->church->id]);
        $newbie = User::factory()->create(['church_id' => $this->church->id]);

        RolesAndPermissionsSeeder::assignDefaultRoles($leader, 'Team Leader');
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $team = Team::factory()->create(['church_id' => $this->church->id]);
        app(TeamRosterService::class)->syncRoster($team, [$leader->id, $member->id], [$leader->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);

        $this->assertTrue($leader->can('manageMembers', $team));
        $this->assertFalse($member->can('manageMembers', $team));
        $this->assertTrue($member->can('view', $team));
        $this->assertFalse($member->can('update', $team));

        app(TeamRosterService::class)->addMember($team, $newbie);
        $this->assertTrue($team->fresh()->isMember($newbie));
    }

    public function test_event_message_to_team_goes_to_leaders_only(): void
    {
        $leader = User::factory()->create([
            'church_id' => $this->church->id,
            'display_name' => 'Leader',
        ]);
        $member = User::factory()->create([
            'church_id' => $this->church->id,
            'display_name' => 'Member',
        ]);

        $team = Team::factory()->create(['church_id' => $this->church->id]);
        app(TeamRosterService::class)->syncRoster($team, [$leader->id, $member->id], [$leader->id]);

        $event = Event::factory()->create(['church_id' => $this->church->id]);
        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'team_usage' => 'optional',
            'team_type_id' => $team->team_type_id,
        ]);

        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'assigned_team_id' => $team->id,
        ]);

        $message = app(EventMessageService::class)->create(
            event: $event,
            author: $this->admin,
            body: '<p>Hello team</p>',
            subject: 'Rehearsal',
            recipientUserIds: [],
            sendNow: false,
            recipientTeamIds: [$team->id],
        )->sole();

        $recipientIds = $message->recipients()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$leader->id], $recipientIds);
        $this->assertNotContains($member->id, $recipientIds);
    }

    public function test_member_can_message_teammates(): void
    {
        $leader = User::factory()->create(['church_id' => $this->church->id]);
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $team = Team::factory()->create(['church_id' => $this->church->id]);
        app(TeamRosterService::class)->syncRoster($team, [$leader->id, $member->id], [$leader->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
        $this->assertTrue($member->can('message', $team));

        $result = app(TeamMessageService::class)->send(
            team: $team,
            author: $member,
            template: MessageTemplate::query()->create([
                'church_id' => $this->church->id,
                'name' => 'Ping',
                'body' => '<p>Hi</p>',
            ]),
            recipientUserIds: [$leader->id],
        );

        $this->assertSame(1, $result['sent'] + $result['failed']);
    }

    public function test_teams_nav_forbidden_when_module_disabled(): void
    {
        app(ChurchModuleManager::class)->disable($this->church, 'teams');
        $this->admin->unsetRelation('church');

        $this->actingAs($this->admin)
            ->get('/admin/teams')
            ->assertForbidden();
    }
}
