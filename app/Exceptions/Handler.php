<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use RealRashid\SweetAlert\Facades\Alert;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (\Exception $e) {
            if ($e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return redirect()->route('login');
            };
        });

        // Un error de base de datos (dato muy largo, restricción violada, etc.)
        // no debe mostrar un 500 en blanco: se redirige de vuelta al formulario
        // con un mensaje legible, igual que cualquier otro error de validación.
        $this->renderable(function (QueryException $e, $request) {
            if (!$request->expectsJson()) {
                Alert::error('Error', $this->mensajeQueryException($e));
                return redirect()->back()->withInput();
            }
        });
    }

    private function mensajeQueryException(QueryException $e): string
    {
        $codigo = $e->errorInfo[1] ?? null;
        return match ($codigo) {
            1406 => 'Uno de los datos ingresados es demasiado largo para el campo correspondiente. Reduzca el texto e intente de nuevo.',
            1062 => 'Ya existe un registro con esos mismos datos.',
            1451, 1452 => 'La operación no se pudo completar porque el registro está relacionado con otros datos del sistema.',
            default => 'Ocurrió un error al guardar los datos. Verifique la información e intente de nuevo.',
        };
    }
}
