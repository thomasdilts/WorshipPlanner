<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use App\Enums\MessageTemplatePurpose;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'name',
        'key',
        'purpose',
        'subject',
        'body',
        'text_body',
        'whatsapp_template',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'purpose' => MessageTemplatePurpose::class,
        ];
    }
}
