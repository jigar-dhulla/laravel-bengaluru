<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_chat_id' => TelegramChat::factory(),
            'body' => fake()->sentence(),
            'remind_at' => now()->addHour(),
        ];
    }

    /**
     * Indicate that the reminder is ready to be delivered.
     */
    public function due(): static
    {
        return $this->state(fn (): array => [
            'remind_at' => now()->subMinute(),
        ]);
    }

    /**
     * Indicate that the reminder has already been delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'remind_at' => now()->subHour(),
            'delivered_at' => now()->subHour(),
        ]);
    }
}
