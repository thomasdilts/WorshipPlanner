<?php

namespace Tests\Feature\SongList;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Songs\Pages\ListSongs;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Song;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\SongCatalogSearch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SongCatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_name_alternate_and_author_and_ranks_recent_usage(): void
    {
        $church = Church::factory()->create();
        app(ChurchModuleManager::class)->enable($church, 'song_list');
        app(CurrentChurch::class)->set($church);

        $recent = Song::factory()->create([
            'church_id' => $church->id,
            'name' => 'Amazing Grace',
            'name2' => 'Grace',
            'author' => 'Newton',
        ]);
        $older = Song::factory()->create([
            'church_id' => $church->id,
            'name' => 'Grace Alone',
            'author' => 'Other',
        ]);
        Song::factory()->create([
            'church_id' => $church->id,
            'name' => 'Unrelated',
            'author' => 'Nobody',
        ]);

        $template = TaskTemplate::factory()->create(['church_id' => $church->id]);
        $event = Event::factory()->create(['church_id' => $church->id]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'song_id' => $recent->id,
            'updated_at' => now()->subDay(),
        ]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'song_id' => $older->id,
            'updated_at' => now()->subMonths(2),
        ]);

        $options = app(SongCatalogSearch::class)->options($church->id, 'grace');

        $this->assertArrayHasKey($recent->id, $options);
        $this->assertArrayHasKey($older->id, $options);
        $this->assertSame([$recent->id, $older->id], array_keys($options));
        $this->assertStringContainsString('Amazing Grace', $options[$recent->id]);
    }

    public function test_empty_search_returns_recent_first(): void
    {
        $church = Church::factory()->create();
        app(ChurchModuleManager::class)->enable($church, 'song_list');

        $used = Song::factory()->create(['church_id' => $church->id, 'name' => 'Used Song']);
        $unused = Song::factory()->create(['church_id' => $church->id, 'name' => 'Aaa Unused']);

        $template = TaskTemplate::factory()->create(['church_id' => $church->id]);
        $event = Event::factory()->create(['church_id' => $church->id]);
        EventTask::factory()->create([
            'event_id' => $event->id,
            'task_template_id' => $template->id,
            'song_id' => $used->id,
        ]);

        $ids = array_keys(app(SongCatalogSearch::class)->options($church->id, null, 10));

        $this->assertSame($used->id, $ids[0]);
        $this->assertContains($unused->id, $ids);
    }

    public function test_songs_list_puts_import_export_under_more_group(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, 'song_list');
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $this->actingAs($admin);

        $component = Livewire::test(ListSongs::class)->assertSuccessful();

        $actions = collect($component->instance()->getCachedHeaderActions());
        $labels = $actions
            ->flatMap(function ($action) {
                if ($action instanceof ActionGroup) {
                    return collect($action->getActions())->map(fn ($inner) => method_exists($inner, 'getName') ? $inner->getName() : null);
                }

                return method_exists($action, 'getName') ? [$action->getName()] : [];
            })
            ->filter()
            ->values()
            ->all();

        $this->assertContains('create', $labels);
        $this->assertContains('import', $labels);
        $this->assertContains('export', $labels);
        $this->assertContains('statistics', $labels);
    }

    public function test_search_is_church_scoped(): void
    {
        $mine = Church::factory()->create();
        $theirs = Church::factory()->create();
        app(ChurchModuleManager::class)->enable($mine, 'song_list');
        app(ChurchModuleManager::class)->enable($theirs, 'song_list');

        Song::factory()->create(['church_id' => $mine->id, 'name' => 'Shared Title']);
        Song::factory()->create(['church_id' => $theirs->id, 'name' => 'Shared Title']);

        $options = app(SongCatalogSearch::class)->options($mine->id, 'Shared');

        $this->assertCount(1, $options);
    }
}
