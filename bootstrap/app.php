<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('piket:check-missed')->dailyAt('23:55')->withoutOverlapping();
        $schedule->command('piket:check-replacement-expiry')->dailyAt('06:00')->withoutOverlapping();
        $schedule->command('piket:check-redemption-expiry')->dailyAt('06:05')->withoutOverlapping();
        $schedule->command('piket:check-semester-end')->dailyAt('00:05')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'                 => \App\Http\Middleware\CheckRole::class,
            'ensure.profile'       => \App\Http\Middleware\EnsureStudentProfile::class,
            'check.email.verified' => \App\Http\Middleware\CheckEmailVerification::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
