<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\EditProfile;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\LanguagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_and_update_their_profile(): void
    {
        $this->seed(LanguagesSeeder::class);
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'church_id' => $church->id,
            'name' => 'Old Name',
            'display_name' => 'Old',
            'email' => 'old@example.test',
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($user, 'Event Planner');

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertOk();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'New Name',
                'display_name' => 'Newbie',
                'email' => 'old@example.test',
                'language_id' => $user->language_id,
                'message_preference' => $user->message_preference->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('Newbie', $user->display_name);
    }
}
