<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Database\Factories\TeamTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamType extends Model
{
    /** @use HasFactory<TeamTypeFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'name',
    ];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function taskTemplates(): HasMany
    {
        return $this->hasMany(TaskTemplate::class);
    }
}
