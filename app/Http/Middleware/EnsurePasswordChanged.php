<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Finché la password è il numero di tessera, l'arbitro può solo cambiarla (o uscire). */
class EnsurePasswordChanged
{
    /** Rotte raggiungibili prima del cambio: oltre al cambio stesso, i link ricevuti via email. */
    private const ALLOWED_ROUTES = ['password.change', 'password.update', 'logout', 'designations.respond', 'profile.email.confirm'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
