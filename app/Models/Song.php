<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Database\Factories\SongFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    /** @use HasFactory<SongFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'name',
        'name2',
        'author',
        'description',
    ];

    public function eventTasks(): HasMany
    {
        return $this->hasMany(EventTask::class);
    }

    public function label(): string
    {
        $bits = [$this->name];

        if (filled($this->name2)) {
            $bits[] = $this->name2;
        }

        if (filled($this->author) && $this->author !== 'Author Unknown') {
            $bits[] = '('.$this->author.')';
        }

        return implode(' — ', $bits);
    }
}
