<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\MessagePreference;
use App\Filament\Pages\EditProfile;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Church;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Messaging\Support\MessagingAvailability;
use Tests\TestCase;

class PreferenceConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed([RolesAndPermissionsSeeder::class, LanguagesSeeder::class]);

        $manager = app(ChurchModuleManager::class);
        $manager->enable($this->church, MessagingAvailability::MODULE_EMAIL);
        $manager->enable($this->church, MessagingAvailability::MODULE_SMS, [
            'api_base' => 'https://api.ip1sms.com/v2',
            'api_token' => $manager->encryptSecret('test-token'),
            'sender' => 'Worship',
        ]);

        $this->user = User::factory()->create([
            'church_id' => $this->church->id,
            'phone' => '070-123 45 67',
            'message_preference' => MessagePreference::Email,
            'language_id' => Language::query()->where('iso', 'en')->value('id'),
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->user, 'Member');

        app(CurrentChurch::class)->set($this->church);
        $this->actingAs($this->user);
    }

    public function test_switching_to_sms_sends_a_confirmation_in_the_chosen_language_before_saving(): void
    {
        Http::fake(['api.ip1sms.com/*' => Http::response(['id' => 'batch-1'], 200)]);

        $this->saveProfile([
            'message_preference' => MessagePreference::Sms->value,
            'language_id' => Language::query()->where('iso', 'sv')->value('id'),
        ])->assertHasNoFormErrors();

        $this->assertSame(MessagePreference::Sms, $this->user->fresh()->message_preference);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['recipients'] === ['46701234567']
            && $request['body'] === 'Din föredragna meddelandetyp är SMS');
    }

    public function test_sms_choice_is_refused_when_the_confirmation_fails(): void
    {
        Http::fake(['api.ip1sms.com/*' => Http::response(['Sender' => ['Invalid sender']], 400)]);

        $this->saveProfile(['message_preference' => MessagePreference::Sms->value])
            ->assertHasFormErrors(['message_preference']);

        $this->assertSame(MessagePreference::Email, $this->user->fresh()->message_preference);
    }

    public function test_sms_choice_is_refused_without_a_usable_mobile_number(): void
    {
        Http::fake();

        $this->saveProfile(['message_preference' => MessagePreference::Sms->value, 'phone' => '08-123 456 78'])
            ->assertHasFormErrors(['message_preference']);

        $this->assertSame(MessagePreference::Email, $this->user->fresh()->message_preference);
        Http::assertNothingSent();
    }

    public function test_email_choice_is_saved_even_when_the_test_email_fails_but_the_editor_is_warned(): void
    {
        $this->user->update(['message_preference' => MessagePreference::Sms]);

        $this->saveProfile(['message_preference' => MessagePreference::Email->value])
            ->assertHasNoFormErrors()
            ->assertNotified(__('The test email could not be sent'));

        $this->assertSame(MessagePreference::Email, $this->user->fresh()->message_preference);
    }

    public function test_email_choice_sends_a_test_email_when_email_is_set_up(): void
    {
        Mail::fake();
        app(ChurchModuleManager::class)->updateSettings($this->church, MessagingAvailability::MODULE_EMAIL, [
            'email' => ['host' => 'smtp.test', 'from_address' => 'church@test'],
        ]);
        $this->user->update(['message_preference' => MessagePreference::Sms]);

        $this->saveProfile(['message_preference' => MessagePreference::Email->value])
            ->assertHasNoFormErrors();

        Mail::assertSentCount(1);
    }

    public function test_saving_without_changing_the_preference_sends_nothing(): void
    {
        Http::fake();
        Mail::fake();

        $this->saveProfile(['display_name' => 'New name'])->assertHasNoFormErrors();

        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_admin_cannot_switch_someone_to_sms_when_the_confirmation_fails(): void
    {
        Http::fake(['api.ip1sms.com/*' => Http::response(['Sender' => ['Invalid sender']], 400)]);

        $admin = User::factory()->create(['church_id' => $this->church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $this->user->getRouteKey()])
            ->fillForm(['message_preference' => MessagePreference::Sms->value])
            ->call('save')
            ->assertHasFormErrors(['message_preference']);

        $this->assertSame(MessagePreference::Email, $this->user->fresh()->message_preference);
        Http::assertSent(fn ($request): bool => $request['recipients'] === ['46701234567']);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function saveProfile(array $changes): mixed
    {
        return Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $this->user->name,
                'display_name' => $this->user->display_name,
                'email' => $this->user->email,
                'language_id' => $this->user->language_id,
                'phone' => $this->user->phone,
                'message_preference' => $this->user->message_preference->value,
                ...$changes,
            ])
            ->call('save');
    }
}
