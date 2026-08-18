<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\Telegram;
use Illuminate\Console\Command;
use Throwable;

class DeliverReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:deliver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the reminders that have come due';

    /**
     * Execute the console command.
     */
    public function handle(Telegram $telegram): int
    {
        $reminders = Reminder::due()->with('telegramChat')->get();

        foreach ($reminders as $reminder) {
            try {
                $telegram->sendMessage($reminder->telegramChat->telegram_id, "⏰ {$reminder->body}");
            } catch (Throwable $e) {
                // Leave it undelivered so the next run tries again, rather than
                // losing a reminder to one bad minute of network.
                $this->components->error($e->getMessage());

                report($e);

                continue;
            }

            $reminder->update(['delivered_at' => now()]);

            $this->components->twoColumnDetail(
                $reminder->telegramChat->name ?? 'chat',
                $reminder->body,
            );
        }

        $this->components->info("Delivered {$reminders->count()} reminder(s).");

        return self::SUCCESS;
    }
}
