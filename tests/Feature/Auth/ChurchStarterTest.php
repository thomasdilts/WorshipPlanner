<?php

namespace Tests\Feature\Auth;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Pages\ChurchStarter;
use App\Models\Church;
use App\Models\Event;
use App\Models\User;
use App\Services\ChurchStarterChecklist;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ChurchStarterTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_marks_incomplete_steps_and_page_is_reachable(): void
    {
        $church = Church::factory()->create(['logo_path' => null]);
        $this->seed(RolesAndPermissionsSeeder::class);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $steps = app(ChurchStarterChecklist::class)->steps($church);
        $byKey = collect($steps)->keyBy('key');

        $this->assertFalse($byKey['identity']['done']);
        $this->assertFalse($byKey['people']['done']);
        $this->assertFalse($byKey['service']['done']);

        $this->actingAs($admin);

        Livewire::test(ChurchStarter::class)
            ->assertSuccessful()
            ->assertSee(__('Invite people'))
            ->assertSee(__('Create a first service'));
    }

    public function test_checklist_completes_when_basics_are_in_place(): void
    {
        $church = Church::factory()->create(['logo_path' => 'churches/1/logo.jpg']);
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'teams');
        app(CurrentChurch::class)->set($church);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');
        User::factory()->create(['church_id' => $church->id]);
        Event::factory()->create(['church_id' => $church->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $this->actingAs($admin);

        $this->assertTrue(app(ChurchStarterChecklist::class)->isComplete($church));
        $this->assertFalse(ChurchStarter::shouldRegisterNavigation());
    }
}
