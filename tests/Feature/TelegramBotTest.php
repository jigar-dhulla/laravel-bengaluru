<?php

use App\Ai\Agents\TelegramAssistant;
use App\Models\TelegramChat;
use App\Services\TelegramBot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;

beforeEach(function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
});

function telegramUpdate(string $text, int $chatId = 42): array
{
    return [
        'update_id' => 1,
        'message' => [
            'text' => $text,
            'chat' => ['id' => $chatId, 'first_name' => 'Jigar'],
        ],
    ];
}

it('replies with the agent response', function () {
    TelegramAssistant::fake(['Laravel is a PHP framework.']);

    app(TelegramBot::class)->handle(telegramUpdate('What is Laravel?'));

    TelegramAssistant::assertPrompted('What is Laravel?');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && $request['text'] === 'Laravel is a PHP framework.');
});

it('records the chat the message came from', function () {
    TelegramAssistant::fake(['Hi.']);

    app(TelegramBot::class)->handle(telegramUpdate('Hello'));

    expect(TelegramChat::sole())
        ->telegram_id->toBe(42)
        ->name->toBe('Jigar')
        ->conversation_id->not->toBeNull();
});

it('answers /start without prompting the agent', function () {
    TelegramAssistant::fake();

    app(TelegramBot::class)->handle(telegramUpdate('/start'));

    TelegramAssistant::assertNeverPrompted();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && str_contains($request['text'], 'Laravel AI SDK'));
});

it('continues the same conversation across messages', function () {
    TelegramAssistant::fake(['First.', 'Second.']);

    app(TelegramBot::class)->handle(telegramUpdate('Hello'));
    $first = TelegramChat::sole()->conversation_id;

    app(TelegramBot::class)->handle(telegramUpdate('And again'));

    expect(TelegramChat::sole()->conversation_id)->toBe($first);
});

it('titles a conversation from its opening message rather than a second model call', function () {
    TelegramAssistant::fake(['Noted.']);

    $text = 'Remember I am speaking at Laravel Pune conference next month';

    app(TelegramBot::class)->handle(telegramUpdate($text));

    expect(Conversation::sole()->title)->toBe(Str::limit($text, 50, preserveWords: true));
});

it('starts a new conversation after /forget', function () {
    TelegramAssistant::fake(['First.', 'Second.']);

    app(TelegramBot::class)->handle(telegramUpdate('Hello'));
    $first = TelegramChat::sole()->conversation_id;

    app(TelegramBot::class)->handle(telegramUpdate('/forget'));

    expect(TelegramChat::sole()->conversation_id)->toBeNull();

    app(TelegramBot::class)->handle(telegramUpdate('Hello again'));

    expect(TelegramChat::sole()->conversation_id)->not->toBe($first);
});

it('ignores updates without text', function () {
    TelegramAssistant::fake();

    app(TelegramBot::class)->handle(['update_id' => 1, 'message' => ['chat' => ['id' => 42]]]);

    Http::assertNothingSent();
});

it('falls back to the configured providers in order', function () {
    config()->set('ai.default', 'gemini');
    config()->set('ai.failover', ['anthropic', 'openai']);

    expect((new TelegramAssistant(new TelegramChat))->provider())
        ->toBe(['gemini', 'anthropic', 'openai']);
});

it('tells the assistant what time it is', function () {
    Carbon::setTestNow('2026-08-30 08:00:00');

    expect((string) (new TelegramAssistant(TelegramChat::factory()->create()))->instructions())
        ->toContain('Sunday 30 August 2026, 08:00');
});
