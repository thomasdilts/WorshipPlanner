<?php

namespace Tests\Feature;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\FieldUsage;
use App\Enums\MessagePreference;
use App\Filament\Resources\Users\UserResource;
use App\Models\Church;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Support\UserAvatar;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_can_open_a_read_only_profile_from_the_eye_icon(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'abilities');
        app(ChurchModuleManager::class)->enable($church, 'messaging');
        $planner = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($planner, 'Event Planner');
        $person = User::factory()->create(['church_id' => $church->id, 'display_name' => 'Anna', 'phone' => '+46700000000', 'message_preference' => MessagePreference::WhatsApp]);
        $ability = TaskTemplate::factory()->create(['church_id' => $church->id, 'name' => 'Preacher', 'person_usage' => FieldUsage::Required]);
        $person->abilityTemplates()->attach($ability);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $this->actingAs($planner);

        $this->get(UserResource::getUrl('view', ['record' => $person]))
            ->assertOk()
            ->assertSee('Anna')
            ->assertSee('+46700000000')
            ->assertSee('Abilities')
            ->assertSee('Preacher')
            ->assertSee('Preferred messaging')
            ->assertSee('WhatsApp')
            ->assertDontSee('type="password"', false);

        $this->assertStringContainsString('View profile for Anna', UserAvatar::html($person)->toHtml());
        $this->assertFalse($planner->can('update', $person));
    }

    public function test_member_cannot_open_another_profile_and_has_no_eye_icon(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        $member = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');
        $person = User::factory()->create(['church_id' => $church->id]);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $this->actingAs($member);

        $this->get(UserResource::getUrl('view', ['record' => $person]))->assertForbidden();
        $this->assertStringNotContainsString('wp-user-profile-link', UserAvatar::html($person)->toHtml());
    }

    public function test_profile_hides_abilities_and_messaging_when_the_modules_are_off(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->disable($church, 'abilities');
        app(ChurchModuleManager::class)->disable($church, 'messaging');
        app(ChurchModuleManager::class)->disable($church, 'messaging_sms');
        app(ChurchModuleManager::class)->disable($church, 'messaging_whatsapp');
        $planner = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($planner, 'Event Planner');
        $person = User::factory()->create(['church_id' => $church->id]);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $this->actingAs($planner);

        $this->get(UserResource::getUrl('view', ['record' => $person]))
            ->assertOk()
            ->assertDontSee('Abilities')
            ->assertDontSee('Preferred messaging');
    }

    public function test_profile_abilities_respect_the_existing_management_permission(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'abilities');
        $viewer = User::factory()->create(['church_id' => $church->id]);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $viewer->givePermissionTo(['users.view', 'abilities.view_own']);
        $person = User::factory()->create(['church_id' => $church->id]);
        $ability = TaskTemplate::factory()->create(['church_id' => $church->id, 'name' => 'Private ability']);
        $person->abilityTemplates()->attach($ability);
        $this->actingAs($viewer);

        $this->get(UserResource::getUrl('view', ['record' => $person]))->assertOk()->assertDontSee('Private ability');
    }

    public function test_planner_cannot_open_a_profile_from_another_church(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        $planner = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($planner, 'Event Planner');
        $outsider = User::factory()->create();
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);
        $this->actingAs($planner);

        $this->get(UserResource::getUrl('view', ['record' => $outsider]))->assertNotFound();
        $this->assertStringNotContainsString('wp-user-profile-link', UserAvatar::html($outsider)->toHtml());
    }
}
