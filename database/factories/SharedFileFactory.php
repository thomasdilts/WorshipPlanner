<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SharedFile>
 */
class SharedFileFactory extends Factory
{
    protected $model = SharedFile::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'church_id' => Church::factory(),
            'uploaded_by_user_id' => User::factory(),
            'name' => Str::title($name),
            'description' => fake()->optional()->sentence(),
            'path' => 'churches/0/files/'.Str::uuid().'.pdf',
            'original_name' => Str::slug($name).'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1_000, 500_000),
        ];
    }
}
