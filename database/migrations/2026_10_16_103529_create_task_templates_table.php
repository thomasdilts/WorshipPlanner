<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_order')->default(0);
            $table->time('default_starts_at')->nullable();
            $table->time('default_ends_at')->nullable();
            $table->boolean('requires_assignee')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['church_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
