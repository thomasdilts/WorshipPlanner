<?php

namespace Tests\Feature\Auth;

use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Church;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Services\UserInviter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_sends_invite_instead_of_requiring_admin_password(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        app(CurrentChurch::class)->set($church);
        app(PermissionRegistrar::class)->setPermissionsTeamId($church->id);

        $admin = User::factory()->create(['church_id' => $church->id]);
        RolesAndPermissionsSeeder::assignDefaultRoles($admin, 'Church Admin');

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Member',
                'display_name' => 'Member',
                'email' => 'member@example.test',
                'send_invite' => true,
                'roles' => ['Member'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'member@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($church->id, $user->church_id);

        Notification::assertSentTo($user, UserInvitationNotification::class);
    }

    public function test_invite_rate_limit_blocks_duplicate_send_within_a_minute(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $user = User::factory()->create(['church_id' => $church->id]);
        $inviter = app(UserInviter::class);

        $inviter->send($user->loadMissing('church'));
        Notification::assertSentTo($user, UserInvitationNotification::class);

        $this->expectException(ValidationException::class);
        $inviter->send($user);
    }

    public function test_invite_notification_uses_filament_reset_url(): void
    {
        $church = Church::factory()->create(['name' => 'Test Church']);
        $user = User::factory()->create([
            'church_id' => $church->id,
            'email' => 'invitee@example.test',
        ]);

        $mail = (new UserInvitationNotification('test-token', 'Admin', 'Test Church'))
            ->toMail($user);

        $this->assertSame(__('Set your password for :church', ['church' => 'Test Church']), $mail->subject);
        $this->assertStringContainsString('password-reset', $mail->actionUrl);
        $this->assertStringContainsString(rawurlencode('invitee@example.test'), $mail->actionUrl);
    }
}
