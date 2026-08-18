<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A thin client for the Telegram Bot HTTP API.
 *
 * @see https://core.telegram.org/bots/api
 */
class Telegram
{
    public function __construct(private readonly string $token) {}

    /**
     * Send a text message to the given chat.
     *
     * Telegram caps a message at 4096 characters, so longer replies are split.
     */
    public function sendMessage(int $chatId, string $text): void
    {
        foreach (mb_str_split($text, 4000) as $chunk) {
            $this->sendChunk($chatId, $chunk);
        }
    }

    /**
     * Send a single message, falling back to plain text.
     */
    private function sendChunk(int $chatId, string $text): void
    {
        try {
            $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (RuntimeException) {
            // A model can emit Markdown that Telegram refuses to parse, and
            // dropping the reply is worse than losing the formatting.
            $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }
    }

    /**
     * Show the "typing..." indicator in the given chat.
     */
    public function sendTyping(int $chatId): void
    {
        $this->call('sendChatAction', [
            'chat_id' => $chatId,
            'action' => 'typing',
        ]);
    }

    /**
     * Point Telegram at the given webhook URL.
     */
    public function setWebhook(string $url, ?string $secret = null): void
    {
        $this->call('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secret,
            'drop_pending_updates' => true,
        ]));
    }

    /**
     * Stop Telegram from delivering updates to a webhook.
     */
    public function deleteWebhook(): void
    {
        $this->call('deleteWebhook', ['drop_pending_updates' => true]);
    }

    /**
     * Long poll for updates newer than the given offset.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(?int $offset = null, int $timeout = 25): array
    {
        return $this->call('getUpdates', array_filter([
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ])) ?? [];
    }

    /**
     * Call a Bot API method and return its result payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function call(string $method, array $payload = []): mixed
    {
        try {
            $response = Http::asJson()
                ->connectTimeout(10)
                ->timeout(60)
                ->post("https://api.telegram.org/bot{$this->token}/{$method}", $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException("Telegram [{$method}] unreachable: ".$this->scrub($e->getMessage()), previous: $e);
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException("Telegram [{$method}] failed: ".$this->scrub($response->body()));
        }

        return $response->json('result');
    }

    /**
     * Remove the bot token from a message before it reaches a log or a screen.
     */
    private function scrub(string $message): string
    {
        return str_replace($this->token, '<TELEGRAM_BOT_TOKEN>', $message);
    }
}
