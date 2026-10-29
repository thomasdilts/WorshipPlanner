<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_task_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('responder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->string('method', 20)->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->text('note')->nullable();
            $table->string('legacy_source', 40)->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();

            $table->index(['event_task_id', 'occurred_at']);
            $table->unique(['church_id', 'legacy_source', 'legacy_id'], 'event_task_responses_legacy_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_task_responses');
    }
};
