<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckBlocked;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrar o middleware check.blocked como um alias
        $middleware->alias([
            'check.blocked' => CheckBlocked::class,
        ]);

        // Configurar o modelo personalizado para o Sanctum
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
