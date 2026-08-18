<?php

namespace App\Console\Commands;

use App\Services\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {url? : The public HTTPS URL of the webhook route} {--remove : Stop delivering updates to the webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register or remove the Telegram webhook';

    /**
     * Execute the console command.
     */
    public function handle(Telegram $telegram): int
    {
        if ($this->option('remove')) {
            $telegram->deleteWebhook();

            $this->components->info('Webhook removed.');

            return self::SUCCESS;
        }

        $url = $this->argument('url') ?? route('telegram.webhook');
        $secret = (string) config('services.telegram.webhook_secret');

        if ($secret === '') {
            $this->components->error('Set TELEGRAM_WEBHOOK_SECRET in your .env first, for example:');
            $this->line('  TELEGRAM_WEBHOOK_SECRET='.Str::random(32));

            return self::FAILURE;
        }

        $telegram->setWebhook($url, $secret);

        $this->components->info("Webhook registered: {$url}");

        return self::SUCCESS;
    }
}
