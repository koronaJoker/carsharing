<?php

use App\Services\MongoTelemetryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telemetry:mongo-sync', function () {
    app(MongoTelemetryService::class)->captureActiveRentalsSnapshot();

    $this->info('Mongo telemetry snapshot completed.');
})->purpose('Write active rentals logs and locations to MongoDB');

Schedule::call(function () {
    app(MongoTelemetryService::class)->captureActiveRentalsSnapshot();
})->everyTenSeconds()->name('mongo-telemetry-sync')->withoutOverlapping();
