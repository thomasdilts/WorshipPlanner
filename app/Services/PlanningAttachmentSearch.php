<?php

namespace App\Services;

use App\Models\Picture;
use App\Models\SharedFile;
use Illuminate\Database\Eloquent\Builder;

/**
 * Searchable options for pictures and shared files on the service-order form.
 */
class PlanningAttachmentSearch
{
    /**
     * @return array<int, string>
     */
    public function pictureOptions(?int $churchId, ?string $search = null, int $limit = 40): array
    {
        if ($churchId === null) {
            return [];
        }

        $term = trim((string) $search);

        $query = Picture::query()
            ->where('church_id', $churchId)
            ->orderBy('name')
            ->limit(max(1, $limit));

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public function fileOptions(?int $churchId, ?string $search = null, int $limit = 40): array
    {
        if ($churchId === null) {
            return [];
        }

        $term = trim((string) $search);

        $query = SharedFile::query()
            ->where('church_id', $churchId)
            ->orderBy('name')
            ->limit(max(1, $limit));

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('original_name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return $query
            ->get()
            ->mapWithKeys(fn (SharedFile $file): array => [
                $file->id => $file->name.(filled($file->original_name) ? ' ('.$file->original_name.')' : ''),
            ])
            ->all();
    }

    public function pictureLabel(?int $id, ?int $churchId = null): ?string
    {
        if ($id === null) {
            return null;
        }

        $query = Picture::query()->whereKey($id);

        if ($churchId !== null) {
            $query->where('church_id', $churchId);
        }

        return $query->value('name');
    }

    public function fileLabel(?int $id, ?int $churchId = null): ?string
    {
        if ($id === null) {
            return null;
        }

        $query = SharedFile::query()->whereKey($id);

        if ($churchId !== null) {
            $query->where('church_id', $churchId);
        }

        $file = $query->first();

        if ($file === null) {
            return null;
        }

        return $file->name.(filled($file->original_name) ? ' ('.$file->original_name.')' : '');
    }
}
