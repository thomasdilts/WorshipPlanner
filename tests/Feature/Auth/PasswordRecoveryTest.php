<?php

namespace Tests\Feature\Auth;

use App\Models\Church;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_routes_are_registered(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertTrue(Filament::getCurrentOrDefaultPanel()->hasPasswordReset());
        $this->assertNotNull(Filament::getRequestPasswordResetUrl());
        $this->get(Filament::getRequestPasswordResetUrl())->assertSuccessful();
    }

    public function test_request_sends_reset_mail_without_disclosing_unknown_emails(): void
    {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $church = Church::factory()->create();
        $user = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'known@example.test',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'known@example.test'])
            ->call('request')
            ->assertNotified();

        Notification::assertSentTo($user, ResetPassword::class);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'missing@example.test'])
            ->call('request')
            ->assertNotified();

        Notification::assertCount(1);
    }
}
