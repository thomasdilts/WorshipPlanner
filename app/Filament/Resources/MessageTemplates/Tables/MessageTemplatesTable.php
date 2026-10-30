<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Enums\MessageTemplatePurpose;
use App\Models\MessageTemplate;
use App\Support\HtmlMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('purpose')
                    ->label(__('Used for'))
                    ->badge()
                    ->formatStateUsing(fn (MessageTemplatePurpose $state): string => $state->label())
                    ->color(fn (MessageTemplatePurpose $state): string => match ($state) {
                        MessageTemplatePurpose::Service => 'primary',
                        MessageTemplatePurpose::Team => 'success',
                        MessageTemplatePurpose::Other => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('key')->placeholder('—')->toggleable(),
                TextColumn::make('subject')->limit(40)->placeholder('—'),
                TextColumn::make('body')
                    ->label(__('Email (HTML)'))
                    ->formatStateUsing(fn (?string $state): string => HtmlMessage::excerpt($state, 60))
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('text_body')
                    ->label(__('Text message'))
                    ->limit(60)
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_active')->boolean()->label(__('Active')),
            ])
            ->filters([
                SelectFilter::make('purpose')
                    ->label(__('Used for'))
                    ->options(MessageTemplatePurpose::options()),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('Preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (MessageTemplate $record): string => 'Preview: '.$record->name)
                    ->modalContent(fn (MessageTemplate $record) => view('filament.messaging.message-preview', [
                        'subject' => $record->subject,
                        'html' => $record->body,
                        'text' => $record->text_body,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->modalWidth('3xl'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
