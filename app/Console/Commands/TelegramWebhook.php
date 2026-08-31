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
    protected $signature = 'telegram:webhook
                            {url? : The public HTTPS URL of the webhook route}
                            {--regenerate : Ignore the configured secret and register a fresh one}
                            {--remove : Stop delivering updates to the webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register or remove the Telegram webhook and print its secret token';

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

        $configured = (string) config('services.telegram.webhook_secret');
        $isGenerated = $configured === '' || $this->option('regenerate');

        $secret = $isGenerated ? Str::random(64) : $configured;

        $url = $this->argument('url') ?? route('telegram.webhook');

        $telegram->setWebhook($url, $secret);

        $this->components->info("Webhook registered: {$url}");

        if (! $isGenerated) {
            $this->components->info('Registered with the secret already in TELEGRAM_WEBHOOK_SECRET.');

            return self::SUCCESS;
        }

        $this->components->warn('Add this to your .env — the webhook rejects every update until you do:');
        $this->line('  TELEGRAM_WEBHOOK_SECRET='.$secret);
        $this->newLine();

        return self::SUCCESS;
    }
}
