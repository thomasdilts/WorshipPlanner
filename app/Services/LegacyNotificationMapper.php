<?php

namespace App\Services;

use App\Enums\AssignmentResponseMethod;
use App\Enums\AssignmentResponseStatus;
use Illuminate\Support\Carbon;

/**
 * Turns rows of the legacy `notification` table into event_task_responses rows.
 *
 * Legacy statuses: "Not replied yet", "Accepted", "Rejected", "No reply requested".
 * Verbal acceptance is not a status there: it is an "Accepted" row without a reply date that was
 * written from the "accepted verbally" message template, with the coordinator in user_from_id.
 */
class LegacyNotificationMapper
{
    public const SOURCE = 'ppp.notification';

    public const VERBAL_TEMPLATE_PATTERN = '/muntlig|verbal/i';

    /**
     * @param  iterable<array<string, mixed>>  $legacyRows
     * @param  array{tasks: array<int|string, int>, users: array<int|string, int>, teams: array<int|string, int>}  $ids  old id => new id
     * @return array{rows: list<array<string, mixed>>, skipped: array<string, int>, unknown_method: int}
     */
    public function map(int $churchId, iterable $legacyRows, array $ids): array
    {
        $rows = [];
        $skipped = [];
        $unknownMethod = 0;
        $now = now();

        foreach ($legacyRows as $legacy) {
            $taskId = $ids['tasks'][$legacy['activity_id'] ?? null] ?? null;
            $userId = $ids['users'][$legacy['user_id'] ?? null] ?? null;
            $teamId = filled($legacy['team_id'] ?? null) ? ($ids['teams'][$legacy['team_id']] ?? null) : null;

            if ($taskId === null) {
                $skipped['task_not_imported'] = ($skipped['task_not_imported'] ?? 0) + 1;

                continue;
            }

            if (filled($legacy['team_id'] ?? null) && $teamId === null) {
                $skipped['team_not_imported'] = ($skipped['team_not_imported'] ?? 0) + 1;

                continue;
            }

            if ($teamId === null && $userId === null) {
                $skipped['user_not_imported'] = ($skipped['user_not_imported'] ?? 0) + 1;

                continue;
            }

            $notifiedAt = $this->dateTime($legacy['notified_date'] ?? null);
            $repliedAt = $this->dateTime($legacy['notify_replied_date'] ?? null);
            $isVerbal = (bool) preg_match(self::VERBAL_TEMPLATE_PATTERN, (string) ($legacy['message_name'] ?? ''));

            [$status, $method, $occurredAt] = match ($legacy['status'] ?? null) {
                'No reply requested' => [AssignmentResponseStatus::Notified, null, $notifiedAt],
                'Not replied yet' => [AssignmentResponseStatus::Invited, null, $notifiedAt],
                'Accepted' => [AssignmentResponseStatus::Confirmed, ...$this->acceptanceMethod($isVerbal, $repliedAt, $notifiedAt)],
                'Rejected' => [AssignmentResponseStatus::Declined, $repliedAt ? AssignmentResponseMethod::Replied : null, $repliedAt ?? $notifiedAt],
                default => [AssignmentResponseStatus::Unknown, null, $notifiedAt],
            };

            if ($status === AssignmentResponseStatus::Confirmed && $method === null) {
                $unknownMethod++;
            }

            $rows[] = [
                'church_id' => $churchId,
                'event_task_id' => $taskId,
                'assignee_user_id' => $teamId === null ? $userId : null,
                'assignee_team_id' => $teamId,
                'responder_user_id' => in_array($status, [AssignmentResponseStatus::Confirmed, AssignmentResponseStatus::Declined], true) ? $userId : null,
                'recorded_by_user_id' => $method === AssignmentResponseMethod::Verbal || $status === AssignmentResponseStatus::Invited
                    ? ($ids['users'][$legacy['user_from_id'] ?? null] ?? null)
                    : null,
                'status' => $status->value,
                'method' => $method?->value,
                'requested_at' => $notifiedAt,
                'occurred_at' => $occurredAt,
                'note' => null,
                'legacy_source' => self::SOURCE,
                'legacy_id' => (int) $legacy['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return ['rows' => $rows, 'skipped' => $skipped, 'unknown_method' => $unknownMethod];
    }

    /**
     * @return array{0: AssignmentResponseMethod|null, 1: Carbon|null}
     */
    private function acceptanceMethod(bool $isVerbal, ?Carbon $repliedAt, ?Carbon $notifiedAt): array
    {
        if ($isVerbal) {
            return [AssignmentResponseMethod::Verbal, $notifiedAt];
        }

        if ($repliedAt !== null) {
            return [AssignmentResponseMethod::Replied, $repliedAt];
        }

        return [null, $notifiedAt];
    }

    private function dateTime(mixed $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }
}
