<?php

namespace App\Jobs;

use App\Services\TelegramBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Answers one Telegram update outside the request.
 *
 * Telegram gives a webhook a short window to respond and retries the update if
 * it does not, so the model call must not happen while it is waiting.
 */
class HandleTelegramUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * A retry would send the user a second reply, so a failed update is dropped
     * and reported instead.
     */
    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $update
     */
    public function __construct(private readonly array $update) {}

    /**
     * Execute the job.
     */
    public function handle(TelegramBot $bot): void
    {
        $bot->handle($this->update);
    }
}
