<?php

namespace Tests\Feature;

use App\Enums\TextDirection;
use App\Filament\Pages\EditProfile;
use App\Http\Middleware\SetUserLocale;
use App\Models\Church;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\TestCase;

class LanguagePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_languages_seeder_creates_en_sv_ar(): void
    {
        $this->seed(LanguagesSeeder::class);

        $this->assertDatabaseHas('languages', [
            'iso' => 'en',
            'name_en' => 'English',
            'name_native' => 'English',
            'direction' => TextDirection::Ltr->value,
        ]);

        $this->assertDatabaseHas('languages', [
            'iso' => 'sv',
            'name_en' => 'Swedish',
            'name_native' => 'Svenska',
            'direction' => TextDirection::Ltr->value,
        ]);

        $arabic = Language::query()->where('iso', 'ar')->first();

        $this->assertNotNull($arabic);
        $this->assertSame('Arabic', $arabic->name_en);
        $this->assertSame('العربية', $arabic->name_native);
        $this->assertSame(TextDirection::Rtl, $arabic->direction);
        $this->assertStringContainsString('Noto Naskh Arabic', $arabic->font);
    }

    public function test_new_users_default_to_english(): void
    {
        $this->seed(LanguagesSeeder::class);

        $user = User::factory()->create();

        $this->assertSame('en', $user->language?->iso);
    }

    public function test_user_can_change_language_on_profile(): void
    {
        $this->seed(LanguagesSeeder::class);

        $swedish = Language::query()->where('iso', 'sv')->firstOrFail();
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'church_id' => $church->id,
            'language_id' => Language::query()->where('iso', 'en')->value('id'),
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($user, 'Member');

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'language_id' => $swedish->id,
                'message_preference' => $user->message_preference->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($swedish->id, $user->fresh()->language_id);
        $this->assertSame('sv', $user->fresh()->language?->iso);
    }

    public function test_middleware_sets_app_locale_from_user_language(): void
    {
        $this->seed(LanguagesSeeder::class);

        $arabic = Language::query()->where('iso', 'ar')->firstOrFail();
        $user = User::factory()->create(['language_id' => $arabic->id]);

        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        App::setLocale('en');

        (new SetUserLocale)->handle($request, function () {
            $this->assertSame('ar', App::getLocale());

            return response('ok');
        });
    }

    public function test_guest_locale_follows_accept_language_when_supported(): void
    {
        $this->seed(LanguagesSeeder::class);

        $request = Request::create('/admin/login', 'GET', server: [
            'HTTP_ACCEPT_LANGUAGE' => 'sv-SE,sv;q=0.9,en;q=0.8',
        ]);
        $request->setUserResolver(fn () => null);

        App::setLocale('en');

        (new SetUserLocale)->handle($request, function () {
            $this->assertSame('sv', App::getLocale());

            return response('ok');
        });
    }

    public function test_guest_locale_defaults_to_english_when_browser_unsupported(): void
    {
        $this->seed(LanguagesSeeder::class);

        $request = Request::create('/partner', 'GET', server: [
            'HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9,de;q=0.8',
        ]);
        $request->setUserResolver(fn () => null);

        App::setLocale('sv');

        (new SetUserLocale)->handle($request, function () {
            $this->assertSame('en', App::getLocale());

            return response('ok');
        });
    }

    public function test_login_page_renders_swedish_when_browser_prefers_swedish(): void
    {
        $this->seed(LanguagesSeeder::class);

        $this->withHeaders(['Accept-Language' => 'sv,en;q=0.5'])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('Logga in', false);
    }

    public function test_partner_form_renders_swedish_when_browser_prefers_swedish(): void
    {
        $this->seed(LanguagesSeeder::class);
        $this->withoutVite();

        $this->withHeaders(['Accept-Language' => 'sv-SE,sv;q=0.9'])
            ->get(route('partner.create'))
            ->assertOk()
            ->assertSee('Anslut er kyrka', false);
    }

    public function test_swedish_and_arabic_app_strings_exist(): void
    {
        App::setLocale('sv');
        $this->assertSame('Språk', __('app.language'));

        App::setLocale('ar');
        $this->assertSame('اللغة', __('app.language'));

        App::setLocale('en');
        $this->assertSame('Language', __('app.language'));
    }
}
