<?php

namespace App\Console\Commands;

use App\Services\LegacyTimelineRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairLegacyTimelines extends Command
{
    protected $signature = 'worship:repair-legacy-timelines
        {--church= : Church id to repair (required)}
        {--event= : Limit to one event id}
        {--accept-ambiguous : Also repair events flagged as ambiguous (shared start times, overnight, unusable event end)}
        {--apply : Write the changes. Without this flag nothing is written}';

    protected $description = 'Rebuild durations, order and end times for activities imported from the legacy PPP system (dry run by default).';

    public function handle(LegacyTimelineRepair $repair): int
    {
        $churchId = $this->option('church');

        if (! is_numeric($churchId)) {
            $this->components->error('Pass --church=<id>.');

            return self::INVALID;
        }

        $eventId = is_numeric($this->option('event')) ? (int) $this->option('event') : null;
        $plan = $repair->plan((int) $churchId, (bool) $this->option('accept-ambiguous'), $eventId);

        $this->renderSummary($plan);

        if (! $this->option('apply')) {
            $this->components->info('Dry run: nothing was written. Re-run with --apply to write.');

            return self::SUCCESS;
        }

        $reportPath = sprintf('legacy-timeline-repairs/%s-church%d.json', now()->format('Ymd-His'), $churchId);
        Storage::disk('local')->put($reportPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $updated = $repair->apply($plan);

        $this->components->info("Updated {$updated} tasks. Before/after report: ".Storage::disk('local')->path($reportPath));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function renderSummary(array $plan): void
    {
        $this->table(['Measure', 'Count'], collect($plan['totals'])->map(fn (int $count, string $key): array => [str_replace('_', ' ', $key), $count])->values()->all());

        $skipped = collect($plan['events'])->where('status', 'skipped');

        if ($skipped->isNotEmpty()) {
            $this->components->warn('Skipped (needs review):');
            $this->table(
                ['Event', 'Date', 'Flags'],
                $skipped->take(25)->map(fn (array $event): array => [$event['event_id'].' '.$event['name'], $event['starts_at'], implode(', ', $event['flags'])])->all(),
            );
        }

        $sample = $this->option('event')
            ? collect($plan['events'])->where('status', 'repair')->all()
            : collect($plan['events'])->where('status', 'repair')->take(1)->all();

        foreach ($sample as $event) {
            $this->components->info("Before/after: event {$event['event_id']} {$event['name']} ({$event['starts_at']})");
            $this->table(
                ['Task', 'Start', 'Mins before', 'Mins after', 'Ends', 'Order', 'From'],
                collect($event['tasks'])->map(fn (array $task): array => [
                    $task['name'], $task['starts_at'], $task['before']['duration'], $task['after']['duration'], $task['after']['ends_at'], $task['after']['sort_order'], $task['inferred'],
                ])->all(),
            );
        }
    }
}
