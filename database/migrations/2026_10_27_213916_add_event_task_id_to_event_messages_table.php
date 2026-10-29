<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An event message is now about one task for one person; older messages keep event_task_id null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->foreignId('event_task_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_task_id');
        });
    }
};
