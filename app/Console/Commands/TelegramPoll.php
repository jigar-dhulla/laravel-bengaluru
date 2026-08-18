<?php

namespace App\Console\Commands;

use App\Services\Telegram;
use App\Services\TelegramBot;
use Illuminate\Console\Command;
use Throwable;

class TelegramPoll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive Telegram messages with long polling, no public URL required';

    /**
     * Execute the console command.
     */
    public function handle(Telegram $telegram, TelegramBot $bot): int
    {
        $telegram->deleteWebhook();

        $this->components->info('Listening for messages. Press Ctrl+C to stop.');

        $offset = null;

        while (true) {
            try {
                $updates = $telegram->getUpdates($offset);
            } catch (Throwable $e) {
                // Conference wi-fi drops connections; keep listening rather than exiting.
                $this->components->warn('Reconnecting: '.$e->getMessage());

                sleep(1);

                continue;
            }

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                $this->components->twoColumnDetail(
                    $update['message']['chat']['first_name'] ?? 'chat',
                    $update['message']['text'] ?? '(no text)',
                );

                try {
                    $bot->handle($update);
                } catch (Throwable $e) {
                    $this->components->error($e->getMessage());

                    report($e);
                }
            }
        }
    }
}
