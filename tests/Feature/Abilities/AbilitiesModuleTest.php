<?php

namespace Tests\Feature\Abilities;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\FieldUsage;
use App\Filament\Pages\EditProfile;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Church;
use App\Models\Event;
use App\Models\TaskTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Abilities\Services\UserAbilities;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AbilitiesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($this->church, 'abilities');

        $this->admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->admin, 'Church Admin');

        app(CurrentChurch::class)->set($this->church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
    }

    public function test_person_options_only_include_users_with_ability_when_module_on(): void
    {
        $worship = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'name' => 'Worship leader',
            'person_usage' => FieldUsage::Required,
        ]);
        $sound = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'name' => 'Sound tech',
            'person_usage' => FieldUsage::Optional,
        ]);

        $able = User::factory()->create(['church_id' => $this->church->id, 'display_name' => 'Able User']);
        $other = User::factory()->create(['church_id' => $this->church->id, 'display_name' => 'Other User']);

        app(UserAbilities::class)->sync($able, [$worship->id]);

        $event = Event::factory()->create(['church_id' => $this->church->id]);

        $options = app(UserAbilities::class)->assignablePersonOptions($event->church, $worship->id);

        $this->assertArrayHasKey($able->id, $options);
        $this->assertArrayNotHasKey($other->id, $options);

        $soundOptions = app(UserAbilities::class)->assignablePersonOptions($event->church, $sound->id);
        $this->assertArrayNotHasKey($able->id, $soundOptions);
        $this->assertArrayNotHasKey($other->id, $soundOptions);
    }

    public function test_person_options_include_all_users_when_module_off(): void
    {
        app(ChurchModuleManager::class)->disable($this->church, 'abilities');

        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'person_usage' => FieldUsage::Required,
        ]);
        $a = User::factory()->create(['church_id' => $this->church->id]);
        $b = User::factory()->create(['church_id' => $this->church->id]);

        $event = Event::factory()->create(['church_id' => $this->church->id]);
        $options = app(UserAbilities::class)->assignablePersonOptions($event->church, $template->id);

        $this->assertArrayHasKey($a->id, $options);
        $this->assertArrayHasKey($b->id, $options);
        $this->assertArrayHasKey($this->admin->id, $options);
    }

    public function test_keeps_current_assignee_even_without_ability(): void
    {
        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'person_usage' => FieldUsage::Required,
        ]);
        $assignee = User::factory()->create(['church_id' => $this->church->id]);

        $event = Event::factory()->create(['church_id' => $this->church->id]);
        $options = app(UserAbilities::class)->assignablePersonOptions(
            $event->church,
            $template->id,
            $assignee->id,
        );

        $this->assertArrayHasKey($assignee->id, $options);
    }

    public function test_admin_can_set_user_abilities(): void
    {
        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'person_usage' => FieldUsage::Required,
            'name' => 'Preacher',
        ]);
        $ignored = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'person_usage' => FieldUsage::NotUsed,
            'name' => 'No person',
        ]);
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $member->getKey()])
            ->fillForm([
                'ability_template_ids' => [$template->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $synced = $member->fresh()->abilityTemplates()->pluck('task_templates.id')->all();
        $this->assertSame([$template->id], array_map('intval', $synced));

        app(UserAbilities::class)->sync($member, [$template->id, $ignored->id]);
        $this->assertSame(
            [$template->id],
            array_map('intval', $member->fresh()->abilityTemplates()->pluck('task_templates.id')->all())
        );
    }

    public function test_member_can_edit_own_abilities_on_profile(): void
    {
        $template = TaskTemplate::factory()->create([
            'church_id' => $this->church->id,
            'person_usage' => FieldUsage::Optional,
        ]);
        $member = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($member, 'Member');

        $this->actingAs($member);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'ability_template_ids' => [$template->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(
            $member->fresh()->abilityTemplates()->where('task_templates.id', $template->id)->exists()
        );
    }

    public function test_abilities_section_hidden_when_module_off(): void
    {
        app(ChurchModuleManager::class)->disable($this->church, 'abilities');
        $this->admin->unsetRelation('church');

        $member = User::factory()->create(['church_id' => $this->church->id]);

        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $member->getKey()])
            ->assertFormFieldIsHidden('ability_template_ids');
    }
}
