<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use App\Models\Church;
use App\Models\Language;
use App\Models\User;
use App\Services\UserInviter;
use App\Support\SeedsDefaultTeamTypes;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class ManageChurches extends ManageRecords
{
    protected static string $resource = ChurchResource::class;

    protected ?string $pendingAdminName = null;

    protected ?string $pendingAdminEmail = null;

    public function getTitle(): string|Htmlable
    {
        return __('Churches');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Create a church, optionally invite its first admin, then they finish setup in the app.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $this->pendingAdminName = filled($data['admin_name'] ?? null)
                        ? trim((string) $data['admin_name'])
                        : null;
                    $this->pendingAdminEmail = filled($data['admin_email'] ?? null)
                        ? trim((string) $data['admin_email'])
                        : null;

                    unset($data['admin_name'], $data['admin_email']);

                    return $data;
                })
                ->after(function (Church $record): void {
                    RolesAndPermissionsSeeder::seedForChurch($record);
                    SeedsDefaultTeamTypes::for($record);

                    $adminEmail = $this->pendingAdminEmail;
                    $adminName = $this->pendingAdminName;
                    $this->pendingAdminEmail = null;
                    $this->pendingAdminName = null;

                    if (! filled($adminEmail)) {
                        return;
                    }

                    if (! filled($adminName)) {
                        $adminName = Str::before($adminEmail, '@') ?: 'Admin';
                    }

                    $admin = User::query()->create([
                        'church_id' => $record->id,
                        'name' => $adminName,
                        'display_name' => $adminName,
                        'email' => $adminEmail,
                        'password' => app(UserInviter::class)->randomPassword(),
                        'language_id' => Language::query()->where('iso', $record->locale ?: 'en')->value('id')
                            ?? Language::query()->where('iso', 'en')->value('id'),
                    ]);

                    app(PermissionRegistrar::class)->setPermissionsTeamId($record->id);
                    $admin->syncRoles(['Church Admin']);

                    try {
                        app(UserInviter::class)->send($admin->loadMissing('church'), auth()->user());

                        Notification::make()
                            ->title(__('Church created and admin invited'))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('Church created, but the admin invite failed'))
                            ->body($e->getMessage())
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
