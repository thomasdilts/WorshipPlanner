<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Models\Church;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessageTemplateFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'messaging');

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        app(CurrentChurch::class)->set($church);
        $this->actingAs($admin);
    }

    public function test_create_page_explains_placeholders(): void
    {
        Livewire::test(CreateMessageTemplate::class)
            ->assertSuccessful()
            ->assertSee('{{first_name}}')
            ->assertSee('{{ name }}');
    }

    public function test_template_stores_html_and_text_versions(): void
    {
        Livewire::test(CreateMessageTemplate::class)
            ->fillForm([
                'name' => 'Reminder',
                'subject' => 'See you {{date}}',
                'body' => '<p>Hi <strong>{{first_name}}</strong></p>',
                'text_body' => 'Hi {{first_name}}',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = MessageTemplate::query()->sole();

        $this->assertSame('<p>Hi <strong>{{first_name}}</strong></p>', $template->body);
        $this->assertSame('Hi {{first_name}}', $template->text_body);
    }

    public function test_template_needs_html_or_text(): void
    {
        Livewire::test(CreateMessageTemplate::class)
            ->fillForm([
                'name' => 'Empty',
                'body' => null,
                'text_body' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['body' => 'required_without', 'text_body' => 'required_without']);

        $this->assertSame(0, MessageTemplate::query()->count());
    }
}
