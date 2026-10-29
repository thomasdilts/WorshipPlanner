<?php

namespace Tests\Feature\Navigation;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\LandingDestinationKey;
use App\Filament\Navigation\LandingDestination;
use App\Filament\Pages\MyTasks;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LandingPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($this->church, 'teams');
        app(CurrentChurch::class)->set($this->church);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($user, $role);

        return $user;
    }

    public function test_a_planner_can_remember_my_schedule_as_the_start_page(): void
    {
        $planner = $this->userWithRole('Event Planner');
        $planner->update(['preferred_landing' => LandingDestinationKey::MySchedule->value]);

        $this->actingAs($planner);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame(MyTasks::getUrl(), app(LandingDestination::class)->url());

        $this->get('/admin')->assertRedirect(MyTasks::getUrl());
    }

    public function test_an_unauthorized_preference_falls_back_to_the_role_default(): void
    {
        $member = $this->userWithRole('Member');
        $member->update(['preferred_landing' => LandingDestinationKey::TasksByEvent->value]);

        $this->actingAs($member);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame(MyTasks::getUrl(), app(LandingDestination::class)->url());
        $this->assertArrayNotHasKey(
            LandingDestinationKey::TasksByEvent->value,
            app(LandingDestination::class)->optionsFor($member),
        );
    }

    public function test_an_existing_services_preference_still_lands_on_my_schedule(): void
    {
        $planner = $this->userWithRole('Church Admin');
        $planner->update(['preferred_landing' => LandingDestinationKey::Services->value]);

        $this->actingAs($planner);

        $this->assertSame(MyTasks::getUrl(), app(LandingDestination::class)->url());
    }

    public function test_a_blank_preference_lands_a_planner_on_my_schedule(): void
    {
        $planner = $this->userWithRole('Event Planner');

        $this->actingAs($planner);

        $this->assertSame(
            MyTasks::getUrl(),
            app(LandingDestination::class)->url(),
        );
    }
}
