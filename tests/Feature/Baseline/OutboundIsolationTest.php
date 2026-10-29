<?php

namespace Tests\Feature\Baseline;

use App\Core\Modules\ChurchModuleManager;
use App\Enums\MessagePreference;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Services\MessageSender;
use Modules\Messaging\Support\MessagingAvailability;
use Tests\TestCase;

class OutboundIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unstubbed_http_requests_are_blocked(): void
    {
        $this->expectException(StrayRequestException::class);

        Http::get('https://bible.zamarapp.com/anything');
    }

    public function test_email_with_a_real_looking_smtp_config_never_leaves_the_process(): void
    {
        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(ChurchModuleManager::class)->enable($church, MessagingAvailability::MODULE_EMAIL, [
            'email' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => 'real-account@gmail.com',
                'password' => 'not-a-real-password',
                'encryption' => 'tls',
                'from_address' => 'real-account@gmail.com',
                'from_name' => 'Church',
            ],
        ]);
        $member = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'volunteer@example.test',
            'message_preference' => MessagePreference::Email,
        ]);

        $result = app(MessageSender::class)->sendToUser(
            $member,
            new OutboundMessageData(subject: 'Sunday', body: 'Call time 9:00'),
        );

        $this->assertTrue($result->success);
        Mail::assertSent(Mailable::class, fn (Mailable $mail): bool => $mail->hasTo('volunteer@example.test'));
    }

    public function test_default_test_mailer_is_the_in_memory_array_transport(): void
    {
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('sync', config('queue.default'));
    }
}
