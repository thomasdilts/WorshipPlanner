<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use App\Services\UserAvatarProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_processor_converts_png_to_jpeg_max_100px(): void
    {
        Storage::fake('public');

        $png = imagecreatetruecolor(400, 300);
        $red = imagecolorallocate($png, 200, 40, 40);
        imagefilledrectangle($png, 0, 0, 399, 299, $red);
        ob_start();
        imagepng($png);
        $binary = ob_get_clean();
        imagedestroy($png);

        $path = 'churches/1/avatars/source.png';
        Storage::disk('public')->put($path, $binary);

        $result = app(UserAvatarProcessor::class)->normalizeToJpeg($path);

        $this->assertStringEndsWith('.jpg', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
        $this->assertFalse(Storage::disk('public')->exists($path));

        $info = getimagesize(Storage::disk('public')->path($result));
        $this->assertNotFalse($info);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        $this->assertLessThanOrEqual(UserAvatarProcessor::MAX_EDGE, $info[1]);
        $this->assertLessThanOrEqual(UserAvatarProcessor::MAX_EDGE, $info[0]);
    }

    public function test_user_saving_normalizes_avatar_and_exposes_url(): void
    {
        Storage::fake('public');

        $church = Church::factory()->create();
        $png = imagecreatetruecolor(120, 120);
        imagefilledrectangle($png, 0, 0, 119, 119, imagecolorallocate($png, 0, 128, 128));
        ob_start();
        imagepng($png);
        $binary = ob_get_clean();
        imagedestroy($png);

        $path = 'churches/'.$church->id.'/avatars/upload.png';
        Storage::disk('public')->put($path, $binary);

        $user = User::factory()->create([
            'church_id' => $church->id,
            'avatar_path' => $path,
        ]);

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertStringEndsWith('.jpg', $user->avatar_path);
        $this->assertNotNull($user->getFilamentAvatarUrl());
        $this->assertSame($user->displayName(), $user->getFilamentName());
        $this->assertTrue(Storage::disk('public')->exists($user->avatar_path));
    }

    public function test_avatar_initials_use_display_name(): void
    {
        $user = new User([
            'name' => 'Thomas Dilts',
            'display_name' => 'Tom D',
        ]);

        $this->assertSame('TD', $user->avatarInitials());
        $this->assertSame('Tom D', $user->displayName());
    }
}
