<?php

namespace App\Services;

use App\Models\EventTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reconciles legacy free-text names ("Person: X" / "Team: X" lines the import appended to descriptions)
 * with real people and teams, without guessing.
 *
 * - Automatic link: the text equals exactly one person's full name (or exactly one team name) in the same
 *   church, ignoring case and extra spaces.
 * - Candidates (first name only, prefix, accent differences, display name) are reported for a human to approve
 *   through an explicit mapping of text to user/team id.
 * - A task that already has a person/team is never changed.
 * - Linking removes the text line from the description and keeps the original in `legacy_person_text` /
 *   `legacy_team_text`. Unlinked text is left exactly where it is.
 */
class LegacyFreeTextReconciler
{
    public const KIND_PERSON = 'person';

    public const KIND_TEAM = 'team';

    public const LINK_EXACT = 'link_exact';

    public const LINK_APPROVED = 'link_approved';

    public const CANDIDATES = 'needs_review_candidates';

    public const AMBIGUOUS = 'ambiguous';

    public const NO_MATCH = 'no_match';

    /**
     * @param  array<string, int>  $approvedMapping  keys like "person:Reine" or "team:Linus & Edit" => user/team id
     * @return array{church_id: int, items: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function plan(int $churchId, array $approvedMapping = []): array
    {
        $users = User::query()->where('church_id', $churchId)->get(['id', 'name', 'display_name']);
        $teams = Team::withoutGlobalScopes()->where('church_id', $churchId)->get(['id', 'name']);

        $groups = [];
        $alreadyAssigned = 0;

        $tasks = EventTask::query()
            ->whereHas('event', fn ($query) => $query->withoutGlobalScopes()->where('church_id', $churchId))
            ->where(fn ($query) => $query->where('description', 'like', '%Person: %')->orWhere('description', 'like', '%Team: %'))
            ->get(['id', 'assigned_user_id', 'assigned_team_id', 'description']);

        foreach ($tasks as $task) {
            foreach ($this->extract((string) $task->description) as [$kind, $text]) {
                $assigned = $kind === self::KIND_PERSON ? $task->assigned_user_id : $task->assigned_team_id;

                if ($assigned !== null) {
                    $alreadyAssigned++;

                    continue;
                }

                $groups[$kind.':'.$text]['kind'] = $kind;
                $groups[$kind.':'.$text]['text'] = $text;
                $groups[$kind.':'.$text]['task_ids'][] = $task->id;
            }
        }

        $items = collect($groups)
            ->map(fn (array $group, string $key): array => $this->classify($group, $key, $users, $teams, $approvedMapping))
            ->sortBy([['outcome', 'asc'], ['text', 'asc']])
            ->values();

        $totals = $items->countBy('outcome')->all() + [
            self::LINK_EXACT => 0, self::LINK_APPROVED => 0, self::CANDIDATES => 0, self::AMBIGUOUS => 0, self::NO_MATCH => 0,
        ];
        $totals['distinct_texts'] = $items->count();
        $totals['tasks_with_text'] = $items->sum(fn (array $item): int => count($item['task_ids']));
        $totals['skipped_already_assigned'] = $alreadyAssigned;

        return ['church_id' => $churchId, 'items' => $items->all(), 'totals' => $totals];
    }

    /**
     * @param  array{church_id: int, items: list<array<string, mixed>>}  $plan
     */
    public function apply(array $plan): int
    {
        $linked = 0;

        DB::transaction(function () use ($plan, &$linked): void {
            foreach ($plan['items'] as $item) {
                if (! in_array($item['outcome'], [self::LINK_EXACT, self::LINK_APPROVED], true)) {
                    continue;
                }

                foreach ($item['task_ids'] as $taskId) {
                    $linked += $this->link($plan['church_id'], $item['kind'], $item['text'], (int) $item['target_id'], (int) $taskId);
                }
            }
        });

        return $linked;
    }

