<?php

use App\Services\Telegram;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('handles a boolean result payload', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

    app(Telegram::class)->deleteWebhook();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/deleteWebhook'));
});

it('sends the secret token when registering the webhook', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

    app(Telegram::class)->setWebhook('https://example.com/telegram/webhook', 'shhh');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/setWebhook')
        && $request['url'] === 'https://example.com/telegram/webhook'
        && $request['secret_token'] === 'shhh');
});

it('returns an empty list when there are no updates', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    expect(app(Telegram::class)->getUpdates())->toBe([]);
});

it('throws when the Bot API reports a failure', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'])]);

    app(Telegram::class)->sendMessage(42, 'Hi');
})->throws(RuntimeException::class, 'chat not found');

it('retries without Markdown when Telegram rejects the formatting', function () {
    Http::fakeSequence()
        ->push(['ok' => false, 'description' => "can't parse entities"], 400)
        ->push(['ok' => true, 'result' => []]);

    app(Telegram::class)->sendMessage(42, 'unmatched *bold');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && ! isset($request['parse_mode']));
});

it('splits a reply longer than the Telegram limit', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

    app(Telegram::class)->sendMessage(42, str_repeat('a', 4500));

    Http::assertSentCount(2);
});

it('keeps the bot token out of connection error messages', function () {
    config()->set('services.telegram.token', 'secret-token');

    Http::fake(fn () => throw new ConnectionException(
        'cURL error 28: timed out for https://api.telegram.org/botsecret-token/getUpdates'
    ));

    try {
        app(Telegram::class)->getUpdates();

        $this->fail('Expected the client to throw.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->not->toContain('secret-token')
            ->toContain('<TELEGRAM_BOT_TOKEN>');
    }
});

it('keeps the bot token out of failed response messages', function () {
    config()->set('services.telegram.token', 'secret-token');

    Http::fake(['api.telegram.org/*' => Http::response(
        ['ok' => false, 'description' => 'bad token secret-token'], 401
    )]);

    try {
        app(Telegram::class)->getUpdates();
    } catch (RuntimeException $e) {
        expect($e->getMessage())
            ->not->toContain('secret-token')
            ->toContain('<TELEGRAM_BOT_TOKEN>');
    }
});
