<?php

namespace Tests\Feature\SongList;

use App\Models\Church;
use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SongList\Services\SongCsvImporter;
use Tests\TestCase;

class SongCsvPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_reports_creates_updates_and_skips_without_writing(): void
    {
        $church = Church::factory()->create();
        Song::factory()->create([
            'church_id' => $church->id,
            'name' => 'Amazing Grace',
            'description' => 'Old lyrics',
        ]);

        $path = storage_path('framework/testing-songs-preview.csv');
        file_put_contents($path, implode("\n", [
            'Name,Name2,Author,Description',
            '"Amazing Grace","Grace","Newton","New lyrics"',
            '"Holy Holy","","Someone","Holy holy"',
            ',,,',
            '"","",,"orphan"',
        ])."\n");

        $preview = app(SongCsvImporter::class)->preview($church, $path);

        $this->assertTrue($preview['valid']);
        $this->assertSame(1, $preview['created']);
        $this->assertSame(1, $preview['updated']);
        $this->assertSame(2, $preview['skipped']);
        $this->assertCount(2, $preview['samples']);
        $this->assertSame('Amazing Grace', $preview['samples'][0]['name']);
        $this->assertSame('update', $preview['samples'][0]['action']);

        $this->assertDatabaseHas('songs', [
            'church_id' => $church->id,
            'name' => 'Amazing Grace',
            'description' => 'Old lyrics',
        ]);
        $this->assertDatabaseMissing('songs', [
            'church_id' => $church->id,
            'name' => 'Holy Holy',
        ]);

        $result = app(SongCsvImporter::class)->import($church, $path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(2, $result['skipped']);
        $this->assertDatabaseHas('songs', [
            'name' => 'Amazing Grace',
            'description' => 'New lyrics',
        ]);
        $this->assertDatabaseHas('songs', [
            'name' => 'Holy Holy',
        ]);

        @unlink($path);
    }

    public function test_preview_rejects_bad_headers_clearly(): void
    {
        $church = Church::factory()->create();
        $path = storage_path('framework/testing-songs-bad.csv');
        file_put_contents($path, "Title,Artist\nHello,World\n");

        $preview = app(SongCsvImporter::class)->preview($church, $path);

        $this->assertFalse($preview['valid']);
        $this->assertStringContainsString('Name, Name2, Author, Description', (string) $preview['error']);

        @unlink($path);
    }
}
