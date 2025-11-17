<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;




Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('cache:clear', function () {
    Artisan::call('cache:clear');
    $this->info('Application cache cleared!');
})->purpose('Clear application cache');

Artisan::command('schedule:run', function () {
    Artisan::call('schedule:run');
    $this->info('Scheduled tasks executed!');
})->purpose('Run scheduled tasks manually');