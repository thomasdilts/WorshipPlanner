<?php

namespace Modules\Messaging\Channels;

use App\Core\Modules\ChurchModuleManager;
use App\Models\Church;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Modules\Messaging\Contracts\MessageChannel;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Data\SendResult;
use Modules\Messaging\Mail\GenericMessageMail;

class EmailChannel implements MessageChannel
{
    public function __construct(
        protected ChurchModuleManager $modules,
    ) {}

    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return __('Email');
    }

    public function isConfigured(Church $church): bool
    {
        if (! $this->modules->isEnabled($church, 'messaging')) {
            return false;
        }

        $email = $this->modules->settings($church, 'messaging')['email'] ?? [];

        return filled($email['host'] ?? null)
            && filled($email['from_address'] ?? null);
    }

    public function send(User $recipient, OutboundMessageData $message): SendResult
    {
        $church = $recipient->church;

        if ($church === null) {
            return SendResult::fail($this->key(), 'Recipient has no church.');
        }

        if (! $this->isConfigured($church)) {
            return SendResult::fail($this->key(), 'Email channel is not configured for this church.');
        }

        if (! filled($recipient->email)) {
            return SendResult::fail($this->key(), 'Recipient has no email address.');
        }

        $settings = $this->modules->settings($church, 'messaging')['email'] ?? [];
        $password = $this->modules->decryptSecret($settings['password'] ?? null);

        Config::set('mail.mailers.church_smtp', [
            'transport' => 'smtp',
            'host' => $settings['host'],
            'port' => (int) ($settings['port'] ?? 587),
            'encryption' => ($settings['encryption'] ?? 'tls') ?: null,
            'username' => ($settings['username'] ?? null) ?: null,
            'password' => $password ?: null,
            'timeout' => null,
        ]);

        Config::set('mail.from', [
            'address' => $settings['from_address'],
            'name' => ($settings['from_name'] ?? null) ?: $church->name,
        ]);

        try {
            Mail::mailer('church_smtp')->to($recipient->email, $recipient->display_name ?: $recipient->name)
                ->send(new GenericMessageMail($message->subject, $message->body));

            return SendResult::ok($this->key());
        } catch (\Throwable $e) {
            return SendResult::fail($this->key(), $e->getMessage());
        }
    }
}
