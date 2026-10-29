<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_response_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();
            $table->foreignId('event_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['event_task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_response_tokens');
    }
};
