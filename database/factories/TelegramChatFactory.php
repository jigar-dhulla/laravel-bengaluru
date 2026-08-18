<?php

namespace Database\Factories;

use App\Models\TelegramChat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramChat>
 */
class TelegramChatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->randomNumber(9),
            'name' => fake()->firstName(),
        ];
    }
}
