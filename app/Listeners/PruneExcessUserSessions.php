<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Keeps each user logged in on at most {@see self::MAX_DEVICES} devices by
 * deleting their least recently used database sessions on a new login.
 */
class PruneExcessUserSessions
{
    public const MAX_DEVICES = 5;

    public function handle(Login $event): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $sessions = DB::connection(config('session.connection'))->table(config('session.table'));

        // The session for this login is persisted at the end of the request,
        // so leave room for it among the user's existing sessions.
        $staleSessionIds = (clone $sessions)
            ->where('user_id', $event->user->getAuthIdentifier())
            ->where('id', '!=', session()->getId())
            ->orderByDesc('last_activity')
            ->skip(self::MAX_DEVICES - 1)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($staleSessionIds->isNotEmpty()) {
            $sessions->whereIn('id', $staleSessionIds)->delete();
        }
    }
}
