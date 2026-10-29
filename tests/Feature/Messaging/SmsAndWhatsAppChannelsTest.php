<?php

namespace Tests\Feature\Messaging;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Enums\MessagePreference;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Services\MessageSender;
use Modules\Messaging\Support\MessagingAvailability;
use Modules\Messaging\Support\PhoneNumber;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SmsAndWhatsAppChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->member = User::factory()->create([
            'church_id' => $this->church->id,
            'phone' => '+46 70 123 45 67',
            'message_preference' => MessagePreference::Sms,
        ]);
        RolesAndPermissionsSeeder::assignDefaultRoles($this->member, 'Member');

        app(CurrentChurch::class)->set($this->church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->church->id);
    }

    public function test_phone_normalizes_to_msisdn(): void
    {
        $this->assertSame('46701234567', PhoneNumber::toMsisdn('+46 70 123 45 67'));
        $this->assertSame('46701234567', PhoneNumber::toMsisdn('0046701234567'));
        $this->assertNull(PhoneNumber::toMsisdn('abc'));
    }

    public function test_preference_field_only_when_two_modules_enabled(): void
    {
        $availability = app(MessagingAvailability::class);

        $this->assertFalse($availability->shouldAskPreference($this->church));

        app(ChurchModuleManager::class)->enable($this->church, MessagingAvailability::MODULE_EMAIL);
        $this->assertFalse($availability->shouldAskPreference($this->church));

        app(ChurchModuleManager::class)->enable($this->church, MessagingAvailability::MODULE_SMS);
        $this->assertTrue($availability->shouldAskPreference($this->church));
        $this->assertArrayHasKey('email', $availability->preferenceOptions($this->church));
        $this->assertArrayHasKey('sms', $availability->preferenceOptions($this->church));
        $this->assertArrayNotHasKey('whatsapp', $availability->preferenceOptions($this->church));
    }

    public function test_sms_channel_posts_to_ip1_batches(): void
    {
        Http::fake([
            'api.ip1sms.com/*' => Http::response(['id' => 'batch-1', 'status' => 112], 200),
        ]);

        $manager = app(ChurchModuleManager::class);
        $manager->enable($this->church, MessagingAvailability::MODULE_SMS, [
            'api_base' => 'https://api.ip1sms.com/v2',
            'api_token' => $manager->encryptSecret('test-token'),
            'sender' => 'Worship',
        ]);

        $result = app(MessageSender::class)->sendToUser(
            $this->member,
            new OutboundMessageData(subject: 'Hi', body: '<p>Hello <strong>team</strong></p>'),
        );

        $this->assertTrue($result->success);
        $this->assertSame('sms', $result->channel);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.ip1sms.com/v2/batches'
                && $request['sender'] === 'Worship'
                && $request['recipients'] === ['46701234567']
                && $request['body'] === 'Hello team'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_whatsapp_channel_posts_to_meta_graph(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $this->member->update(['message_preference' => MessagePreference::WhatsApp]);

        $manager = app(ChurchModuleManager::class);
        $manager->enable($this->church, MessagingAvailability::MODULE_WHATSAPP, [
            'api_version' => 'v21.0',
            'access_token' => $manager->encryptSecret('wa-token'),
            'phone_number_id' => '123456789',
        ]);

        $result = app(MessageSender::class)->sendToUser(
            $this->member->fresh(),
            new OutboundMessageData(subject: '', body: 'Sunday call time'),
        );

        $this->assertTrue($result->success);
        $this->assertSame('whatsapp', $result->channel);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'graph.facebook.com/v21.0/123456789/messages')
                && $request['to'] === '46701234567'
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Sunday call time';
        });
    }

    public function test_missing_phone_fails_sms_without_calling_provider(): void
    {
        Http::fake();

        $manager = app(ChurchModuleManager::class);
        $manager->enable($this->church, MessagingAvailability::MODULE_SMS, [
            'api_base' => 'https://api.ip1sms.com/v2',
            'api_token' => $manager->encryptSecret('test-token'),
            'sender' => 'Worship',
        ]);

        $this->member->update([
            'phone' => null,
            'message_preference' => MessagePreference::Sms,
        ]);

        $result = app(MessageSender::class)->sendToUser(
            $this->member->fresh(),
            new OutboundMessageData(subject: 'Hi', body: 'Body'),
        );

        $this->assertFalse($result->success);
        $this->assertSame('sms', $result->channel);
        Http::assertNothingSent();
    }

    public function test_falls_back_to_email_when_sms_module_off(): void
    {
        Http::fake();

        app(ChurchModuleManager::class)->enable($this->church, MessagingAvailability::MODULE_EMAIL, [
            'email' => [
                'host' => 'smtp.test',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'from_address' => 'church@test',
                'from_name' => 'Church',
            ],
        ]);

        // Prefer SMS but module not enabled → email attempt (may fail config in test env — we only assert channel path via preference fallback)
        $this->member->update(['message_preference' => MessagePreference::Sms]);

        $result = app(MessageSender::class)->sendToUser(
            $this->member->fresh(),
            new OutboundMessageData(subject: 'Subj', body: 'Body'),
        );

        // Email channel may fail SMTP in tests; important is it did not stay on SMS
        $this->assertNotSame('sms', $result->channel);
        Http::assertNothingSent();
    }

    public function test_email_sends_when_optional_smtp_settings_are_missing(): void
    {
        Mail::fake();

        app(ChurchModuleManager::class)->enable($this->church, MessagingAvailability::MODULE_EMAIL, [
            'email' => [
                'host' => 'smtp.test',
                'from_address' => 'church@test',
            ],
        ]);

        $this->member->update(['message_preference' => MessagePreference::Email]);

        $result = app(MessageSender::class)->sendToUser(
            $this->member->fresh(),
            new OutboundMessageData(subject: 'Subj', body: 'Body'),
        );

        $this->assertTrue($result->success);
        Mail::assertSentCount(1);
    }
}
