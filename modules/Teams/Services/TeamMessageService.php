<?php

namespace Modules\Teams\Services;

use App\Core\Modules\ChurchModuleManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Services\MessageSender;
use Modules\Messaging\Support\MessagingAvailability;

class TeamMessageService
{
    public function __construct(
        protected MessageSender $sender,
        protected ChurchModuleManager $modules,
    ) {}

    /**
     * @param  list<int>  $recipientUserIds
     * @return array{sent: int, failed: int}
     */
    public function send(
        Team $team,
        User $author,
        string $body,
        ?string $subject,
        array $recipientUserIds,
    ): array {
        if (! $this->modules->isEnabled($team->church, 'teams')) {
            throw new \InvalidArgumentException('Teams module is not enabled.');
        }

        if (! app(MessagingAvailability::class)->anyEnabled($team->church)) {
            throw new \InvalidArgumentException('No messaging module is enabled.');
        }

        if (! $team->isMember($author) && ! $author->can('teams.manage')) {
            throw new \InvalidArgumentException('Only team members can message this team.');
        }

        $allowedIds = $team->members()->pluck('users.id')->all();
        $recipientUserIds = array_values(array_intersect(
            array_map('intval', $recipientUserIds),
            $allowedIds
        ));

        if ($recipientUserIds === []) {
            throw new \InvalidArgumentException('Select at least one team member.');
        }

        $sent = 0;
        $failed = 0;

        $recipients = User::query()->whereIn('id', $recipientUserIds)->get();

        foreach ($recipients as $recipient) {
            $result = $this->sender->sendToUser(
                $recipient,
                new OutboundMessageData(
                    subject: $subject ?: ('Team: '.$team->name),
                    body: $body,
                    templateKey: null,
                    meta: [
                        'team_id' => $team->id,
                        'from_user_id' => $author->id,
                    ],
                ),
            );

            $result->success ? $sent++ : $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Resolve event-message team recipients → leader user ids.
     *
     * @param  list<int>  $teamIds
     * @return list<int>
     */
    public function leaderIdsForTeams(Collection|array $teamIds): array
    {
        $ids = collect($teamIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Team::query()
            ->whereIn('id', $ids)
            ->with('leaders')
            ->get()
            ->flatMap(fn (Team $team) => $team->leaders->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
