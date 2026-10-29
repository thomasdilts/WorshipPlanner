<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundMessage extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'event_id',
        'event_message_id',
        'user_id',
        'channel',
        'subject',
        'body',
        'status',
        'error',
        'template_key',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventMessage(): BelongsTo
    {
        return $this->belongsTo(EventMessage::class);
    }
}
