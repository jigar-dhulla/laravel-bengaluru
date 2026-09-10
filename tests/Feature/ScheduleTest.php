<?php

use Illuminate\Console\Scheduling\Schedule;

it('delivers reminders once a day at 9am', function () {
    $deliveries = collect(app(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains($event->command ?? '', 'reminders:deliver'));

    expect($deliveries)->toHaveCount(1)
        ->and($deliveries->first()->expression)->toBe('0 9 * * *');
});
