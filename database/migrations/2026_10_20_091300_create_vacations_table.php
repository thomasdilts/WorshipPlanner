<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historically named create_vacations_table; creates unavailabilities.
     */
    public function up(): void
    {
        if (Schema::hasTable('unavailabilities')) {
            return;
        }

        // Local DBs that already ran the original vacations create are upgraded
        // by 2026_10_20_200000_rename_vacations_to_unavailabilities.
        if (Schema::hasTable('vacations')) {
            return;
        }

        // team_id is added later in 2026_10_20_165254_* after teams exists.
        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'user_id']);
            $table->index(['user_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
