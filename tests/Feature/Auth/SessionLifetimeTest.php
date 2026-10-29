<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionLifetimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_keeps_only_the_most_recent_sessions_per_user(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach (range(1, 6) as $minutesAgo) {
            $this->insertSession("user-{$minutesAgo}", $user->id, now()->subMinutes($minutesAgo)->timestamp);
        }
        $this->insertSession('other-user', $otherUser->id, now()->subYear()->timestamp);

        event(new Login('web', $user, false));

        $this->assertSame(
            ['user-1', 'user-2', 'user-3', 'user-4'],
            DB::table('sessions')->where('user_id', $user->id)->orderBy('id')->pluck('id')->all(),
        );
        $this->assertDatabaseHas('sessions', ['id' => 'other-user']);
    }

    private function insertSession(string $id, int $userId, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => $lastActivity,
        ]);
    }
}
