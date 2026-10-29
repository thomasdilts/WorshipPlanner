<?php

namespace Tests\Feature\Baseline;

use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Songs\Pages\ListSongs;
use App\Filament\Resources\Teams\Pages\ListTeams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\TwoChurchFixture;
use Tests\TestCase;

class TwoChurchAccessTest extends TestCase
{
    use RefreshDatabase;

    private TwoChurchFixture $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = TwoChurchFixture::create($this);
    }

    public function test_church_admin_lists_only_their_own_churchs_records(): void
    {
        $this->actingAs($this->fixture->adminA);
        app(CurrentChurch::class)->set($this->fixture->churchA);

        Livewire::test(ListEvents::class)
            ->assertCanSeeTableRecords([$this->fixture->eventA])
            ->assertCanNotSeeTableRecords([$this->fixture->eventB]);

        Livewire::test(ListTeams::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$this->fixture->teamA])
            ->assertCanNotSeeTableRecords([$this->fixture->teamB]);

        Livewire::test(ListSongs::class)
            ->assertCanSeeTableRecords([$this->fixture->songA])
            ->assertCanNotSeeTableRecords([$this->fixture->songB]);
    }

    public function test_church_admin_cannot_open_another_churchs_records_by_url(): void
    {
        $this->actingAs($this->fixture->adminA);

        foreach ([
            "/admin/events/{$this->fixture->eventA->id}/edit-details",
            "/admin/teams/{$this->fixture->teamA->id}",
            "/admin/songs/{$this->fixture->songA->id}/edit",
        ] as $ownUrl) {
            $this->get($ownUrl)->assertOk();
        }

        foreach ([
            "/admin/events/{$this->fixture->eventB->id}/edit",
            "/admin/events/{$this->fixture->eventB->id}/edit-details",
            "/admin/teams/{$this->fixture->teamB->id}",
            "/admin/songs/{$this->fixture->songB->id}/edit",
        ] as $url) {
            $this->assertContains($this->get($url)->getStatusCode(), [403, 404], $url);
        }
    }

    public function test_church_admin_cannot_see_church_management_but_platform_admin_can(): void
    {
        $this->actingAs($this->fixture->adminA);
        $this->assertFalse(ChurchResource::canViewAny());

        $this->actingAs($this->fixture->platformAdmin);
        $this->assertTrue(ChurchResource::canViewAny());
    }

    public function test_platform_admin_has_no_access_to_church_operational_data(): void
    {
        $this->actingAs($this->fixture->platformAdmin);

        $this->get('/admin/events')->assertForbidden();
        $this->get("/admin/events/{$this->fixture->eventA->id}/edit")->assertStatus(404);
    }

    public function test_plain_member_cannot_manage_events_in_their_own_church(): void
    {
        $this->actingAs($this->fixture->memberA);

        $this->get('/admin/events/create')->assertForbidden();
    }
}
