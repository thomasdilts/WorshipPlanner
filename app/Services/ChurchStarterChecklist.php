<?php

namespace App\Services;

use App\Core\Modules\ChurchModuleManager;
use App\Filament\Pages\ChurchSettings;
use App\Filament\Pages\ManageModules;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Church;
use App\Models\Event;
use App\Models\User;

/**
 * Short first-time setup checklist for a church.
 *
 * @phpstan-type Step array{key: string, label: string, done: bool, url: ?string, hint: string}
 */
class ChurchStarterChecklist
{
    public function __construct(
        protected ChurchModuleManager $modules,
    ) {}

    /**
     * @return list<Step>
     */
    public function steps(Church $church): array
    {
        $peopleCount = User::query()->where('church_id', $church->id)->count();
        $eventCount = Event::query()->where('church_id', $church->id)->count();
        $modulesOn = collect(['teams', 'song_list', 'messaging', 'unavailability'])
            ->filter(fn (string $key): bool => $this->modules->isEnabled($church, $key))
            ->count();

        return [
            [
                'key' => 'identity',
                'label' => __('Name and logo'),
                'done' => filled($church->name) && filled($church->logo_path ?? null),
                'url' => ChurchSettings::getUrl(),
                'hint' => __('So volunteers recognize your church in the app and on PDFs.'),
            ],
            [
                'key' => 'people',
                'label' => __('Invite people'),
                'done' => $peopleCount >= 2,
                'url' => UserResource::getUrl('create'),
                'hint' => __('Add people and send an invite so they set their own password.'),
            ],
            [
                'key' => 'service',
                'label' => __('Create a first service'),
                'done' => $eventCount >= 1,
                'url' => EventResource::getUrl('create'),
                'hint' => __('One service is enough to try the planner.'),
            ],
            [
                'key' => 'modules',
                'label' => __('Turn on only what you need'),
                'done' => $modulesOn >= 1,
                'url' => ManageModules::getUrl(),
                'hint' => __('Songs, teams, messaging… leave the rest off until you need them.'),
            ],
        ];
    }

    public function incompleteCount(Church $church): int
    {
        return collect($this->steps($church))->where('done', false)->count();
    }

    public function isComplete(Church $church): bool
    {
        return $this->incompleteCount($church) === 0;
    }
}
