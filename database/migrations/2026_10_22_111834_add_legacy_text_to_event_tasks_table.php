<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('legacy_person_text')->nullable()->after('assigned_team_id');
            $table->string('legacy_team_text')->nullable()->after('legacy_person_text');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn(['legacy_person_text', 'legacy_team_text']);
        });
    }
};
