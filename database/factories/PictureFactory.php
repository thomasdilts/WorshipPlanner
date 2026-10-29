<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Picture;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Picture>
 */
class PictureFactory extends Factory
{
    protected $model = Picture::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'path' => 'pictures/placeholder.jpg',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Picture $picture): void {
            if ($picture->path !== 'pictures/placeholder.jpg') {
                return;
            }

            $disk = Storage::disk('public');
            $path = 'churches/'.($picture->church_id ?? '0').'/pictures/test-'.uniqid('', true).'.jpg';

            // Minimal valid JPEG (1x1) so FileUpload/url helpers have a real file in tests.
            $disk->put($path, base64_decode(
                '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z'
            ));

            $picture->path = $path;
        });
    }
}
