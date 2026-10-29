<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMessageTemplate extends EditRecord
{
    protected static string $resource = MessageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('Preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading(fn (): string => 'Preview: '.($this->data['name'] ?? $this->getRecord()->name))
                ->modalContent(function () {
                    $state = $this->form->getState();

                    return view('filament.messaging.message-preview', [
                        'subject' => $state['subject'] ?? $this->getRecord()->subject,
                        'html' => $state['body'] ?? $this->getRecord()->body,
                        'text' => $state['text_body'] ?? $this->getRecord()->text_body,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('3xl'),
            DeleteAction::make(),
        ];
    }
}
