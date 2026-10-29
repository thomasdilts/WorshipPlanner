<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Database\Factories\SharedFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class SharedFile extends Model
{
    /** @use HasFactory<SharedFileFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'uploaded_by_user_id',
        'name',
        'description',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (SharedFile $file): void {
            if (filled($file->path)) {
                Storage::disk('public')->delete($file->path);
            }
        });
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }

    public function eventTasks(): BelongsToMany
    {
        return $this->belongsToMany(EventTask::class)
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function url(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }

    public function humanSize(): string
    {
        if ($this->size === null) {
            return '—';
        }

        $bytes = (float) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
