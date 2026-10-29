<?php

namespace Database\Factories;

use App\Enums\MessagePreference;
use App\Enums\TextDirection;
use App\Models\Church;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => fake()->name(),
            'display_name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_platform_admin' => false,
            'message_preference' => MessagePreference::Email,
            'language_id' => fn (): int => Language::query()->firstOrCreate(
                ['iso' => 'en'],
                [
                    'name_en' => 'English',
                    'name_native' => 'English',
                    'font' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                    'direction' => TextDirection::Ltr,
                    'is_active' => true,
                    'sort_order' => 10,
                ],
            )->id,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'church_id' => null,
            'is_platform_admin' => true,
        ]);
    }
}
