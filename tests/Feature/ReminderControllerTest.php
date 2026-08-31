<?php

use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-08-30 08:00:00');
});

it('lists upcoming reminders soonest first', function () {
    $chat = TelegramChat::factory()->create(['name' => 'Jigar']);
    Reminder::factory()->for($chat)->create([
        'body' => 'Renew the passport',
        'remind_at' => '2026-09-02 11:00:00',
    ]);
    Reminder::factory()->for($chat)->create([
        'body' => 'Call the dentist',
        'remind_at' => '2026-08-31 09:00:00',
    ]);

    $response = $this->get(route('reminders.index'));

    $response->assertSeeInOrder(['Call the dentist', 'Renew the passport'])
        ->assertSee('Mon 31 Aug, 09:00')
        ->assertSee('Jigar');
});

it('shows which chat each reminder belongs to', function () {
    Reminder::factory()->for(TelegramChat::factory()->create(['name' => 'Jigar']))->create();
    Reminder::factory()->for(TelegramChat::factory()->create(['name' => 'Priya']))->create();

    $response = $this->get(route('reminders.index'));

    $response->assertSee('Jigar')->assertSee('Priya');
});

it('separates sent reminders from the ones still waiting', function () {
    Reminder::factory()->delivered()->create(['body' => 'Already sent']);
    Reminder::factory()->create(['body' => 'Still waiting']);

    $response = $this->get(route('reminders.index'));

    $response->assertSeeInOrder(['Upcoming', 'Still waiting', 'Sent', 'Already sent']);
});

it('keeps only the twenty most recently sent reminders on the page', function () {
    Reminder::factory()->count(20)->delivered()->create(['delivered_at' => now()->subMinute()]);
    Reminder::factory()->delivered()->create([
        'body' => 'Oldest delivery',
        'delivered_at' => now()->subYear(),
    ]);

    $response = $this->get(route('reminders.index'));

    $response->assertDontSee('Oldest delivery')
        ->assertSee('last 20 of 21');
});

it('marks a reminder the bot has not delivered yet as due now', function () {
    Reminder::factory()->due()->create(['body' => 'Take the bins out']);

    $response = $this->get(route('reminders.index'));

    $response->assertSee('Take the bins out')->assertSee('due now');
});

it('invites the user to the chat when there is nothing to show', function () {
    $response = $this->get(route('reminders.index'));

    $response->assertSee('Message the bot on Telegram to set one.');
});

it('escapes html in a reminder body', function () {
    Reminder::factory()->create(['body' => '<script>alert(1)</script>']);

    $response = $this->get(route('reminders.index'));

    $response->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertSee('<script>alert(1)</script>');
});
