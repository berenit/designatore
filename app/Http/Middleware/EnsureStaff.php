<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Riserva le funzioni di gestione a designatori e delegati: gli arbitri non vi accedono. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isReferee(), 403);

        return $next($request);
    }
}
