<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'team_type_id',
        'name',
        'description',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TeamType::class, 'team_type_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_leader'])
            ->withTimestamps()
            ->orderByPivot('is_leader', 'desc')
            ->orderBy('display_name')
            ->orderBy('name');
    }

    /**
     * Messages members sent to each other through this team (one row per recipient).
     */
    public function teamMessages(): HasMany
    {
        return $this->hasMany(OutboundMessage::class, 'church_id', 'church_id')
            ->where('outbound_messages.meta->team_id', $this->id);
    }

    public function leaders(): BelongsToMany
    {
        return $this->members()->wherePivot('is_leader', true);
    }

    public function eventTasks(): HasMany
    {
        return $this->hasMany(EventTask::class, 'assigned_team_id');
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class);
    }

    public function sharedFiles(): BelongsToMany
    {
        return $this->belongsToMany(SharedFile::class)
            ->withTimestamps()
            ->orderBy('name');
    }

    public function hasLeader(): bool
    {
        return $this->leaders()->exists();
    }

    public function isLeader(User $user): bool
    {
        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivot('is_leader', true)
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * @return EloquentCollection<int, User>
     */
    public function leaderUsers(): EloquentCollection
    {
        return $this->leaders()->get();
    }

    /**
     * Sync members. Every leader id must also be in member ids.
     *
     * @param  list<int>  $memberIds
     * @param  list<int>  $leaderIds
     */
    public function syncRoster(array $memberIds, array $leaderIds): void
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        $leaderIds = array_values(array_unique(array_map('intval', $leaderIds)));
        $leaderIds = array_values(array_intersect($leaderIds, $memberIds));

        if ($leaderIds === []) {
            throw new \InvalidArgumentException('A team must have at least one team leader.');
        }

        $sync = [];
        foreach ($memberIds as $id) {
            $sync[$id] = ['is_leader' => in_array($id, $leaderIds, true)];
        }

        $this->members()->sync($sync);
    }
}
