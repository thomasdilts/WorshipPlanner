<?php

namespace App\Filament\Resources\Songs\Pages;

use App\Filament\Pages\SongStatistics;
use App\Filament\Resources\Songs\SongResource;
use App\Models\Song;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Modules\SongList\Services\SongCsvExporter;
use Modules\SongList\Services\SongCsvImporter;

class ListSongs extends ListRecords
{
    protected static string $resource = SongResource::class;

    /** @var array<string, string> */
    protected array $extraBodyAttributes = [
        'class' => 'wp-songs-list',
    ];

    public function getSubheading(): string|Htmlable|null
    {
        return __('Search the catalog, open a song for lyrics, then pick it when planning a service.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add song')),
            ActionGroup::make([
                Action::make('statistics')
                    ->label(__('Statistics'))
                    ->icon(Heroicon::OutlinedChartBar)
                    ->url(fn (): string => SongStatistics::getUrl())
                    ->visible(fn (): bool => SongStatistics::canAccess()),
                Action::make('import')
                    ->label(__('Import CSV'))
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (): bool => SongResource::canCreate())
                    ->modalHeading(__('Import songs from CSV'))
                    ->modalDescription(__('WorshipHHN export format. Review the preview before importing.'))
                    ->modalSubmitActionLabel(__('Import songs'))
                    ->form([
                        FileUpload::make('csv')
                            ->label(__('CSV file'))
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'])
                            ->disk('local')
                            ->directory('song-imports')
                            ->required()
                            ->live()
                            ->helperText(__('Required columns: Name, Name2, Author, Description.')),
                        ViewField::make('csv_preview')
                            ->label(__('Preview'))
                            ->view('filament.songs.csv-import-preview')
                            ->viewData(fn (Get $get): array => [
                                'preview' => $this->csvPreviewFor($get('csv')),
                            ])
                            ->dehydrated(false)
                            ->visible(fn (Get $get): bool => filled($get('csv'))),
                    ])
                    ->action(function (array $data): void {
                        $user = auth()->user();
                        $church = $user?->church;

                        if ($church === null) {
                            return;
                        }

                        $relative = $data['csv'];
                        $path = Storage::disk('local')->path($relative);

                        try {
                            $preview = app(SongCsvImporter::class)->preview($church, $path);

                            if (! $preview['valid']) {
                                Notification::make()
                                    ->title(__('Import blocked'))
                                    ->body($preview['error'] ?: __('This file cannot be imported.'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $result = app(SongCsvImporter::class)->import($church, $path);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('Import failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        } finally {
                            Storage::disk('local')->delete($relative);
                        }

                        Notification::make()
                            ->title(__('Songs imported'))
                            ->body(__('Created :created, updated :updated, skipped :skipped.', [
                                'created' => $result['created'],
                                'updated' => $result['updated'],
                                'skipped' => $result['skipped'],
                            ]))
                            ->success()
                            ->send();
                    }),
                Action::make('export')
                    ->label(__('Export CSV'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (): bool => Song::query()->exists() && SongResource::canViewAny())
                    ->action(function () {
                        $church = auth()->user()?->church;

                        if ($church === null) {
                            return null;
                        }

                        return app(SongCsvExporter::class)->download($church);
                    }),
            ])
                ->label(__('More'))
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->button()
                ->color('gray'),
        ];
    }

    /**
     * @return array{valid: bool, error: ?string, created: int, updated: int, skipped: int, samples: list<array{name: string, action: string, author: ?string}>}|null
     */
    protected function csvPreviewFor(mixed $relativePath): ?array
    {
        if (! filled($relativePath) || ! is_string($relativePath)) {
            return null;
        }

        $church = auth()->user()?->church;

        if ($church === null) {
            return null;
        }

        $path = Storage::disk('local')->path($relativePath);

        if (! is_file($path)) {
            return [
                'valid' => false,
                'error' => __('Upload is not ready yet. Wait a moment and try again.'),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'samples' => [],
            ];
        }

        $preview = app(SongCsvImporter::class)->preview($church, $path);
        unset($preview['rows']);

        return $preview;
    }
}
