<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // L'app gira sempre dietro al reverse proxy interno (nginx + Nginx
        // Proxy Manager per HTTPS/Let's Encrypt): senza questo, Laravel non
        // si fida dell'header X-Forwarded-Proto e genera URL http:// (asset,
        // redirect) anche quando il sito è servito in https://, con
        // conseguente blocco "contenuto misto" nei browser (soprattutto
        // mobile). "*" è sicuro qui perché non c'è modo di raggiungere
        // l'app se non passando dai proxy della rete Docker interna.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
