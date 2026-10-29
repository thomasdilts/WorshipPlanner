<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Filament\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Modules\Teams\Services\TeamRosterService;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    /** @var list<int> */
    protected array $pendingMemberIds = [];

    /** @var list<int> */
    protected array $pendingLeaderIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);

        $data['church_id'] = $user->church_id;

        $this->pendingMemberIds = array_map('intval', $data['member_ids'] ?? []);
        $this->pendingLeaderIds = array_map('intval', $data['leader_ids'] ?? []);

        unset($data['member_ids'], $data['leader_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(TeamRosterService::class)->syncRoster(
            $this->record,
            $this->pendingMemberIds,
            $this->pendingLeaderIds,
        );
    }
}
