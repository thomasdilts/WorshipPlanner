<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Accepted verbally" becomes a setting on the task that overrides any message response.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->timestamp('accepted_verbally_at')->nullable()->after('assigned_team_id');
            $table->foreignId('accepted_verbally_by_user_id')->nullable()->after('accepted_verbally_at')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('event_tasks')
            ->where(fn ($query) => $query->whereNotNull('assigned_user_id')->orWhereNotNull('assigned_team_id'))
            ->orderBy('id')
            ->each(function (object $task): void {
                $latest = DB::table('event_task_responses')
                    ->where('event_task_id', $task->id)
                    ->where('status', '!=', 'notified')
                    ->when(
                        $task->assigned_user_id !== null,
                        fn ($query) => $query->where('assignee_user_id', $task->assigned_user_id),
                        fn ($query) => $query->where('assignee_team_id', $task->assigned_team_id),
                    )
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->first();

                if ($latest?->status === 'confirmed' && $latest->method === 'verbal') {
                    DB::table('event_tasks')->where('id', $task->id)->update([
                        'accepted_verbally_at' => $latest->occurred_at,
                        'accepted_verbally_by_user_id' => $latest->recorded_by_user_id,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_verbally_by_user_id');
            $table->dropColumn('accepted_verbally_at');
        });
    }
};
