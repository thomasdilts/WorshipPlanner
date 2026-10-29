<?php

namespace Modules\Teams\Services;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class TeamRosterService
{
    public const LEADER_ROLE = 'Team Leader';

    /**
     * @param  list<int>  $memberIds
     * @param  list<int>  $leaderIds
     */
    public function syncRoster(Team $team, array $memberIds, array $leaderIds): Team
    {
        return DB::transaction(function () use ($team, $memberIds, $leaderIds) {
            $team->syncRoster($memberIds, $leaderIds);

            foreach ($leaderIds as $leaderId) {
                $leader = User::query()->find($leaderId);

                if ($leader !== null) {
                    $this->ensureLeaderRole($leader);
                }
            }

            return $team->fresh(['members']);
        });
    }

    public function ensureLeaderRole(User $user): void
    {
        if ($user->church_id === null) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        Role::findOrCreate(self::LEADER_ROLE, 'web');

        if (! $user->hasRole(self::LEADER_ROLE)) {
            $user->assignRole(self::LEADER_ROLE);
        }
    }

    public function addMember(Team $team, User $user, bool $asLeader = false): void
    {
        if ((int) $user->church_id !== (int) $team->church_id) {
            throw new \InvalidArgumentException('User belongs to a different church.');
        }

        $team->members()->syncWithoutDetaching([
            $user->id => ['is_leader' => $asLeader],
        ]);

        if ($asLeader) {
            $this->ensureLeaderRole($user);
        }

        if (! $team->hasLeader()) {
            throw new \InvalidArgumentException('A team must have at least one team leader.');
        }
    }

    public function removeMember(Team $team, User $user): void
    {
        $wasOnlyLeader = $team->isLeader($user)
            && $team->leaders()->count() === 1;

        if ($wasOnlyLeader) {
            throw new \InvalidArgumentException('Cannot remove the last team leader.');
        }

        $team->members()->detach($user->id);
    }

    public function setLeader(Team $team, User $user, bool $isLeader): void
    {
        if (! $team->isMember($user)) {
            throw new \InvalidArgumentException('User is not a member of this team.');
        }

        if (! $isLeader && $team->isLeader($user) && $team->leaders()->count() === 1) {
            throw new \InvalidArgumentException('A team must have at least one team leader.');
        }

        $team->members()->updateExistingPivot($user->id, ['is_leader' => $isLeader]);

        if ($isLeader) {
            $this->ensureLeaderRole($user);
        }
    }
}
