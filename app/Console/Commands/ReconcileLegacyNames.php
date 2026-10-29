<?php

namespace App\Console\Commands;

use App\Services\LegacyFreeTextReconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReconcileLegacyNames extends Command
{
    protected $signature = 'worship:reconcile-legacy-names
        {--church= : Church id (required)}
        {--mapping= : JSON file of approved links, e.g. {"person:Reine": 55, "team:Linus & Edit": 12}}
        {--apply : Write the links. Without this flag nothing is written}';

    protected $description = 'Link legacy free-text names to people/teams only when the match is exact and unique, or explicitly approved (dry run by default).';

    public function handle(LegacyFreeTextReconciler $reconciler): int
    {
        if (! is_numeric($this->option('church'))) {
            $this->components->error('Pass --church=<id>.');

            return self::INVALID;
        }

        $mapping = [];

        if (filled($this->option('mapping'))) {
            $path = (string) $this->option('mapping');
            $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

            if (! is_array($decoded)) {
                $this->components->error("Mapping file [{$path}] is missing or not a JSON object.");

                return self::INVALID;
            }

            $mapping = array_map('intval', $decoded);
        }

        $churchId = (int) $this->option('church');
        $plan = $reconciler->plan($churchId, $mapping);

        $this->table(['Outcome', 'Distinct texts'], collect($plan['items'])->countBy('outcome')->map(fn (int $count, string $outcome): array => [$outcome, $count])->values()->all());
        $this->line("Tasks with legacy text: {$plan['totals']['tasks_with_text']} (already assigned and left alone: {$plan['totals']['skipped_already_assigned']})");

        $review = collect($plan['items'])->whereIn('outcome', [LegacyFreeTextReconciler::CANDIDATES, LegacyFreeTextReconciler::AMBIGUOUS]);

        if ($review->isNotEmpty()) {
            $this->components->warn('Needs a human decision (never linked automatically):');
            $this->table(['Text', 'Tasks', 'Candidates (id name)'], $review->take(30)->map(fn (array $item): array => [
                $item['kind'].': '.$item['text'],
                count($item['task_ids']),
                collect($item['candidates'])->map(fn (array $candidate): string => $candidate['id'].' '.$candidate['name'])->implode('; '),
            ])->all());
        }

        if (! $this->option('apply')) {
            $this->components->info('Dry run: nothing was written. Re-run with --apply to link exact/approved matches.');

            return self::SUCCESS;
        }

        $linkable = collect($plan['items'])->whereIn('outcome', [LegacyFreeTextReconciler::LINK_EXACT, LegacyFreeTextReconciler::LINK_APPROVED]);

        if ($linkable->isEmpty()) {
            $this->components->info('Nothing to link.');

            return self::SUCCESS;
        }

        $reportPath = sprintf('legacy-name-links/%s-church%d.json', now()->format('Ymd-His'), $churchId);
        Storage::disk('local')->put($reportPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $linked = $reconciler->apply($plan);

        $this->components->info("Linked {$linked} task fields. Report: ".Storage::disk('local')->path($reportPath));

        return self::SUCCESS;
    }
}
