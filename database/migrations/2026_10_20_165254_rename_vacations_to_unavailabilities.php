<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vacations') && ! Schema::hasTable('unavailabilities')) {
            Schema::rename('vacations', 'unavailabilities');
        }

        if (Schema::hasTable('unavailabilities') && ! Schema::hasColumn('unavailabilities', 'team_id')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $this->rebuildSqliteUnavailabilitiesTable();
            } else {
                Schema::table('unavailabilities', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->change();
                    $table->foreignId('team_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained()
                        ->cascadeOnDelete();
                    $table->index(['church_id', 'team_id']);
                    $table->index(['team_id', 'starts_on', 'ends_on']);
                });
            }
        }

        if (Schema::hasTable('church_modules')) {
            DB::table('church_modules')
                ->where('module_key', 'vacations')
                ->update(['module_key' => 'unavailability']);
        }

        $this->renamePermissions([
            'vacations.view' => 'unavailability.view',
            'vacations.manage_own' => 'unavailability.manage_own',
            'vacations.manage' => 'unavailability.manage',
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('church_modules')) {
            DB::table('church_modules')
                ->where('module_key', 'unavailability')
                ->update(['module_key' => 'vacations']);
        }

        $this->renamePermissions([
            'unavailability.view' => 'vacations.view',
            'unavailability.manage_own' => 'vacations.manage_own',
            'unavailability.manage' => 'vacations.manage',
        ]);

        if (Schema::hasTable('unavailabilities') && Schema::hasColumn('unavailabilities', 'team_id')) {
            Schema::table('unavailabilities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_id');
            });
        }

        if (Schema::hasTable('unavailabilities') && ! Schema::hasTable('vacations')) {
            Schema::rename('unavailabilities', 'vacations');
        }
    }

    /**
     * @param  array<string, string>  $map
     */
    protected function renamePermissions(array $map): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ($map as $from => $to) {
            $existing = DB::table('permissions')->where('name', $to)->where('guard_name', 'web')->first();
            $old = DB::table('permissions')->where('name', $from)->where('guard_name', 'web')->first();

            if ($old === null) {
                continue;
            }

            if ($existing === null) {
                DB::table('permissions')->where('id', $old->id)->update(['name' => $to]);

                continue;
            }

            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $old->id)
                    ->update(['permission_id' => $existing->id]);
            }

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')
                    ->where('permission_id', $old->id)
                    ->update(['permission_id' => $existing->id]);
            }

            DB::table('permissions')->where('id', $old->id)->delete();
        }
    }

    protected function rebuildSqliteUnavailabilitiesTable(): void
    {
        Schema::create('unavailabilities_tmp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'user_id']);
            $table->index(['church_id', 'team_id']);
            $table->index(['user_id', 'starts_on', 'ends_on']);
            $table->index(['team_id', 'starts_on', 'ends_on']);
        });

        $rows = DB::table('unavailabilities')->get();

        foreach ($rows as $row) {
            DB::table('unavailabilities_tmp')->insert([
                'id' => $row->id,
                'church_id' => $row->church_id,
                'user_id' => $row->user_id,
                'team_id' => null,
                'starts_on' => $row->starts_on,
                'ends_on' => $row->ends_on,
                'note' => $row->note,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('unavailabilities');
        Schema::rename('unavailabilities_tmp', 'unavailabilities');
    }
};
