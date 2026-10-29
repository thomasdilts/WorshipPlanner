<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One template serves every channel: HTML body for email, text body for SMS / WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->text('text_body')->nullable()->after('body');
        });

        DB::table('message_templates')
            ->whereIn('channel', ['sms', 'whatsapp'])
            ->update([
                'text_body' => DB::raw('body'),
                'body' => null,
            ]);

        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex(['church_id', 'channel']);
            $table->dropColumn('channel');
        });

        Schema::table('event_messages', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->text('text_body')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->dropColumn('text_body');
        });

        DB::table('event_messages')->whereNull('body')->update(['body' => '']);

        Schema::table('event_messages', function (Blueprint $table) {
            $table->text('body')->nullable(false)->change();
        });

        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('channel', 20)->default('email')->after('key');
            $table->index(['church_id', 'channel']);
        });

        DB::table('message_templates')
            ->whereNull('body')
            ->update([
                'channel' => 'sms',
                'body' => DB::raw('text_body'),
            ]);

        DB::table('message_templates')->whereNull('body')->update(['body' => '']);

        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn('text_body');
            $table->text('body')->nullable(false)->change();
        });
    }
};
