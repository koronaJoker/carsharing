<?php

use Illuminate\Console\Scheduling\Schedule;

test('active rental telemetry is scheduled every ten seconds', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'mongo-telemetry-sync');

    expect($event)->not->toBeNull()
        ->and($event->repeatSeconds)->toBe(10);
});
