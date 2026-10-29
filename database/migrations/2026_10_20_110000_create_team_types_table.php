<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['church_id', 'name']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('team_type_id')
                ->nullable()
                ->after('church_id')
                ->constrained('team_types')
                ->restrictOnDelete();
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->foreignId('team_type_id')
                ->nullable()
                ->after('team_usage')
                ->constrained('team_types')
                ->nullOnDelete();
        });

        // Backfill: one default type per church that already has teams, then attach.
        $churchIds = DB::table('teams')->distinct()->pluck('church_id');

        foreach ($churchIds as $churchId) {
            $typeId = DB::table('team_types')->insertGetId([
                'church_id' => $churchId,
                'name' => 'General',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('teams')
                ->where('church_id', $churchId)
                ->whereNull('team_type_id')
                ->update(['team_type_id' => $typeId]);
        }

        // SQLite-friendly: recreate not-null via change if supported; otherwise leave nullable
        // and enforce in app. For MySQL/Postgres we can tighten:
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('team_type_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_type_id');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_type_id');
        });

        Schema::dropIfExists('team_types');
    }
};
