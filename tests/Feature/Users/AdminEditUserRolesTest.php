<?php

namespace Tests\Feature\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminEditUserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_a_users_roles(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $member = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'member@example.test',
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $this->actingAs($admin);

        Livewire::test(EditUser::class, [
            'record' => $member->getRouteKey(),
        ])
            ->assertFormSet([
                'roles' => ['Member'],
            ])
            ->fillForm([
                'roles' => ['Event Planner', 'Member'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $member->refresh();

        $this->assertTrue($member->hasRole('Member'));
        $this->assertTrue($member->hasRole('Event Planner'));
        $this->assertTrue($member->can('events.create'));
    }
}
