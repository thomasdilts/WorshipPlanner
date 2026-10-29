<?php

namespace Modules\Messaging\Services;

use App\Enums\MessagePreference;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Traits\Localizable;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Data\SendResult;

/**
 * Sends "Your preferred messaging type is …" through the newly chosen channel, in the person's language.
 */
class PreferenceConfirmation
{
    use Localizable;

    public function __construct(
        protected MessageSender $sender,
    ) {}

    public function send(User $recipient, MessagePreference $preference, ?int $languageId = null): SendResult
    {
        $locale = Language::query()->find($languageId ?? $recipient->language_id)?->iso ?? app()->getLocale();

        $message = $this->withLocale($locale, fn (): OutboundMessageData => new OutboundMessageData(
            subject: __('Preferred messaging'),
            body: __('Your preferred messaging type is :type', ['type' => $preference->label()]),
            templateKey: 'preference_confirmation',
        ));

        return $this->sender->sendVia($recipient, $preference, $message);
    }
}
