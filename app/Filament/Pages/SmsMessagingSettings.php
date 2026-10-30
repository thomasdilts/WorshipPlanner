<?php

namespace App\Filament\Pages;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Messaging\Services\TemplateMessages;
use Modules\Messaging\Support\MessagingAvailability;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class SmsMessagingSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 56;

    public static function getNavigationLabel(): string
    {
        return __('SMS settings');
    }

    public function getTitle(): string|Htmlable
    {
        return __('SMS messaging settings (iP.1)');
    }

    protected static ?string $slug = 'sms-messaging-settings';

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->church_id === null || $user->church === null) {
            return false;
        }

        if (! app(ChurchModuleManager::class)->isEnabled($user->church, MessagingAvailability::MODULE_SMS)) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        return $user->can('messaging.settings.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $church = app(CurrentChurch::class)->get() ?? auth()->user()?->church;
        abort_unless($church, 403);

        $settings = app(ChurchModuleManager::class)->settings($church, MessagingAvailability::MODULE_SMS);

        $this->form->fill([
            'api_base' => $settings['api_base'] ?? 'https://api.ip1sms.com/v2',
            'api_token' => '',
            'sender' => $settings['sender'] ?? '',
            'confirmation_template_id' => $settings['confirmation_template_id'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('iP.1 SMS API'))
                    ->description(__('SMS via iP.1. Paste the API token from your iP.1 account. The sender must already be registered there.'))
                    ->schema([
                        TextInput::make('api_base')
                            ->label(__('API base URL'))
                            ->required()
                            ->default('https://api.ip1sms.com/v2'),
                        TextInput::make('api_token')
                            ->label(__('API token'))
                            ->password()
                            ->revealable()
                            ->helperText(__('Leave blank to keep the current token.')),
                        TextInput::make('sender')
                            ->label(__('Sender'))
                            ->required()
                            ->maxLength(15)
                            ->helperText(__('E.164 number or alphanumeric (3–11 chars).')),
                    ]),
                Section::make(__('Preferred messaging confirmation'))
                    ->description(__('Sent by SMS when someone chooses SMS as their preferred messaging. The choice is refused if it cannot be sent.'))
                    ->schema([
                        Select::make('confirmation_template_id')
                            ->label(__('Message template'))
                            ->options(fn (): array => app(TemplateMessages::class)->options())
                            ->searchable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('Save settings'))
                                ->submit('save'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $church = app(CurrentChurch::class)->get() ?? auth()->user()?->church;
        abort_unless($church, 403);

        $manager = app(ChurchModuleManager::class);
        $data = $this->form->getState();
        $existing = $manager->settings($church, MessagingAvailability::MODULE_SMS);

        $token = filled($data['api_token'] ?? null)
            ? $manager->encryptSecret($data['api_token'])
            : ($existing['api_token'] ?? '');

        $manager->updateSettings($church, MessagingAvailability::MODULE_SMS, [
            'api_base' => rtrim((string) ($data['api_base'] ?? 'https://api.ip1sms.com/v2'), '/'),
            'api_token' => $token,
            'sender' => $data['sender'] ?? '',
            'confirmation_template_id' => (int) $data['confirmation_template_id'],
        ]);

        $manager->enable($church, MessagingAvailability::MODULE_SMS);

        Notification::make()
            ->title(__('SMS settings saved'))
            ->success()
            ->send();

        $this->form->fill([
            ...$data,
            'api_token' => '',
        ]);
    }
}
