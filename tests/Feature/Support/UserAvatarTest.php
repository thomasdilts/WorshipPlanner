<?php

namespace Tests\Feature\Support;

use App\Models\Church;
use App\Models\User;
use App\Support\InitialsAvatarProvider;
use App\Support\UserAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'church_id' => Church::factory()->create()->id,
            'name' => 'Anna Lindqvist',
            'display_name' => 'Anna',
        ]);
    }

    public function test_a_person_without_a_photo_shows_initials_and_no_image(): void
    {
        $html = (string) UserAvatar::html($this->member());

        $this->assertStringContainsString('AN', $html);
        $this->assertStringContainsString('Anna', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_a_photo_that_is_missing_from_storage_falls_back_to_initials(): void
    {
        Storage::fake('public');
        $user = $this->member(['avatar_path' => 'avatars/gone.jpg']);

        $this->assertNull($user->avatarUrl());
        $this->assertStringNotContainsString('<img', (string) UserAvatar::html($user));
    }

    public function test_an_existing_photo_keeps_initials_underneath_and_drops_itself_if_it_fails_to_load(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('avatars', UploadedFile::fake()->image('a.jpg'), 'a.jpg');
        $user = $this->member(['avatar_path' => 'avatars/a.jpg']);

        $html = (string) UserAvatar::html($user);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('onerror="this.remove()"', $html);
        $this->assertStringContainsString('AN', $html);
    }

    public function test_an_avatar_without_a_visible_name_still_has_an_accessible_name(): void
    {
        $html = (string) UserAvatar::html($this->member(), withName: false);

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('aria-label="Anna"', $html);
    }

    public function test_an_unknown_person_renders_a_placeholder_instead_of_failing(): void
    {
        $this->assertStringContainsString('?', (string) UserAvatar::html(null));
    }

    public function test_the_panel_fallback_avatar_is_local_and_shows_the_initials(): void
    {
        $uri = app(InitialsAvatarProvider::class)->get($this->member());

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $this->assertStringContainsString('AN', base64_decode(substr($uri, strlen('data:image/svg+xml;base64,'))));
    }
}
