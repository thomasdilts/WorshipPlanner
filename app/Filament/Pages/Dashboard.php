<?php

namespace App\Filament\Pages;

use App\Filament\Navigation\LandingDestination;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $destination = app(LandingDestination::class)->url();

        if ($destination !== null) {
            $this->redirect($destination, navigate: false);
        }
    }
}
