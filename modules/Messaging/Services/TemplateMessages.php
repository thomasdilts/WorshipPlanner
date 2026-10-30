<?php

namespace Modules\Messaging\Services;

use App\Enums\MessageTemplatePurpose;
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
     * Active templates for a select, only those made for the given purpose.
     *
     * @return array<int, string>
     */
    public function options(MessageTemplatePurpose $purpose): array
    {
        return MessageTemplate::query()
            ->where('is_active', true)
            ->where('purpose', $purpose)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Templates for the preferred-messaging confirmation select: those marked "Other", plus the
     * saved choice so it stays selectable after its purpose changes.
     *
     * @return array<int, string>
     */
    public function confirmationOptions(int|string|null $current): array
    {
        $options = $this->options(MessageTemplatePurpose::Other);

        if (filled($current) && ! array_key_exists((int) $current, $options)) {
            $saved = MessageTemplate::query()->find($current);

            if ($saved !== null) {
                $options[$saved->id] = $saved->name;
            }
        }

        return $options;
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
     * @param  array<string, string>  $values  Extra placeholder values, e.g. {{message_sender}} and {{team}}.
     */
    public function compose(
        MessageTemplate $template,
        User $recipient,
        ?string $customMessage = null,
        array $meta = [],
        ?string $fallbackSubject = null,
        array $values = [],
    ): OutboundMessageData {
        $custom = self::customMessageValues($customMessage);
        $values = [...$this->placeholders->valuesFor($recipient, null, collect()), ...$values, ...$custom['text']];

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
