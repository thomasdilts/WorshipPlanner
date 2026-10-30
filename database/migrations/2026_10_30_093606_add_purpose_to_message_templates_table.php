<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('purpose', 20)->default('service')->after('key');
        });

        DB::table('message_templates')->where('key', 'team_custom_message')->update(['purpose' => 'team']);
        DB::table('message_templates')->where('key', 'preferred_messageing')->update(['purpose' => 'other']);
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
