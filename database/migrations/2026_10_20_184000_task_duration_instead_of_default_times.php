<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->unsignedInteger('default_duration')->default(5)->after('default_order');
        });

        foreach (DB::table('task_templates')->cursor() as $template) {
            $minutes = 5;

            if ($template->default_starts_at && $template->default_ends_at) {
                $start = strtotime((string) $template->default_starts_at);
                $end = strtotime((string) $template->default_ends_at);

                if ($start !== false && $end !== false && $end > $start) {
                    $minutes = max(1, (int) round(($end - $start) / 60));
                }
            }

            DB::table('task_templates')->where('id', $template->id)->update([
                'default_duration' => $minutes,
            ]);
        }

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn(['default_starts_at', 'default_ends_at']);
        });

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->unsignedInteger('duration')->nullable()->after('special_needs');
        });

        foreach (DB::table('event_tasks')->cursor() as $task) {
            $minutes = null;

            if ($task->starts_at && $task->ends_at) {
                $start = strtotime((string) $task->starts_at);
                $end = strtotime((string) $task->ends_at);

                if ($start !== false && $end !== false && $end > $start) {
                    $minutes = max(1, (int) round(($end - $start) / 60));
                }
            }

            if ($minutes === null) {
                $templateDuration = DB::table('task_templates')
                    ->where('id', $task->task_template_id)
                    ->value('default_duration');
                $minutes = max(1, (int) ($templateDuration ?? 5));
            }

            DB::table('event_tasks')->where('id', $task->id)->update([
                'duration' => $minutes,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->time('default_starts_at')->nullable()->after('default_order');
            $table->time('default_ends_at')->nullable()->after('default_starts_at');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn('default_duration');
        });

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
};
