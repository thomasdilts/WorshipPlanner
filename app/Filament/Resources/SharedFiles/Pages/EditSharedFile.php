<?php

namespace App\Filament\Resources\SharedFiles\Pages;

use App\Filament\Resources\SharedFiles\SharedFileResource;
use App\Models\SharedFile;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditSharedFile extends EditRecord
{
    protected static string $resource = SharedFileResource::class;

    /** @var list<int> */
    protected array $pendingTeamIds = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var SharedFile $record */
        $record = $this->getRecord();
        $data['team_ids'] = $record->teams()->pluck('teams.id')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingTeamIds = array_values(array_map('intval', $data['team_ids'] ?? []));
        unset($data['team_ids']);

        if (filled($data['path'] ?? null)) {
            $path = is_array($data['path']) ? ($data['path'][0] ?? null) : $data['path'];
            $data['path'] = $path;
            $data['mime'] = $path ? Storage::disk('public')->mimeType($path) : null;
            $data['size'] = $path ? Storage::disk('public')->size($path) : null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var SharedFile $record */
        $record = $this->getRecord();
        $record->teams()->sync($this->pendingTeamIds);
    }
}
