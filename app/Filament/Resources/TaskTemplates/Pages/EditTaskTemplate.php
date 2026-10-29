<?php

namespace App\Filament\Resources\TaskTemplates\Pages;

use App\Enums\FieldUsage;
use App\Filament\Resources\TaskTemplates\TaskTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskTemplate extends EditRecord
{
    protected static string $resource = TaskTemplateResource::class;

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
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $usage = FieldUsage::tryFrom((string) ($data['team_usage'] ?? ''));

        if ($usage === null || ! $usage->isUsed()) {
            $data['team_type_id'] = null;
        }

        return $data;
    }
}
