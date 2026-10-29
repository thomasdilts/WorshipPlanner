<?php

namespace App\Filament\Resources\SharedFiles\Pages;

use App\Filament\Resources\SharedFiles\SharedFileResource;
use App\Models\SharedFile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateSharedFile extends CreateRecord
{
    protected static string $resource = SharedFileResource::class;

    /** @var list<int> */
    protected array $pendingTeamIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);

        $this->pendingTeamIds = array_values(array_map('intval', $data['team_ids'] ?? []));
        unset($data['team_ids']);

        $data['church_id'] = $user->church_id;
        $data['uploaded_by_user_id'] = $user->id;

        if (filled($data['path'] ?? null)) {
            $path = is_array($data['path']) ? ($data['path'][0] ?? null) : $data['path'];
            $data['path'] = $path;
            $data['mime'] = $path ? Storage::disk('public')->mimeType($path) : null;
            $data['size'] = $path ? Storage::disk('public')->size($path) : null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var SharedFile $record */
        $record = $this->getRecord();
        $record->teams()->sync($this->pendingTeamIds);
    }
}
