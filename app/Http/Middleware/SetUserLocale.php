<?php

namespace App\Http\Middleware;

use App\Support\AvailableLocales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $available = AvailableLocales::codes();
        $fallback = config('app.fallback_locale', 'en');

        $user = $request->user();

        if ($user !== null) {
            $user->loadMissing('language');
            $iso = strtolower((string) ($user->language?->iso ?? ''));

            if ($iso !== '' && in_array($iso, $available, true)) {
                return $iso;
            }
        }

        // Prefer Accept-Language; Symfony returns the first $available entry when nothing matches.
        $preferred = $request->getPreferredLanguage($available);

        if (is_string($preferred) && $preferred !== '' && in_array($preferred, $available, true)) {
            return $preferred;
        }

        return in_array($fallback, $available, true) ? $fallback : 'en';
    }
}
