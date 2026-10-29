<?php

namespace Tests\Feature\Events;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\Team;
use App\Models\User;
use App\Services\LegacyFreeTextReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyFreeTextReconcilerTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->event = Event::factory()->create(['church_id' => $this->church->id]);
    }

    private function person(string $name, ?Church $church = null, ?string $display = null): User
    {
        return User::factory()->create(['church_id' => ($church ?? $this->church)->id, 'name' => $name, 'display_name' => $display ?? explode(' ', $name)[0]]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function task(string $description, array $attributes = [], ?Event $event = null): EventTask
    {
        return EventTask::factory()->create($attributes + [
            'event_id' => ($event ?? $this->event)->id,
            'description' => $description,
        ]);
    }

    private function plan(array $mapping = []): array
    {
        return app(LegacyFreeTextReconciler::class)->plan($this->church->id, $mapping);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function itemsByKey(array $plan): array
    {
        return collect($plan['items'])->keyBy(fn (array $item): string => $item['kind'].':'.$item['text'])->all();
    }

    public function test_exact_unique_full_name_is_linked_and_the_original_text_is_kept(): void
    {
        $emilia = $this->person('Emilia Nilsson');
        $task = $this->task("Bring the guitar\nPerson: Emilia  nilsson");
        $reconciler = app(LegacyFreeTextReconciler::class);

        $this->assertSame(1, $reconciler->apply($reconciler->plan($this->church->id)));

        $task->refresh();
        $this->assertSame($emilia->id, $task->assigned_user_id);
        $this->assertSame('Emilia  nilsson', $task->legacy_person_text);
        $this->assertSame('Bring the guitar', $task->description);
    }

    public function test_first_name_prefix_accent_and_display_name_matches_are_candidates_never_links(): void
    {
        $reine = $this->person('Reine Nkurikiye');
        $this->person('Åsa Lindqvist', display: 'Åsa');
        $task = $this->task("Person: Reine\nPerson: Asa Lindqvist");

        $items = $this->itemsByKey($this->plan());

        $this->assertSame(LegacyFreeTextReconciler::CANDIDATES, $items['person:Reine']['outcome']);
        $this->assertSame($reine->id, $items['person:Reine']['candidates'][0]['id']);
        $this->assertSame(LegacyFreeTextReconciler::CANDIDATES, $items['person:Asa Lindqvist']['outcome']);

        $reconciler = app(LegacyFreeTextReconciler::class);
        $this->assertSame(0, $reconciler->apply($reconciler->plan($this->church->id)));
        $this->assertNull($task->fresh()->assigned_user_id);
        $this->assertStringContainsString('Person: Reine', $task->fresh()->description);
    }

    public function test_two_people_with_the_same_name_are_ambiguous(): void
    {
        $this->person('Anna Berg');
        $this->person('Anna Berg');
        $this->task('Person: Anna Berg');

        $this->assertSame(LegacyFreeTextReconciler::AMBIGUOUS, $this->itemsByKey($this->plan())['person:Anna Berg']['outcome']);
    }

    public function test_an_approved_mapping_links_a_candidate(): void
    {
        $reine = $this->person('Reine Nkurikiye');
        $task = $this->task('Person: Reine');
        $reconciler = app(LegacyFreeTextReconciler::class);

        $plan = $reconciler->plan($this->church->id, ['person:Reine' => $reine->id]);
        $reconciler->apply($plan);

        $this->assertSame(LegacyFreeTextReconciler::LINK_APPROVED, $this->itemsByKey($plan)['person:Reine']['outcome']);
        $this->assertSame($reine->id, $task->fresh()->assigned_user_id);
        $this->assertSame('Reine', $task->fresh()->legacy_person_text);
        $this->assertNull($task->fresh()->description);
    }

    public function test_other_church_people_are_never_matched_or_linked(): void
    {
        $otherChurch = Church::factory()->create();
        $outsider = $this->person('Noah Ernvik', $otherChurch);
        $task = $this->task('Person: Noah Ernvik');
        $reconciler = app(LegacyFreeTextReconciler::class);

        $plan = $reconciler->plan($this->church->id, ['person:Noah Ernvik' => $outsider->id]);
        $reconciler->apply($plan);

        $this->assertSame(LegacyFreeTextReconciler::NO_MATCH, $this->itemsByKey($plan)['person:Noah Ernvik']['outcome']);
        $this->assertNull($task->fresh()->assigned_user_id);

        $foreignEvent = Event::factory()->create(['church_id' => $otherChurch->id]);
        $this->person('Edit Carling');
        $foreignTask = $this->task('Person: Edit Carling', event: $foreignEvent);
        $reconciler->apply($reconciler->plan($this->church->id));
        $this->assertNull($foreignTask->fresh()->assigned_user_id, 'Tasks of another church are out of scope.');
    }

    public function test_existing_assignments_are_never_overwritten(): void
    {
        $kept = $this->person('Existing Person');
        $this->person('Kanske Robin');
        $task = $this->task('Person: Kanske Robin', ['assigned_user_id' => $kept->id]);
        $reconciler = app(LegacyFreeTextReconciler::class);

        $plan = $reconciler->plan($this->church->id);
        $reconciler->apply($plan);

        $this->assertSame(1, $plan['totals']['skipped_already_assigned']);
        $this->assertSame($kept->id, $task->fresh()->assigned_user_id);
        $this->assertStringContainsString('Person: Kanske Robin', $task->fresh()->description);
    }

    public function test_team_names_link_only_on_exact_unique_match(): void
    {
        $team = Team::factory()->create(['church_id' => $this->church->id, 'name' => 'Värdgrupp']);
        $linked = $this->task('Team: värdgrupp');
        $unlinked = $this->task('Team: Linus & Edit');
        $reconciler = app(LegacyFreeTextReconciler::class);

        $reconciler->apply($reconciler->plan($this->church->id));

        $this->assertSame($team->id, $linked->fresh()->assigned_team_id);
        $this->assertSame('värdgrupp', $linked->fresh()->legacy_team_text);
        $this->assertNull($unlinked->fresh()->assigned_team_id);
    }

    public function test_command_is_dry_run_by_default_and_reruns_are_idempotent(): void
    {
        Storage::fake('local');
        $person = $this->person('Mika Kosonen');
        $task = $this->task('Person: Mika Kosonen');

        $this->artisan('worship:reconcile-legacy-names', ['--church' => $this->church->id])->assertSuccessful();
        $this->assertNull($task->fresh()->assigned_user_id);

        $this->artisan('worship:reconcile-legacy-names', ['--church' => $this->church->id, '--apply' => true])->assertSuccessful();
        $this->assertSame($person->id, $task->fresh()->assigned_user_id);

        $this->artisan('worship:reconcile-legacy-names', ['--church' => $this->church->id, '--apply' => true])->assertSuccessful();
        $this->assertSame($person->id, $task->fresh()->assigned_user_id);
        $this->assertCount(1, Storage::disk('local')->allFiles('legacy-name-links'));
    }

    public function test_command_requires_a_church_and_a_readable_mapping_file(): void
    {
        $this->artisan('worship:reconcile-legacy-names')->assertFailed();
        $this->artisan('worship:reconcile-legacy-names', ['--church' => $this->church->id, '--mapping' => '/nonexistent.json'])->assertFailed();
    }
}
