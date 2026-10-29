<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name2')->nullable();
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['church_id', 'name']);
            $table->index(['church_id', 'author']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->boolean('allow_manual_song_entry')->default(false)->after('song_usage');
        });

        Schema::table('event_tasks', function (Blueprint $table) {
            $table->foreignId('song_id')
                ->nullable()
                ->after('song')
                ->constrained('songs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('song_id');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropColumn('allow_manual_song_entry');
        });

        Schema::dropIfExists('songs');
    }
};
