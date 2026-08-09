<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Enviar recordatorios de torneos cuya fecha ya llegó.
Schedule::command('tournaments:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();

// Limpiar salas de minijuegos inactivas (evita acumular filas en la DB).
Schedule::command('familia:prune')
    ->hourly()
    ->withoutOverlapping();
