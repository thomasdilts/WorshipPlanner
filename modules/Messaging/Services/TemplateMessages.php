<?php

namespace Modules\Messaging\Services;

use App\Models\MessageTemplate;
use App\Models\User;
use App\Support\HtmlMessage;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Support\MessagePlaceholders;

/**
 * Builds messages from the church's message templates: every message is sent from a template,
 * with {{custom_mess}} replaced by the text the sender typed.
 */
class TemplateMessages
{
    public function __construct(
        protected MessagePlaceholders $placeholders,
        protected WhatsAppTemplates $whatsAppTemplates,
    ) {}

    /**
     * Active templates for a select.
     *
     * @return array<int, string>
     */
    public function options(): array
    {
        return MessageTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Whether the template, or its WhatsApp template, contains {{custom_mess}}.
     */
    public function usesCustomMessage(?MessageTemplate $template): bool
    {
        if ($template === null) {
            return false;
        }

        if (MessagePlaceholders::usesCustomMessage($template->subject, $template->body, $template->text_body)) {
            return true;
        }

        return $template->church !== null
            && $this->whatsAppTemplates->usesCustomMessage($template->church, $template->whatsapp_template);
    }

    /**
     * Placeholder values for {{custom_mess}}: plain text, and HTML with the line breaks kept.
     *
     * @return array{text: array<string, string>, html: array<string, string>}
     */
    public static function customMessageValues(?string $customMessage): array
    {
        $text = trim((string) $customMessage);

        return [
            'text' => [MessagePlaceholders::CUSTOM_MESSAGE => $text],
            'html' => [MessagePlaceholders::CUSTOM_MESSAGE => str_replace(["\r\n", "\r", "\n"], '<br>', e($text))],
        ];
    }

    /**
     * The template as this recipient gets it, without event or task details.
     *
     * @param  array<string, mixed>  $meta
     */
    public function compose(
        MessageTemplate $template,
        User $recipient,
        ?string $customMessage = null,
        array $meta = [],
        ?string $fallbackSubject = null,
    ): OutboundMessageData {
        $custom = self::customMessageValues($customMessage);
        $values = [...$this->placeholders->valuesFor($recipient, null, collect()), ...$custom['text']];

        $html = filled(HtmlMessage::toPlainText($template->body))
            ? $this->placeholders->render((string) $template->body, $values, escapeHtml: true, htmlValues: $custom['html'])
            : nl2br($this->placeholders->render(e((string) $template->text_body), $values, escapeHtml: true, htmlValues: $custom['html']));
        $text = filled($template->text_body)
            ? $this->placeholders->render((string) $template->text_body, $values, escapeHtml: false)
            : HtmlMessage::toPlainText($this->placeholders->render((string) $template->body, $values, escapeHtml: false));

        return new OutboundMessageData(
            subject: $this->placeholders->render((string) ($template->subject ?: $fallbackSubject ?: $template->name), $values, escapeHtml: false),
            body: $html,
            templateKey: $template->key,
            meta: $meta,
            textBody: $text,
            whatsappTemplate: $template->whatsapp_template,
            placeholders: $values,
        );
    }
}
