<?php

use Modules\Abilities\Providers\AbilitiesServiceProvider;
use Modules\BibleVerses\Providers\BibleVersesServiceProvider;
use Modules\Events\Providers\EventsServiceProvider;
use Modules\FileSharing\Providers\FileSharingServiceProvider;
use Modules\Messaging\Providers\MessagingServiceProvider;
use Modules\Pictures\Providers\PicturesServiceProvider;
use Modules\SongList\Providers\SongListServiceProvider;
use Modules\SongStatistics\Providers\SongStatisticsServiceProvider;
use Modules\Teams\Providers\TeamsServiceProvider;
use Modules\Unavailability\Providers\UnavailabilityServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module service providers
    |--------------------------------------------------------------------------
    |
    | Feature modules live under /modules and register themselves here.
    | Required Core capabilities stay in app/Core and are always loaded.
    |
    */

    'providers' => [
        EventsServiceProvider::class,
        MessagingServiceProvider::class,
        UnavailabilityServiceProvider::class,
        TeamsServiceProvider::class,
        PicturesServiceProvider::class,
        FileSharingServiceProvider::class,
        BibleVersesServiceProvider::class,
        SongListServiceProvider::class,
        SongStatisticsServiceProvider::class,
        AbilitiesServiceProvider::class,
        // Modules\Scheduling\Providers\SchedulingServiceProvider::class,
        // Modules\Media\Providers\MediaServiceProvider::class,
    ],

];
