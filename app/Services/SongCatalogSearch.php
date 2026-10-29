<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Church song catalog search for the planning picker and library.
 *
 * Ranks by recent service usage, then name. Matches primary name, alternate name, and author.
 */
class SongCatalogSearch
{
    /**
     * @return array<int, string> song id => label
     */
    public function options(?int $churchId, ?string $search = null, int $limit = 40): array
    {
        if ($churchId === null) {
            return [];
        }

        return $this->query($churchId, $search, $limit)
            ->mapWithKeys(fn (Song $song): array => [$song->id => $song->label()])
            ->all();
    }

    /**
     * @return Collection<int, Song>
     */
    public function query(?int $churchId, ?string $search = null, int $limit = 40): Collection
    {
        if ($churchId === null) {
            return collect();
        }

        $term = trim((string) $search);

        $builder = Song::query()
            ->where('church_id', $churchId)
            ->withMax('eventTasks as last_used_at', 'updated_at')
            ->withCount('eventTasks');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $builder->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('name2', 'like', $like)
                    ->orWhere('author', 'like', $like);
            });
        }

        return $builder
            ->orderByDesc('last_used_at')
            ->orderByDesc('event_tasks_count')
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get();
    }

    public function labelFor(?int $songId, ?int $churchId = null): ?string
    {
        if ($songId === null) {
            return null;
        }

        $query = Song::query()->whereKey($songId);

        if ($churchId !== null) {
            $query->where('church_id', $churchId);
        }

        return $query->first()?->label();
    }
}
