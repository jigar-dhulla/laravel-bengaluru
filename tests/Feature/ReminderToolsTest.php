<?php

use App\Ai\Tools\CancelReminder;
use App\Ai\Tools\CreateReminder;
use App\Ai\Tools\ListReminders;
use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->chat = TelegramChat::factory()->create();

    Carbon::setTestNow('2026-08-30 08:00:00');
});

it('stores the reminder it is given', function () {
    $result = (new CreateReminder($this->chat))->handle(new Request([
        'body' => ' Call the dentist ',
        'remind_at' => '2026-08-31 09:00',
    ]));

    $reminder = $this->chat->reminders()->sole();

    expect($reminder->body)->toBe('Call the dentist')
        ->and($reminder->remind_at->toDateTimeString())->toBe('2026-08-31 09:00:00')
        ->and($result)->toContain('Reminder #'.$reminder->id)
        ->and($result)->toContain('Mon 31 Aug 2026, 09:00');
});

it('accepts the same moment written as iso 8601', function () {
    (new CreateReminder($this->chat))->handle(new Request([
        'body' => 'Call the dentist',
        'remind_at' => '2026-08-31T09:00:00',
    ]));

    expect($this->chat->reminders()->sole()->remind_at->toDateTimeString())
        ->toBe('2026-08-31 09:00:00');
});

it('tells the model to ask for a specific day when the date is unreadable', function () {
    $result = (new CreateReminder($this->chat))->handle(new Request([
        'body' => 'Call the dentist',
        'remind_at' => 'sometime next week',
    ]));

    expect($result)->toContain('Ask the user for a specific day and time')
        ->and($this->chat->reminders()->count())->toBe(0);
});

it('tells the model to ask when no date was passed at all', function () {
    $result = (new CreateReminder($this->chat))->handle(new Request([
        'body' => 'Call the dentist',
    ]));

    expect($result)->toContain('Ask the user when they want the reminder')
        ->and($this->chat->reminders()->count())->toBe(0);
});

it('refuses a time that has already gone by', function () {
    $result = (new CreateReminder($this->chat))->handle(new Request([
        'body' => 'Call the dentist',
        'remind_at' => '2026-08-30 07:00',
    ]));

    expect($result)->toContain('already gone by')
        ->and($this->chat->reminders()->count())->toBe(0);
});

it('lists the reminders that are still coming', function () {
    Reminder::factory()->for($this->chat)->create([
        'body' => 'Call the dentist',
        'remind_at' => '2026-08-31 09:00:00',
    ]);
    Reminder::factory()->for($this->chat)->delivered()->create(['body' => 'Already sent']);
    Reminder::factory()->create(['body' => 'Somebody else reminder']);

    $result = (new ListReminders($this->chat))->handle(new Request);

    expect($result)->toContain('Call the dentist')
        ->and($result)->toContain('Mon 31 Aug, 09:00')
        ->and($result)->not->toContain('Already sent')
        ->and($result)->not->toContain('Somebody else reminder');
});

it('lists delivered reminders when asked for them', function () {
    Reminder::factory()->for($this->chat)->delivered()->create(['body' => 'Already sent']);

    expect((new ListReminders($this->chat))->handle(new Request))
        ->not->toContain('Already sent');

    expect((new ListReminders($this->chat))->handle(new Request(['include_delivered' => true])))
        ->toContain('Already sent')
        ->toContain('(sent)');
});

it('cancels a pending reminder', function () {
    $reminder = Reminder::factory()->for($this->chat)->create();

    $result = (new CancelReminder($this->chat))->handle(new Request(['id' => $reminder->id]));

    expect($result)->toContain('Cancelled')
        ->and($this->chat->reminders()->count())->toBe(0);
});

it('will not cancel another chat\'s reminder', function () {
    $reminder = Reminder::factory()->create();

    $result = (new CancelReminder($this->chat))->handle(new Request(['id' => $reminder->id]));

    expect($result)->toContain('No reminder')
        ->and(Reminder::whereKey($reminder->id)->exists())->toBeTrue();
});
