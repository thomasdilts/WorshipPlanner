<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'name']);
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'is_leader']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->boolean('allows_team')->default(false)->after('requires_assignee');
        });

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->foreignId('assigned_team_id')
                ->nullable()
                ->after('assigned_user_id')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_team_id');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn('allows_team');
        });

        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
