<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * Si el navegador trae la cookie de sesión pero igual llegó aquí sin
     * autenticación, es que la sesión venció (o fue invalidada) mientras el
     * usuario seguía navegando — no que nunca inició sesión. En ese caso se
     * deja un aviso para mostrarlo en la pantalla de login, en vez del
     * silencio actual que solo lo manda de vuelta sin explicación.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson() && $request->cookie(config('session.cookie'))) {
            session()->flash('mensaje_sesion', 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión para continuar.');
        }

        return $request->expectsJson() ? null : route('login');
    }
}
