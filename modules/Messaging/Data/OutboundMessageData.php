<?php

namespace Modules\Messaging\Data;

use App\Support\HtmlMessage;

final class OutboundMessageData
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $templateKey = null,
        /** @var array<string, mixed> */
        public readonly array $meta = [],
        public readonly ?string $textBody = null,
        /** Approved Meta WhatsApp template sent instead of the text body. */
        public readonly ?string $whatsappTemplate = null,
        /** @var array<string, string> Placeholder values ({{task}}, {{accept_url}}, …) for the WhatsApp template. */
        public readonly array $placeholders = [],
    ) {}

    /**
     * Body for text-only channels (SMS, WhatsApp): the explicit text version, else the HTML stripped.
     */
    public function plainText(): string
    {
        return filled($this->textBody)
            ? trim((string) $this->textBody)
            : HtmlMessage::toPlainText($this->body);
    }
}
