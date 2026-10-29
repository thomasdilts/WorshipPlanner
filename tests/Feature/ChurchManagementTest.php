<?php

namespace Tests\Feature;

use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Churches\Pages\ManageChurches;
use App\Models\Church;
use App\Models\Role;
use App\Models\User;
use App\Support\SeedsDefaultTeamTypes;
use Database\Seeders\LanguagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ChurchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguagesSeeder::class);
    }

    public function test_platform_admin_can_list_churches(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Church::factory()->create(['name' => 'Alpha Church']);

        $this->actingAs($admin)
            ->get(ChurchResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Alpha Church');
    }

    public function test_church_admin_cannot_manage_churches_list(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $churchAdmin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($churchAdmin, 'Church Admin');

        $this->actingAs($churchAdmin);

        $this->assertFalse(ChurchResource::canViewAny());
        $this->assertFalse(ChurchResource::shouldRegisterNavigation());

        $this->get(ChurchResource::getUrl('index'))->assertForbidden();
    }

    public function test_platform_admin_can_create_church_with_roles_and_team_types(): void
    {
        // Global permissions must exist before per-church role seeding.
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin);

        Livewire::test(ManageChurches::class)
            ->callAction('create', data: [
                'name' => 'New Hope',
                'slug' => 'new-hope',
                'timezone' => 'Europe/Stockholm',
                'locale' => 'sv',
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $church = Church::query()->where('slug', 'new-hope')->first();

        $this->assertNotNull($church);
        $this->assertSame('New Hope', $church->name);
        $this->assertSame('sv', $church->locale);

        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);

        $this->assertTrue(Role::query()->where('name', 'Church Admin')->where('church_id', $church->id)->exists());
        $this->assertTrue(Role::query()->where('name', 'Member')->where('church_id', $church->id)->exists());

        foreach (SeedsDefaultTeamTypes::DEFAULTS as $name) {
            $this->assertDatabaseHas('team_types', [
                'church_id' => $church->id,
                'name' => $name,
            ]);
        }
    }

    public function test_platform_admin_can_update_and_delete_church(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $church = Church::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageChurches::class)
            ->callTableAction('edit', $church, data: [
                'name' => 'Renamed Church',
                'slug' => 'old-name',
                'timezone' => $church->timezone,
                'locale' => $church->locale,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Renamed Church', $church->fresh()->name);

        Livewire::test(ManageChurches::class)
            ->callTableAction('delete', $church);

        $this->assertDatabaseMissing('churches', ['id' => $church->id]);
    }

    public function test_churches_nav_is_in_admin_group(): void
    {
        $this->assertSame(__('Admin'), ChurchResource::getNavigationGroup());
        $this->assertSame(__('Churches'), ChurchResource::getNavigationLabel());
    }
}
