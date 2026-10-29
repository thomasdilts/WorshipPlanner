<?php

namespace App\Filament\Resources\Teams\Pages;

use App\Filament\Resources\Teams\Actions\MessageTeamAction;
use App\Filament\Resources\Teams\TeamResource;
use App\Services\TeamWorkspaceSummary;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    /** @var array<string, string> */
    protected array $extraBodyAttributes = [
        'class' => 'wp-team-workspace-page',
    ];

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $summary = app(TeamWorkspaceSummary::class)->for($this->getRecord());

        if (! $summary['has_leader']) {
            return __('Needs a team leader');
        }

        return trans_choice(
            ':count member · :upcoming upcoming|:count members · :upcoming upcoming',
            $summary['member_count'],
            [
                'count' => $summary['member_count'],
                'upcoming' => $summary['upcoming_count'],
            ],
        );
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.teams.workspace-summary'),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            MessageTeamAction::make($this->getRecord())
                ->color('gray'),
            EditAction::make()
                ->label(__('Edit details'))
                ->visible(fn (): bool => auth()->user()?->can('update', $this->getRecord()) ?? false),
        ];
    }
}
