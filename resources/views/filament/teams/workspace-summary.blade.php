@php
    use App\Services\TeamWorkspaceSummary;

    $team = $this->getRecord();
    $summary = app(TeamWorkspaceSummary::class)->for($team);
@endphp

<div class="wp-team-workspace" role="region" aria-label="{{ __('Team overview') }}">
    <div class="wp-team-workspace-head">
        <div>
            <p class="wp-team-workspace-type">{{ $team->type?->name }}</p>
            @if (filled($team->description))
                <p class="wp-team-workspace-desc">{{ $team->description }}</p>
            @endif
        </div>

        <div class="wp-team-workspace-stats" role="status">
            <span class="wp-staffing-pill">
                {{ trans_choice(':count member|:count members', $summary['member_count'], ['count' => $summary['member_count']]) }}
            </span>
            <span class="wp-staffing-pill {{ $summary['has_leader'] ? '' : 'wp-staffing-pill-warn' }}">
                @if ($summary['has_leader'])
                    {{ trans_choice(':count leader|:count leaders', $summary['leader_count'], ['count' => $summary['leader_count']]) }}
                @else
                    {{ __('No leader assigned') }}
                @endif
            </span>
            <span class="wp-staffing-pill">
                {{ trans_choice(':count upcoming|:count upcoming', $summary['upcoming_count'], ['count' => $summary['upcoming_count']]) }}
            </span>
        </div>
    </div>

    <div class="wp-team-workspace-grid">
        <section class="wp-team-workspace-block">
            <h3 class="wp-team-workspace-heading">{{ __('Leaders') }}</h3>
            @if ($summary['leaders'] === [])
                <p class="wp-team-workspace-empty">{{ __('Add at least one leader so this team can respond and manage the roster.') }}</p>
            @else
                <ul class="wp-team-workspace-leaders">
                    @foreach ($summary['leaders'] as $leader)
                        <li>{!! $leader['avatar'] !!}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="wp-team-workspace-block">
            <h3 class="wp-team-workspace-heading">{{ __('Next commitments') }}</h3>
            @if ($summary['next_commitments'] === [])
                <p class="wp-team-workspace-empty">{{ __('No upcoming assignments for this team.') }}</p>
            @else
                <ul class="wp-team-workspace-commits">
                    @foreach ($summary['next_commitments'] as $row)
                        <li>
                            <span class="wp-team-workspace-commit-when">{{ $row['when'] }}</span>
                            <span class="wp-team-workspace-commit-role">{{ $row['role'] }}</span>
                            <span class="wp-team-workspace-commit-event">{{ $row['event'] }} · {{ $row['state'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
