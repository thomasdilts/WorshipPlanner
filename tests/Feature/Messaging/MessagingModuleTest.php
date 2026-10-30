<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Enums\MessagePreference;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\MessagingSettings;
use App\Models\Church;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_messaging_and_open_settings(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $this->actingAs($admin)
            ->get('/admin/manage-modules')
            ->assertOk();

        app(ChurchModuleManager::class)->enable($church, 'messaging');

        $this->actingAs($admin)
            ->get('/admin/messaging-settings')
            ->assertOk();
    }

    public function test_admin_can_save_email_settings_on_church_module(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'messaging');

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $this->actingAs($admin);

        Livewire::test(MessagingSettings::class)
            ->fillForm([
                'host' => 'smtp.example.test',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'church',
                'password' => 'secret',
                'from_address' => 'worship@example.test',
                'from_name' => 'Example Church',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(ChurchModuleManager::class)->settings($church->fresh(), 'messaging');

        $this->assertSame('smtp.example.test', $settings['email']['host']);
        $this->assertSame('worship@example.test', $settings['email']['from_address']);
        $this->assertNotSame('secret', $settings['email']['password']);
        $this->assertSame('secret', app(ChurchModuleManager::class)->decryptSecret($settings['email']['password']));
    }

    public function test_user_can_set_message_preference_on_profile(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        Http::fake(['api.ip1sms.com/*' => Http::response(['id' => 'batch-1'], 200)]);
        $manager = app(ChurchModuleManager::class);
        $manager->enable($church, 'messaging');
        $manager->enable($church, 'messaging_sms', [
            'api_base' => 'https://api.ip1sms.com/v2',
            'api_token' => $manager->encryptSecret('test-token'),
            'sender' => 'Worship',
            'confirmation_template_id' => MessageTemplate::query()->create([
                'church_id' => $church->id,
                'name' => 'SMS confirmation',
                'text_body' => 'SMS works',
            ])->id,
        ]);

        $user = User::factory()->create([
            'church_id' => $church->id,
            'message_preference' => MessagePreference::Email,
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($user, 'Member');

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'language_id' => $user->language_id,
                'phone' => '070-123 45 67',
                'message_preference' => MessagePreference::Sms->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(MessagePreference::Sms, $user->fresh()->message_preference);
    }
}
