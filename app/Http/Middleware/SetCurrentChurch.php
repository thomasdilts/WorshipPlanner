<?php

namespace App\Http\Middleware;

use App\Core\Tenancy\CurrentChurch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentChurch
{
    public function __construct(
        protected CurrentChurch $currentChurch,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->church_id) {
            $this->currentChurch->set($user->church);
        } else {
            $this->currentChurch->forget();
        }

        return $next($request);
    }
}
