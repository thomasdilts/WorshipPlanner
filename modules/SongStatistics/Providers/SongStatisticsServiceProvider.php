<?php

namespace Modules\SongStatistics\Providers;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Core\Permissions\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class SongStatisticsServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $modules, PermissionRegistry $permissions): void
    {
        $modules->register(new ModuleDefinition(
            key: 'song_statistics',
            name: 'Song statistics',
            description: 'See which songs have been used recently. Helpful if you want to vary the repertoire.',
            required: false,
        ));

        $permissions->register('song_statistics', [
            'song_statistics.view',
        ]);
    }
}
