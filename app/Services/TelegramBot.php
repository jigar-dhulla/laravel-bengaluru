<?php

namespace App\Services;

use App\Ai\Agents\TelegramAssistant;
use App\Models\TelegramChat;

/**
 * Turns an incoming Telegram update into a reply.
 */
class TelegramBot
{
    /**
     * The reply to /start.
     */
    private const WELCOME = 'Hi! I am a reminder bot built with Laravel and the Laravel AI SDK. Tell me what to remind you about and when — "remind me to call the dentist tomorrow at 9". Send /forget to start a fresh conversation.';

    public function __construct(private readonly Telegram $telegram) {}

    /**
     * Handle a single update delivered by webhook or long polling.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $message = $update['message'] ?? [];
        $text = trim((string) ($message['text'] ?? ''));

        if ($text === '') {
            return;
        }

        $chat = $this->chatFor($message);

        $this->telegram->sendMessage($chat->telegram_id, $this->reply($chat, $text));
    }

    /**
     * Build the reply for the given message.
     */
    private function reply(TelegramChat $chat, string $text): string
    {
        return match ($text) {
            '/start' => self::WELCOME,
            '/forget' => $this->forget($chat),
            default => $this->ask($chat, $text),
        };
    }

    /**
     * Drop the stored conversation so the next message starts fresh.
     */
    private function forget(TelegramChat $chat): string
    {
        $chat->update(['conversation_id' => null]);

        return 'Done, I have forgotten our conversation. What would you like to talk about?';
    }

    /**
     * Prompt the agent, remembering which conversation the reply belongs to.
     *
     * The typing indicator is sent here rather than for every update, because
     * this is the only reply that waits on a model.
     */
    private function ask(TelegramChat $chat, string $text): string
    {
        $this->telegram->sendTyping($chat->telegram_id);

        $agent = new TelegramAssistant($chat);

        if ($chat->conversation_id) {
            $agent->continue($chat->conversation_id, as: $chat);
        } else {
            $agent->forParticipant($chat);
        }

        $response = $agent->prompt($text);

        $chat->update(['conversation_id' => $response->conversationId]);

        return $response->text;
    }

    /**
     * Find or create the chat the message belongs to.
     *
     * @param  array<string, mixed>  $message
     */
    private function chatFor(array $message): TelegramChat
    {
        return TelegramChat::firstOrCreate(
            ['telegram_id' => $message['chat']['id']],
            ['name' => $message['chat']['first_name'] ?? $message['chat']['title'] ?? null],
        );
    }
}
