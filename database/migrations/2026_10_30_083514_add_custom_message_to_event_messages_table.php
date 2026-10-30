<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->text('custom_message')->nullable()->after('text_body');
        });
    }

    public function down(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->dropColumn('custom_message');
        });
    }
};
