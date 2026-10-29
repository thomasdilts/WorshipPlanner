<?php

namespace App\Support;

use App\Models\User;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Panel fallback avatar rendered locally as an inline SVG: no external request and no broken image.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initials = $record instanceof User ? $record->avatarInitials() : '?';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#e8f4f1"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="26" font-weight="700" fill="#115e59">'
            .e($initials)
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
