<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Un sync completo toma unos minutos por el límite de 60 requests/minuto de la wiki.
Schedule::command('pcgw:sync')->dailyAt('04:00')->withoutOverlapping();

// Después del sync, para cubrir los juegos nuevos de la wiki.
Schedule::command('covers:sync')->dailyAt('05:00')->withoutOverlapping();
