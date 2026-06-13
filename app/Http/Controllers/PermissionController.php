<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Parametro;

class PermissionController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function index()
    {
        $permisos=Permission::all();
        return view('permisos.index',compact('permisos'));
    }
    public function create()
    {
        $grupos = Parametro::where('tipo', 'grupos')->get();
        $permiso=new Permission();
        $idempotencyToken = $this->generarToken('permiso_store_token');
        return view('permisos.create',compact('permiso','grupos','idempotencyToken'));
    }
    public function store(Request $request)
    {
        if (!$this->tokenValido('permiso_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('permisos.index');
        }

        $permiso=Permission::create($request->all());
        Alert::success('Registrado','Permiso registrado con exito!');
        return redirect()->route('permisos.index');
    }
    public function edit(Permission $permiso)
    {
        $grupos = Parametro::where('tipo', 'grupos')->get();
        return view('permisos.edit',compact('permiso','grupos'));
    }

    public function update(Request $request, Permission $permiso)
    {
        $permiso->update($request->all());
        Alert::success('Actualizado','Permiso actualizado con exito!');
        return redirect()->route('permisos.index');
    }

    public function destroy(Permission $permiso)
    {
        $permiso->delete();
        Alert::success('Eliminado','Permiso eliminado con exito!');
        return redirect()->route('permisos.index');    }
}
