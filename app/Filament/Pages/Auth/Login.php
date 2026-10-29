<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        // Heading lives in the login column so the aside can lead with its own title.
        return null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Group::make([
                            View::make('filament.auth.login-heading')
                                ->visible(fn (): bool => blank($this->userUndertakingMultiFactorAuthentication)),
                            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                            $this->getFormContentComponent(),
                            $this->getMultiFactorChallengeFormContentComponent(),
                            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
                        ]),
                        View::make('filament.auth.login-aside')
                            ->visible(fn (): bool => blank($this->userUndertakingMultiFactorAuthentication)),
                    ]),
            ]);
    }
}
