<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('display_name')->nullable()->after('name');
            $table->boolean('is_platform_admin')->default(false)->after('password');
            $table->dropUnique(['email']);
            $table->unique(['church_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['church_id', 'email']);
            $table->unique('email');
            $table->dropConstrainedForeignId('church_id');
            $table->dropColumn(['display_name', 'is_platform_admin']);
        });
    }
};
