<?php

namespace Modules\SongList\Services;

use App\Models\Church;
use App\Models\Song;
use Illuminate\Support\Facades\DB;

class SongCsvImporter
{
    /**
     * Expected headers (WorshipHHN export): Name, Name2, Author, Description
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(Church $church, string $absolutePath): array
    {
        $preview = $this->preview($church, $absolutePath);

        if (! $preview['valid']) {
            throw new \InvalidArgumentException($preview['error'] ?? 'CSV could not be read.');
        }

        $created = 0;
        $updated = 0;
        $skipped = $preview['skipped'];

        DB::transaction(function () use ($church, $preview, &$created, &$updated): void {
            foreach ($preview['rows'] as $row) {
                $attributes = [
                    'name2' => $row['name2'],
                    'author' => $row['author'],
                    'description' => $row['description'],
                ];

                if ($row['action'] === 'update') {
                    Song::query()
                        ->where('church_id', $church->id)
                        ->whereKey($row['existing_id'])
                        ->update($attributes);
                    $updated++;

                    continue;
                }

                Song::query()->create([
                    'church_id' => $church->id,
                    'name' => $row['name'],
                    ...$attributes,
                ]);
                $created++;
            }
        });

        return compact('created', 'updated', 'skipped');
    }

    /**
     * Dry-run the CSV: validate headers and count create/update/skip without writing.
     *
     * @return array{
     *     valid: bool,
     *     error: ?string,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     samples: list<array{name: string, action: string, author: ?string}>,
     *     rows: list<array{name: string, name2: ?string, author: ?string, description: ?string, action: string, existing_id: ?int}>
     * }
     */
    public function preview(Church $church, string $absolutePath): array
    {
        $empty = [
            'valid' => false,
            'error' => null,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'samples' => [],
            'rows' => [],
        ];

        $handle = @fopen($absolutePath, 'r');

        if ($handle === false) {
            $empty['error'] = __('Could not open the CSV file.');

            return $empty;
        }

        try {
            $header = fgetcsv($handle, escape: '\\');

            if ($header === false) {
                $empty['error'] = __('This CSV is empty.');

                return $empty;
            }

            try {
                $map = $this->mapHeader($header);
            } catch (\InvalidArgumentException $e) {
                $empty['error'] = $e->getMessage();

                return $empty;
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $rows = [];
            $samples = [];

            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                if ($this->rowIsBlank($row)) {
                    $skipped++;

                    continue;
                }

                $name = trim((string) ($row[$map['name']] ?? ''));

                if ($name === '') {
                    $skipped++;

                    continue;
                }

                $attributes = [
                    'name' => $name,
                    'name2' => $this->nullable($row[$map['name2']] ?? null),
                    'author' => $this->nullable($row[$map['author']] ?? null),
                    'description' => $this->nullable($row[$map['description']] ?? null),
                ];

                $existing = Song::query()
                    ->where('church_id', $church->id)
                    ->where('name', $name)
                    ->first();

                $action = $existing ? 'update' : 'create';

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }

                $rows[] = [
                    ...$attributes,
                    'action' => $action,
                    'existing_id' => $existing?->id,
                ];

                if (count($samples) < 5) {
                    $samples[] = [
                        'name' => $name,
                        'action' => $action,
                        'author' => $attributes['author'],
                    ];
                }
            }

            return [
                'valid' => true,
                'error' => null,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'samples' => $samples,
                'rows' => $rows,
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $header
     * @return array{name: int, name2: int, author: int, description: int}
     */
    protected function mapHeader(array $header): array
    {
        $normalized = [];

        foreach ($header as $index => $column) {
            $key = strtolower(trim((string) $column));
            $normalized[$key] = $index;
        }

        $required = ['name', 'name2', 'author', 'description'];

        foreach ($required as $column) {
            if (! array_key_exists($column, $normalized)) {
                throw new \InvalidArgumentException(
                    __('CSV must include columns: Name, Name2, Author, Description (WorshipHHN export).')
                );
            }
        }

        return [
            'name' => $normalized['name'],
            'name2' => $normalized['name2'],
            'author' => $normalized['author'],
            'description' => $normalized['description'],
        ];
    }

    /**
     * @param  list<string|null>  $row
     */
    protected function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
