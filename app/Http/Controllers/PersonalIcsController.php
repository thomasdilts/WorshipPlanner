<?php

namespace App\Http\Controllers;

use App\Services\PersonalIcsExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalIcsController extends Controller
{
    public function __invoke(PersonalIcsExporter $exporter): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);
        abort_unless($user->can('events.view'), 403);

        return $exporter->download($user->loadMissing('church'));
    }
}
