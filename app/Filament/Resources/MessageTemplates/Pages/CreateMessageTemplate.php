<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class CreateMessageTemplate extends CreateRecord
{
    protected static string $resource = MessageTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['church_id'] = auth()->user()?->church_id;
        $data['key'] = filled($data['key'] ?? null) ? $data['key'] : null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('Preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading(__('Preview template'))
                ->modalContent(function () {
                    $state = $this->form->getState();

                    return view('filament.messaging.message-preview', [
                        'subject' => $state['subject'] ?? null,
                        'html' => $state['body'] ?? '',
                        'text' => $state['text_body'] ?? null,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('3xl'),
        ];
    }
}
