<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });

        Schema::create('event_message_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_message_id')->constrained('event_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('delivery_status', 20)->default('pending');
            $table->text('delivery_error')->nullable();
            $table->timestamps();

            $table->unique(['event_message_id', 'user_id']);
        });

        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('church_id')->constrained()->nullOnDelete();
            $table->foreignId('event_message_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_message_id');
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::dropIfExists('event_message_user');
        Schema::dropIfExists('event_messages');
    }
};
