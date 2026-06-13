<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use RealRashid\SweetAlert\Facades\Alert;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $roles = Role::where('id','>',1)->withCount('users')->get();
        return view('roles.index',compact('roles'));
    }

    public function create()
    {
        $permissions=Permission::all();
        $grupos=Permission::select('grupo')->distinct()->get();
        $role= new Role();
        $idempotencyToken = $this->generarToken('role_store_token');
        return view('roles.create',compact('permissions','role','grupos','idempotencyToken'));
    }

    public function store(RoleRequest $request)
    {
        if (!$this->tokenValido('role_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('roles.index');
        }

        $role=Role::create(['name'=>$request->name,'descripcion'=>$request->descripcion,'guard_name'=>'web','uuid'=>\Illuminate\Support\Str::uuid()]);
        $role->permissions()->sync($request->get('permissions') ?? []);
        Alert::success('Guardado','Rol creado con exito!');
        return redirect()->route('roles.index');
    }

    public function show($uuid)
    {
        $role=Role::where('uuid',$uuid)->firstOrFail();
        $permissions=$role->permissions;
        $grupos=DB::table('role_has_permissions as rp')->join('permissions as p','p.id','=','rp.permission_id')->where('rp.role_id',$role->id)->select('grupo')->distinct()->get();
        //dd($grupos);
        return view('roles.show',compact('role','permissions','grupos'));
    }

    public function edit($uuid)
    {
        $role=Role::where('uuid',$uuid)->firstOrFail();
        $permissions=Permission::all();
        $grupos=Permission::select('grupo')->distinct()->get();
        //dd($grupos);
        return view('roles.edit',compact('role','permissions','grupos'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $role->update($request->only('name'));
        $permissions = Permission::whereIn('id', $request->get('permissions') ?? [])->get();
        $role->syncPermissions($permissions);
        Alert::success('Actualizado', 'Datos del Rol actualizado con éxito!');
        return redirect()->route('roles.index');
    }


    public function destroy($uuid)
    {
        $role = Role::where('uuid',$uuid)->withCount('users')->firstOrFail();

        // Verificar si el rol está siendo usado por usuarios
        if ($role->users_count > 0) {
            Alert::warning(
                'No se puede eliminar',
                "El rol '{$role->name}' no puede ser eliminado porque {$role->users_count} " .
                ($role->users_count == 1 ? 'usuario tiene' : 'usuarios tienen') .
                " este rol asignado."
            );
            return redirect()->route('roles.index');
        }

        // Eliminar permisos asociados al rol
        DB::table('role_has_permissions')->where('role_id',$role->id)->delete();

        // Eliminar el rol
        DB::table('roles')->where('id',$role->id)->delete();

        Alert::success('Eliminado','Rol eliminado con éxito!');
        return redirect()->route('roles.index');
    }
}
