<?php

use App\Ai\Agents\TelegramAssistant;
use App\Jobs\HandleTelegramUpdate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

    config()->set('services.telegram.webhook_secret', 'shhh');
});

it('handles an update from Telegram', function () {
    TelegramAssistant::fake(['Hello there.']);

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'shhh')
        ->postJson(route('telegram.webhook'), [
            'message' => ['text' => 'Hi', 'chat' => ['id' => 42, 'first_name' => 'Jigar']],
        ])
        ->assertNoContent();

    TelegramAssistant::assertPrompted('Hi');
});

it('rejects a request without the secret token', function () {
    TelegramAssistant::fake();

    $this->postJson(route('telegram.webhook'), [
        'message' => ['text' => 'Hi', 'chat' => ['id' => 42]],
    ])->assertForbidden();

    TelegramAssistant::assertNeverPrompted();
});

it('answers outside the request so Telegram is not kept waiting', function () {
    Queue::fake();

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'shhh')
        ->postJson(route('telegram.webhook'), [
            'message' => ['text' => 'Hi', 'chat' => ['id' => 42]],
        ])
        ->assertNoContent();

    Queue::assertPushed(HandleTelegramUpdate::class);
});
