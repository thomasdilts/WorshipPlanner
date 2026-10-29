<?php

namespace Tests\Feature\Baseline;

use App\Models\Church;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use App\Models\Unavailability;
use App\Models\User;
use Database\Seeders\SandboxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SandboxSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_the_scenarios_the_redesign_phases_depend_on(): void
    {
        $this->seed(SandboxSeeder::class);

        $this->assertSame(2, Church::query()->count());
        $this->assertSame(1, User::query()->where('is_platform_admin', true)->count());

        $vacantRequired = EventTask::query()
            ->whereNull('assigned_user_id')
            ->whereHas('template', fn ($query) => $query->where('person_usage', 'required'))
            ->count();
        $this->assertSame(1, $vacantRequired);

        $conflicts = Unavailability::query()->get()->filter(
            fn (Unavailability $away): bool => EventTask::query()
                ->where('assigned_user_id', $away->user_id)
                ->whereHas('event', fn ($query) => $query->whereBetween('starts_at', [$away->starts_on->startOfDay(), $away->ends_on->endOfDay()]))
                ->exists()
        );
        $this->assertCount(1, $conflicts);

        $months = EventTask::query()->with('event')->get()->map(fn (EventTask $task): string => $task->event->starts_at->format('Y-m'))->unique();
        $this->assertGreaterThanOrEqual(2, $months->count());

        $statuses = EventTaskResponse::query()->pluck('status')->map(fn ($status): string => $status->value)->unique()->sort()->values()->all();
        $this->assertSame(['confirmed', 'declined', 'invited'], $statuses);
        $this->assertSame(1, EventTaskResponse::query()->where('method', 'verbal')->count());
    }

    public function test_it_refuses_to_run_outside_sandbox_or_testing(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);

        (new SandboxSeeder)->run();
    }
}
