<?php

namespace Modules\Messaging\Contracts;

use App\Models\Church;
use App\Models\User;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Data\SendResult;

interface MessageChannel
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(Church $church): bool;

    public function send(User $recipient, OutboundMessageData $message): SendResult;
}
