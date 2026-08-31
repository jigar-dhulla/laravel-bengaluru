<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

    Str::createRandomStringsUsing(fn (): string => 'generated-secret');
});

afterEach(function () {
    Str::createRandomStringsNormally();
});

it('generates a secret, registers the webhook with it and prints it', function () {
    config()->set('services.telegram.webhook_secret', '');

    $this->artisan('telegram:webhook', ['url' => 'https://example.com/telegram/webhook'])
        ->expectsOutputToContain('TELEGRAM_WEBHOOK_SECRET=generated-secret')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/setWebhook')
        && $request['url'] === 'https://example.com/telegram/webhook'
        && $request['secret_token'] === 'generated-secret');
});

it('registers with the secret already configured', function () {
    config()->set('services.telegram.webhook_secret', 'already-set');

    $this->artisan('telegram:webhook', ['url' => 'https://example.com/telegram/webhook'])
        ->doesntExpectOutputToContain('already-set')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request['secret_token'] === 'already-set');
});

it('registers a fresh secret when told to regenerate', function () {
    config()->set('services.telegram.webhook_secret', 'already-set');

    $this->artisan('telegram:webhook', ['url' => 'https://example.com/telegram/webhook', '--regenerate' => true])
        ->expectsOutputToContain('TELEGRAM_WEBHOOK_SECRET=generated-secret')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request['secret_token'] === 'generated-secret');
});

it('registers the route URL when no URL is given', function () {
    config()->set('services.telegram.webhook_secret', 'already-set');

    $this->artisan('telegram:webhook')->assertSuccessful();

    Http::assertSent(fn ($request) => $request['url'] === route('telegram.webhook'));
});

it('removes the webhook', function () {
    $this->artisan('telegram:webhook', ['--remove' => true])->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/deleteWebhook'));
});
