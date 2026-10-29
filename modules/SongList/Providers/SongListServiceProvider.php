<?php

namespace Modules\SongList\Providers;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Core\Permissions\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class SongListServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $modules, PermissionRegistry $permissions): void
    {
        $modules->register(new ModuleDefinition(
            key: 'song_list',
            name: 'Song list',
            description: 'A shared song catalog you can search when planning. Without it, planners can still type song titles by hand.',
            required: false,
        ));

        $permissions->register('song_list', [
            'song_list.view',
            'song_list.manage',
        ]);
    }
}
