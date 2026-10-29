<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentState;
use App\Models\AssignmentResponseToken;
use App\Models\EventTask;
use App\Models\User;
use App\Services\AssignmentResponder;
use App\Services\AssignmentResponseLink;
use App\Services\AssignmentStateResolver;
use App\Support\AvailableLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class AssignmentResponseController extends Controller
{
    /**
     * Signed GET — preview only. Never mutates assignment state.
     */
    public function show(Request $request, EventTask $task, User $user): View|RedirectResponse|Response
    {
        abort_unless($request->hasValidSignature(), 403);

        $this->applyInviteeLocale($user);

        $task->loadMissing(['event.church', 'template', 'assignedUser', 'assignedTeam']);

        if (! $this->linkStillValid($task, $user)) {
            return response()->view('assignments.respond-invalid', [
                'reason' => __('This invitation is no longer valid.'),
            ], 410);
        }

        $state = app(AssignmentStateResolver::class)->for($task);

        return view('assignments.respond', [
            'task' => $task,
            'user' => $user,
            'state' => $state,
            'storeUrl' => app(AssignmentResponseLink::class)->storeUrl($task, $user),
        ]);
    }

    /**
     * Signed POST — intentional accept / decline.
     */
    public function store(Request $request, EventTask $task, User $user): RedirectResponse|View|Response
    {
        abort_unless($request->hasValidSignature(), 403);

        $this->applyInviteeLocale($user);

        $task->loadMissing(['event', 'assignedUser', 'assignedTeam']);

        if (! $this->linkStillValid($task, $user)) {
            return response()->view('assignments.respond-invalid', [
                'reason' => __('This invitation is no longer valid.'),
            ], 410);
        }

        return $this->record(
            $request,
            $task,
            $user,
            app(AssignmentResponseLink::class)->showUrl($task, $user),
        );
    }

    /**
     * Short-link response page (no login, no signature — the token is the secret).
     */
    public function showByToken(string $token): View|Response
    {
        $responseToken = $this->resolveToken($token);

        if ($responseToken instanceof Response) {
            return $responseToken;
        }

        $task = $responseToken->task->loadMissing(['event.church', 'template', 'assignedUser', 'assignedTeam']);

        return view('assignments.respond', [
            'task' => $task,
            'user' => $responseToken->user,
            'state' => app(AssignmentStateResolver::class)->for($task),
            'storeUrl' => route('assignments.quick.store', ['token' => $token]),
        ]);
    }

    /**
     * Target of the accept / decline links in messages. Opening it changes nothing: the answer is only
     * saved when the person presses the button, because mail scanners and chat previews open every link.
     */
    public function answerByToken(string $token, string $answer): View|Response
    {
        $responseToken = $this->resolveToken($token);

        if ($responseToken instanceof Response) {
            return $responseToken;
        }

        $task = $responseToken->task->loadMissing(['event.church', 'template', 'assignedTeam']);

        return view('assignments.respond-submit', [
            'task' => $task,
            'decision' => $answer === 'yes' ? 'confirm' : 'decline',
            'storeUrl' => route('assignments.quick.store', ['token' => $token]),
        ]);
    }

    public function storeByToken(Request $request, string $token): RedirectResponse|View|Response
    {
        $responseToken = $this->resolveToken($token);

        if ($responseToken instanceof Response) {
            return $responseToken;
        }

        return $this->record(
            $request,
            $responseToken->task,
            $responseToken->user,
            route('assignments.quick.answer', [
                'token' => $token,
                'answer' => $request->input('decision') === 'confirm' ? 'yes' : 'no',
            ]),
        );
    }

    protected function record(Request $request, EventTask $task, User $user, string $redirectTo): RedirectResponse|View|Response
    {
        $data = $request->validate([
            'decision' => ['required', 'in:confirm,decline'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $responder = app(AssignmentResponder::class);

        try {
            if ($data['decision'] === 'confirm') {
                $responder->confirm($task, $user);
            } else {
                $responder->decline($task, $user, $data['note'] ?? null);
            }
        } catch (\Throwable $e) {
            return response()->view('assignments.respond-invalid', [
                'reason' => $e->getMessage() ?: __('Could not save your response.'),
            ], 422);
        }

        $state = app(AssignmentStateResolver::class)->for($task->fresh());

        return redirect()
            ->to($redirectTo)
            ->with('status', $state === AssignmentState::Confirmed
                ? __('Thanks — you\'re confirmed')
                : __('Response recorded'));
    }

    protected function resolveToken(string $token): AssignmentResponseToken|Response
    {
        $responseToken = AssignmentResponseToken::query()
            ->with(['task.event', 'user'])
            ->where('token', strtolower($token))
            ->first();

        abort_if($responseToken === null, 404);

        $this->applyInviteeLocale($responseToken->user);

        if ($responseToken->isExpired() || ! $this->linkStillValid($responseToken->task, $responseToken->user)) {
            return response()->view('assignments.respond-invalid', [
                'reason' => __('This invitation is no longer valid.'),
            ], 410);
        }

        return $responseToken;
    }

    protected function linkStillValid(EventTask $task, User $user): bool
    {
        if ($task->event === null) {
            return false;
        }

        if ((int) $task->event->church_id !== (int) $user->church_id) {
            return false;
        }

        if (filled($task->assigned_user_id) && (int) $task->assigned_user_id === (int) $user->id) {
            return true;
        }

        if (filled($task->assigned_team_id)) {
            $task->loadMissing('assignedTeam');

            return $task->assignedTeam?->isLeader($user) ?? false;
        }

        return false;
    }

    /**
     * Signed links are often opened logged-out; prefer the invitee's UI language.
     */
    protected function applyInviteeLocale(User $user): void
    {
        $user->loadMissing('language');
        $iso = strtolower((string) ($user->language?->iso ?? ''));

        if ($iso !== '' && in_array($iso, AvailableLocales::codes(), true)) {
            App::setLocale($iso);
        }
    }
}
