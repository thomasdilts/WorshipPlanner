<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pictures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('path');
            $table->timestamps();

            $table->index(['church_id', 'name']);
        });

        Schema::create('event_task_picture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('picture_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_task_id', 'picture_id']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->string('picture_usage', 20)->default('not_used')->after('song_usage');
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn('picture_usage');
        });

        Schema::dropIfExists('event_task_picture');
        Schema::dropIfExists('pictures');
    }
};
