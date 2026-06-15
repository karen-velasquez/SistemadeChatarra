<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Str;

trait PrevenirRegistroDoble
{
    protected function generarToken(string $clave): string
    {
        // Si ya hay un token en sesión lo reutilizamos para no invalidar
        // tokens que el usuario tiene abiertos en el campo del formulario.
        if (!session()->has($clave)) {
            session([$clave => Str::uuid()->toString()]);
        }
        return session($clave);
    }

    protected function forzarToken(string $clave): string
    {
        $token = Str::uuid()->toString();
        session([$clave => $token]);
        return $token;
    }

    protected function tokenValido(string $clave, ?string $tokenEnviado): bool
    {
        $tokenEnSesion = session($clave);
        if (!$tokenEnviado || $tokenEnviado !== $tokenEnSesion) {
            return false;
        }
        session()->forget($clave);
        return true;
    }
}
