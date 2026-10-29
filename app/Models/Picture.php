<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Database\Factories\PictureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Picture extends Model
{
    /** @use HasFactory<PictureFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'name',
        'description',
        'path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Picture $picture): void {
            if (filled($picture->path)) {
                Storage::disk('public')->delete($picture->path);
            }
        });
    }

    public function eventTasks(): BelongsToMany
    {
        return $this->belongsToMany(EventTask::class)
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function url(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }
}
