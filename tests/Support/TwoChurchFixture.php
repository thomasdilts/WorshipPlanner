<?php

namespace Tests\Support;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Models\Church;
use App\Models\Event;
use App\Models\Song;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

/**
 * Two fully populated churches plus a platform admin, for tenancy and role boundary tests.
 */
final class TwoChurchFixture
{
    public Church $churchA;

    public Church $churchB;

    public User $platformAdmin;

    public User $adminA;

    public User $adminB;

    public User $memberA;

    public Event $eventA;

    public Event $eventB;

    public Team $teamA;

    public Team $teamB;

    public Song $songA;

    public Song $songB;

    public static function create(TestCase $test): self
    {
        $fixture = new self;

        $fixture->platformAdmin = User::factory()->platformAdmin()->create();

        $fixture->churchA = Church::factory()->create(['name' => 'Alpha Church']);
        $fixture->churchB = Church::factory()->create(['name' => 'Beta Church']);

        $test->seed(RolesAndPermissionsSeeder::class);

        foreach ([$fixture->churchA, $fixture->churchB] as $church) {
            app(ChurchModuleManager::class)->enable($church, 'teams');
            app(ChurchModuleManager::class)->enable($church, 'song_list');
        }

        $fixture->adminA = self::userWithRole($fixture->churchA, 'Church Admin', 'admin-a@example.test');
        $fixture->adminB = self::userWithRole($fixture->churchB, 'Church Admin', 'admin-b@example.test');
        $fixture->memberA = self::userWithRole($fixture->churchA, 'Member', 'member-a@example.test');

        $fixture->eventA = Event::factory()->create(['church_id' => $fixture->churchA->id, 'name' => 'Alpha Sunday']);
        $fixture->eventB = Event::factory()->create(['church_id' => $fixture->churchB->id, 'name' => 'Beta Sunday']);
        $fixture->teamA = Team::factory()->create(['church_id' => $fixture->churchA->id, 'name' => 'Alpha Band']);
        $fixture->teamB = Team::factory()->create(['church_id' => $fixture->churchB->id, 'name' => 'Beta Band']);
        $fixture->songA = Song::factory()->create(['church_id' => $fixture->churchA->id, 'name' => 'Alpha Anthem']);
        $fixture->songB = Song::factory()->create(['church_id' => $fixture->churchB->id, 'name' => 'Beta Ballad']);

        app(CurrentChurch::class)->forget();

        return $fixture;
    }

    private static function userWithRole(Church $church, string $role, string $email): User
    {
        $user = User::factory()->create(['church_id' => $church->id, 'email' => $email]);
        RolesAndPermissionsSeeder::assignDefaultRoles($user, $role);

        return $user;
    }
}
