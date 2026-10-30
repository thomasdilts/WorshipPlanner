<?php

namespace Modules\Messaging\Channels;

use App\Core\Modules\ChurchModuleManager;
use App\Models\Church;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\Messaging\Contracts\MessageChannel;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Data\SendResult;
use Modules\Messaging\Services\WhatsAppTemplates;
use Modules\Messaging\Support\MessagingAvailability;
use Modules\Messaging\Support\PhoneNumber;

/**
 * Meta WhatsApp Cloud API — an approved template when the message has one, else a text message
 * (text only arrives within 24 hours of the person's last message to the business).
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages
 */
class WhatsAppChannel implements MessageChannel
{
    public function __construct(
        protected ChurchModuleManager $modules,
        protected WhatsAppTemplates $templates,
    ) {}

    public function key(): string
    {
        return 'whatsapp';
    }

    public function label(): string
    {
        return __('WhatsApp');
    }

    public function isConfigured(Church $church): bool
    {
        if (! $this->modules->isEnabled($church, MessagingAvailability::MODULE_WHATSAPP)) {
            return false;
        }

        $settings = $this->modules->settings($church, MessagingAvailability::MODULE_WHATSAPP);

        return filled($settings['access_token'] ?? null)
            && filled($settings['phone_number_id'] ?? null);
    }

    public function send(User $recipient, OutboundMessageData $message): SendResult
    {
        $church = $recipient->church;

        if ($church === null) {
            return SendResult::fail($this->key(), 'Recipient has no church.');
        }

        if (! $this->isConfigured($church)) {
            return SendResult::fail($this->key(), 'WhatsApp is not configured for this church.');
        }

        $msisdn = PhoneNumber::toMsisdn($recipient->phone);

        if ($msisdn === null) {
            return SendResult::fail($this->key(), 'Recipient has no valid mobile number for WhatsApp.');
        }

        $settings = $this->modules->settings($church, MessagingAvailability::MODULE_WHATSAPP);
        $token = $this->modules->decryptSecret($settings['access_token'] ?? null);
        $phoneNumberId = (string) ($settings['phone_number_id'] ?? '');
        $version = trim((string) ($settings['api_version'] ?? 'v21.0'), '/');
        $url = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $phoneNumberId);

        if (filled($message->whatsappTemplate)) {
            $content = $this->templateContent($recipient, $church, $message);

            if ($content instanceof SendResult) {
                return $content;
            }
        } else {
            $body = $message->plainText();

            if ($body === '') {
                return SendResult::fail($this->key(), 'Message body is empty.');
            }

            $content = [
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ];
        }

        try {
            $response = Http::withToken((string) $token)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $msisdn,
                    ...$content,
                ]);

            if (! $response->successful()) {
                return SendResult::fail(
                    $this->key(),
                    'WhatsApp API failed ('.$response->status().'): '.$response->body(),
                );
            }

            return SendResult::ok($this->key());
        } catch (\Throwable $e) {
            return SendResult::fail($this->key(), $e->getMessage());
        }
    }

    /**
     * The "template" part of the request, or a failure when the template cannot be used.
     *
     * @return array{type: string, template: array<string, mixed>}|SendResult
     */
    protected function templateContent(User $recipient, Church $church, OutboundMessageData $message): array|SendResult
    {
        $name = (string) $message->whatsappTemplate;
        $template = $this->templates->find($church, $name, $recipient->language?->iso);

        if ($template === null) {
            $fetchError = $this->templates->fetchError($church);

            if ($fetchError !== null) {
                return SendResult::fail($this->key(), __('WhatsApp templates could not be loaded: :error', ['error' => $fetchError]));
            }

            return SendResult::fail($this->key(), __('WhatsApp template ":name" was not found. Check the WhatsApp Business Account ID in the WhatsApp settings.', ['name' => $name]));
        }

        if ($template['status'] !== WhatsAppTemplates::STATUS_APPROVED) {
            return SendResult::fail($this->key(), __('WhatsApp template ":name" is not approved by Meta yet (status: :status).', ['name' => $name, 'status' => $template['status']]));
        }

        try {
            $components = $this->templates->components($template, $message->placeholders);
        } catch (\InvalidArgumentException $e) {
            return SendResult::fail($this->key(), $e->getMessage());
        }

        return [
            'type' => 'template',
            'template' => array_filter([
                'name' => $template['name'],
                'language' => ['code' => $template['language']],
                'components' => $components,
            ]),
        ];
    }
}
