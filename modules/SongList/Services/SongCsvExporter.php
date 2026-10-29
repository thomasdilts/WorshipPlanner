<?php

namespace Modules\SongList\Services;

use App\Models\Church;
use App\Models\Song;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SongCsvExporter
{
    public function download(Church $church, ?string $filename = null): StreamedResponse
    {
        $filename ??= 'Song export'.now()->format('Y-m-d H-i').'.csv';

        return response()->streamDownload(function () use ($church): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Name', 'Name2', 'Author', 'Description'], escape: '\\');

            Song::query()
                ->where('church_id', $church->id)
                ->orderBy('name')
                ->cursor()
                ->each(function (Song $song) use ($out): void {
                    fputcsv($out, [
                        $song->name,
                        $song->name2 ?? '',
                        $song->author ?? '',
                        $song->description ?? '',
                    ], escape: '\\');
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
