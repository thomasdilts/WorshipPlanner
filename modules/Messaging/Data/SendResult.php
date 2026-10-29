<?php

namespace Modules\Messaging\Data;

final class SendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $channel,
        public readonly ?string $error = null,
        public readonly ?string $providerMessageId = null,
    ) {}

    public static function ok(string $channel, ?string $providerMessageId = null): self
    {
        return new self(true, $channel, null, $providerMessageId);
    }

    public static function fail(string $channel, string $error): self
    {
        return new self(false, $channel, $error);
    }
}
