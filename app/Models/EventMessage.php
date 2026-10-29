<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventMessage extends Model
{
    use BelongsToChurch;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'church_id',
        'event_id',
        'event_task_id',
        'created_by_user_id',
        'message_template_id',
        'subject',
        'body',
        'text_body',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(EventTask::class, 'event_task_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_message_user')
            ->withPivot(['delivery_status', 'delivery_error'])
            ->withTimestamps();
    }

    public function outboundMessages(): HasMany
    {
        return $this->hasMany(OutboundMessage::class);
    }
}
