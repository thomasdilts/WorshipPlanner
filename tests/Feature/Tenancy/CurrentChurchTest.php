<?php

namespace Tests\Feature\Tenancy;

use App\Core\Tenancy\CurrentChurch;
use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentChurchTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_current_church_sets_spatie_team_id(): void
    {
        $church = Church::factory()->create();
        $current = app(CurrentChurch::class);

        $current->set($church);

        $this->assertTrue($current->check());
        $this->assertSame($church->id, $current->id());
        $this->assertSame(
            $church->id,
            app(\Spatie\Permission\PermissionRegistrar::class)->getPermissionsTeamId()
        );
    }

    public function test_users_belong_to_separate_churches(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();

        $userA = User::factory()->create(['church_id' => $churchA->id, 'email' => 'a@test.com']);
        $userB = User::factory()->create(['church_id' => $churchB->id, 'email' => 'a@test.com']);

        $this->assertNotSame($userA->church_id, $userB->church_id);
        $this->assertSame('a@test.com', $userA->email);
        $this->assertSame('a@test.com', $userB->email);
    }
}
