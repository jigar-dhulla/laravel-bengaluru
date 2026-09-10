<?php

use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->chat = TelegramChat::factory()->create(['telegram_id' => 42]);
});

/**
 * Http::fake() merges stubs rather than replacing them, so each test registers
 * the Bot API response it needs instead of sharing one from beforeEach.
 */
function fakeBotApi(int $status = 200, bool $ok = true): void
{
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => $ok, 'result' => []], $status)]);
}

it('sends a due reminder and marks it delivered', function () {
    fakeBotApi();

    $reminder = Reminder::factory()->for($this->chat)->due()->create(['body' => 'Call the dentist']);

    $this->artisan('reminders:deliver')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && str_contains($request['text'], 'Call the dentist')
        && $request['chat_id'] === 42);

    expect($reminder->fresh()->delivered_at)->not->toBeNull();
});

it('leaves reminders that are not due yet', function () {
    fakeBotApi();

    Reminder::factory()->for($this->chat)->create(['remind_at' => now()->addHour()]);

    $this->artisan('reminders:deliver')->assertSuccessful();

    Http::assertNothingSent();
});

it('does not send the same reminder twice', function () {
    fakeBotApi();

    Reminder::factory()->for($this->chat)->delivered()->create();

    $this->artisan('reminders:deliver')->assertSuccessful();

    Http::assertNothingSent();
});

it('retries a reminder the Bot API refused', function () {
    fakeBotApi(status: 500, ok: false);

    $reminder = Reminder::factory()->for($this->chat)->due()->create();

    $this->artisan('reminders:deliver')->assertSuccessful();

    expect($reminder->fresh()->delivered_at)->toBeNull();
});

it('catches up on reminders missed since the last run', function () {
    fakeBotApi();

    $missed = Reminder::factory()->for($this->chat)->create([
        'body' => 'Water the plants',
        'remind_at' => now()->subDays(3),
    ]);

    $this->artisan('reminders:deliver')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request['text'], 'Water the plants'));

    expect($missed->fresh()->delivered_at)->not->toBeNull();
});
