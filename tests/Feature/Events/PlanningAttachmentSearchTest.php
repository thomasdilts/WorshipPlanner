<?php

namespace Tests\Feature\Events;

use App\Models\Church;
use App\Models\Picture;
use App\Models\SharedFile;
use App\Services\PlanningAttachmentSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningAttachmentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_picture_and_file_search_are_church_scoped_and_match_names(): void
    {
        $mine = Church::factory()->create();
        $theirs = Church::factory()->create();

        $picture = Picture::factory()->create([
            'church_id' => $mine->id,
            'name' => 'Altar flowers',
            'description' => 'Spring arrangement',
        ]);
        Picture::factory()->create([
            'church_id' => $theirs->id,
            'name' => 'Altar flowers',
        ]);

        $file = SharedFile::factory()->create([
            'church_id' => $mine->id,
            'name' => 'Chord chart',
            'original_name' => 'chords.pdf',
        ]);
        SharedFile::factory()->create([
            'church_id' => $theirs->id,
            'name' => 'Chord chart',
        ]);

        $pictures = app(PlanningAttachmentSearch::class)->pictureOptions($mine->id, 'altar');
        $files = app(PlanningAttachmentSearch::class)->fileOptions($mine->id, 'chord');

        $this->assertSame([$picture->id => 'Altar flowers'], $pictures);
        $this->assertArrayHasKey($file->id, $files);
        $this->assertStringContainsString('chords.pdf', $files[$file->id]);
        $this->assertCount(1, $files);
    }
}