    private function link(int $churchId, string $kind, string $text, int $targetId, int $taskId): int
    {
        $isPerson = $kind === self::KIND_PERSON;
        $task = EventTask::query()
            ->whereKey($taskId)
            ->whereHas('event', fn ($query) => $query->withoutGlobalScopes()->where('church_id', $churchId))
            ->first();

        $targetInChurch = $isPerson
            ? User::query()->whereKey($targetId)->where('church_id', $churchId)->exists()
            : Team::withoutGlobalScopes()->whereKey($targetId)->where('church_id', $churchId)->exists();

        $assignedColumn = $isPerson ? 'assigned_user_id' : 'assigned_team_id';

        if ($task === null || ! $targetInChurch || $task->{$assignedColumn} !== null) {
            return 0;
        }

        $label = $isPerson ? 'Person' : 'Team';
        $remaining = collect(preg_split('/\R/', (string) $task->description))
            ->reject(fn (string $line): bool => $this->isTextLine($line, $label, $text))
            ->implode("\n");

        $task->forceFill([
            $assignedColumn => $targetId,
            $isPerson ? 'legacy_person_text' : 'legacy_team_text' => $text,
            'description' => trim($remaining) === '' ? null : trim($remaining),
        ])->saveQuietly();

        return 1;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function extract(string $description): array
    {
        $found = [];

        foreach (preg_split('/\R/', $description) ?: [] as $line) {
            if (preg_match('/^Person: (.+)$/u', $line, $match) === 1) {
                $found[] = [self::KIND_PERSON, trim($match[1])];
            } elseif (preg_match('/^Team: (.+)$/u', $line, $match) === 1) {
                $found[] = [self::KIND_TEAM, trim($match[1])];
            }
        }

        return $found;
    }

    private function isTextLine(string $line, string $label, string $text): bool
    {
        return preg_match('/^'.$label.': (.+)$/u', $line, $match) === 1 && trim($match[1]) === $text;
    }

    /**
     * @param  array{kind: string, text: string, task_ids: list<int>}  $group
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Team>  $teams
     * @param  array<string, int>  $approvedMapping
     * @return array<string, mixed>
     */
    private function classify(array $group, string $key, Collection $users, Collection $teams, array $approvedMapping): array
    {
        $isPerson = $group['kind'] === self::KIND_PERSON;
        $pool = $isPerson ? $users : $teams;
        $nameOf = fn ($model): string => (string) $model->name;
        $needle = $this->normalise($group['text']);

        $base = ['kind' => $group['kind'], 'text' => $group['text'], 'task_ids' => $group['task_ids'], 'candidates' => []];

        if (isset($approvedMapping[$key]) && $pool->contains('id', $approvedMapping[$key])) {
            return $base + ['outcome' => self::LINK_APPROVED, 'target_id' => $approvedMapping[$key]];
        }

        $exact = $pool->filter(fn ($model): bool => $this->normalise($nameOf($model)) === $needle);

        if ($exact->count() === 1) {
            return $base + ['outcome' => self::LINK_EXACT, 'target_id' => $exact->first()->id];
        }

        if ($exact->count() > 1) {
            return ['candidates' => $this->describe($exact, $isPerson)] + $base + ['outcome' => self::AMBIGUOUS];
        }

        $looser = $pool->filter(function ($model) use ($needle, $nameOf, $isPerson): bool {
            $name = $this->normalise($nameOf($model));
            $display = $isPerson ? $this->normalise((string) $model->display_name) : '';

            return $this->fold($name) === $this->fold($needle)
                || ($display !== '' && $display === $needle)
                || str_starts_with($name, $needle.' ');
        });

        if ($looser->isNotEmpty()) {
            return ['candidates' => $this->describe($looser, $isPerson)] + $base + ['outcome' => self::CANDIDATES];
        }

        return $base + ['outcome' => self::NO_MATCH];
    }

    /**
     * @param  Collection<int, User|Team>  $models
     * @return list<array{id: int, name: string}>
     */
    private function describe(Collection $models, bool $isPerson): array
    {
        return $models->map(fn ($model): array => ['id' => $model->id, 'name' => (string) $model->name])->values()->all();
    }

    private function normalise(string $value): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($value)));
    }

    private function fold(string $value): string
    {
        return Str::ascii($value);
    }
}
