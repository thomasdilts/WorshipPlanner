<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->boolean('use_globally')->default(false)->after('default_ends_at');
            $table->unsignedInteger('default_global_order')->default(0)->after('use_globally');
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn(['use_globally', 'default_global_order']);
        });
    }
};
