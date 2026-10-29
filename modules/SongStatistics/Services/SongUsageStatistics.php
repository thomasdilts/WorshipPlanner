<?php

namespace Modules\SongStatistics\Services;

use App\Models\Church;
use App\Models\EventTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SongUsageStatistics
{
    /**
     * Aggregate songs used on event tasks for a church.
     * Includes catalog picks (song_id) and manual free-text titles.
     *
     * @return Collection<string, array{
     *     key: string,
     *     title: string,
     *     author: string|null,
     *     source: 'catalog'|'manual',
     *     song_id: int|null,
     *     usage_count: int,
     *     event_count: int,
     *     first_used_at: string|null,
     *     last_used_at: string|null
     * }>
     */
    public function forChurch(Church $church): Collection
    {
        $catalog = $this->catalogRows($church->id);
        $manual = $this->manualRows($church->id);

        return $catalog
            ->concat($manual)
            ->keyBy('key')
            ->sortByDesc('usage_count')
            ->values()
            ->keyBy('key');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function catalogRows(int $churchId): Collection
    {
        return EventTask::query()
            ->select([
                'event_tasks.song_id',
                DB::raw('COALESCE(songs.name, event_tasks.song) as title'),
                'songs.author as author',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('COUNT(DISTINCT event_tasks.event_id) as event_count'),
                DB::raw('MIN(events.starts_at) as first_used_at'),
                DB::raw('MAX(events.starts_at) as last_used_at'),
            ])
            ->join('events', 'events.id', '=', 'event_tasks.event_id')
            ->leftJoin('songs', 'songs.id', '=', 'event_tasks.song_id')
            ->where('events.church_id', $churchId)
            ->whereNotNull('event_tasks.song_id')
            ->groupBy('event_tasks.song_id', 'songs.name', 'event_tasks.song', 'songs.author')
            ->get()
            ->map(function ($row): array {
                $songId = (int) $row->song_id;

                return [
                    'key' => 'catalog:'.$songId,
                    'title' => (string) ($row->title ?: 'Untitled song'),
                    'author' => filled($row->author) ? (string) $row->author : null,
                    'source' => 'catalog',
                    'song_id' => $songId,
                    'usage_count' => (int) $row->usage_count,
                    'event_count' => (int) $row->event_count,
                    'first_used_at' => $row->first_used_at,
                    'last_used_at' => $row->last_used_at,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function manualRows(int $churchId): Collection
    {
        $normalized = 'LOWER(TRIM(event_tasks.song))';

        return EventTask::query()
            ->select([
                DB::raw('MIN(event_tasks.song) as title'),
                DB::raw("{$normalized} as title_key"),
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('COUNT(DISTINCT event_tasks.event_id) as event_count'),
                DB::raw('MIN(events.starts_at) as first_used_at'),
                DB::raw('MAX(events.starts_at) as last_used_at'),
            ])
            ->join('events', 'events.id', '=', 'event_tasks.event_id')
            ->where('events.church_id', $churchId)
            ->whereNull('event_tasks.song_id')
            ->whereNotNull('event_tasks.song')
            ->where('event_tasks.song', '!=', '')
            ->groupBy(DB::raw($normalized))
            ->get()
            ->map(function ($row): array {
                $title = (string) $row->title;

                return [
                    'key' => 'manual:'.Str::lower(trim($title)),
                    'title' => $title,
                    'author' => null,
                    'source' => 'manual',
                    'song_id' => null,
                    'usage_count' => (int) $row->usage_count,
                    'event_count' => (int) $row->event_count,
                    'first_used_at' => $row->first_used_at,
                    'last_used_at' => $row->last_used_at,
                ];
            });
    }
}
