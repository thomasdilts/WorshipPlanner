<?php

namespace App\Core\Tenancy;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToChurch
{
    public static function bootBelongsToChurch(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('church_id')) {
                return;
            }

            $churchId = static::resolveChurchId();

            if ($churchId) {
                $model->setAttribute('church_id', $churchId);
            }
        });

        static::addGlobalScope('church', function (Builder $query): void {
            $churchId = static::resolveChurchId();

            if ($churchId === null) {
                return;
            }

            $query->where(
                $query->getModel()->getTable().'.church_id',
                $churchId
            );
        });
    }

    protected static function resolveChurchId(): ?int
    {
        $fromContext = app(CurrentChurch::class)->id();

        if ($fromContext !== null) {
            return $fromContext;
        }

        $fromUser = auth()->user()?->church_id;

        return $fromUser !== null ? (int) $fromUser : null;
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
