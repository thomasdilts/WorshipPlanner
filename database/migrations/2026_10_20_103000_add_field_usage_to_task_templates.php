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
            $table->string('person_usage', 20)->default('optional')->after('default_ends_at');
            $table->string('team_usage', 20)->default('not_used')->after('person_usage');
            $table->string('description_usage', 20)->default('optional')->after('team_usage');
            $table->string('song_usage', 20)->default('not_used')->after('description_usage');
        });

        if (Schema::hasColumn('task_templates', 'requires_assignee')) {
            DB::table('task_templates')->orderBy('id')->each(function (object $row): void {
                DB::table('task_templates')->where('id', $row->id)->update([
                    'person_usage' => $row->requires_assignee ? 'required' : 'optional',
                    'team_usage' => (! empty($row->allows_team ?? false)) ? 'optional' : 'not_used',
                ]);
            });

            Schema::table('task_templates', function (Blueprint $table) {
                $table->dropColumn(['requires_assignee', 'allows_team']);
            });
        }

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('song')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn('song');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->boolean('requires_assignee')->default(false)->after('default_ends_at');
            $table->boolean('allows_team')->default(false)->after('requires_assignee');
        });

        DB::table('task_templates')->orderBy('id')->each(function (object $row): void {
            DB::table('task_templates')->where('id', $row->id)->update([
                'requires_assignee' => ($row->person_usage ?? '') === 'required',
                'allows_team' => in_array($row->team_usage ?? '', ['optional', 'required'], true),
            ]);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn(['person_usage', 'team_usage', 'description_usage', 'song_usage']);
        });
    }
};
