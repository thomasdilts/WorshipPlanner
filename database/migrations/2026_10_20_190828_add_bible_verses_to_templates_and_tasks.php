<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->string('bible_usage', 20)->default('not_used')->after('file_usage');
        });

        Schema::table('event_tasks', function (Blueprint $table) {
            // List of {bible, ref, label} — verse text stays in the Zamar API.
            $table->json('bible_passages')->nullable()->after('special_needs');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn('bible_passages');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn('bible_usage');
        });
    }
};
