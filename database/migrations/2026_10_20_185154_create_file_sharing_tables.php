<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'name']);
        });

        Schema::create('shared_file_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_file_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'shared_file_id']);
        });

        Schema::create('event_task_shared_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_task_id', 'shared_file_id']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->string('special_needs_usage', 20)->default('optional')->after('description_usage');
            $table->string('file_usage', 20)->default('not_used')->after('picture_usage');
        });
    }

    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn(['special_needs_usage', 'file_usage']);
        });

        Schema::dropIfExists('event_task_shared_file');
        Schema::dropIfExists('shared_file_team');
        Schema::dropIfExists('shared_files');
    }
};
