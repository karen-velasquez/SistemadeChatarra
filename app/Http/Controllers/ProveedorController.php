<?php

namespace App\Http\Controllers;

use App\Models\Ciudad;
use App\Models\Contrato;
use App\Models\Proveedor;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Requests\ProveedorRequest;
use RealRashid\SweetAlert\Facades\Alert;

class ProveedorController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $paises=Parametro::where('tipo','paises')->get();
        $proveedores = Proveedor::with(['contacts', 'pais'])->whereNull('deleted_at')->orderby('created_at','desc')->get();
        $idempotencyToken = $this->generarToken('proveedor_store_token');
        return view('proveedores.index',compact('proveedores','paises','idempotencyToken'));
    }
      
    public function nuevoToken()
    {
        return response()->json([
            'token' => $this->generarToken('proveedor_store_token'),
        ]);
    }

    public function create()
    {
        $proveedor=new Proveedor();
        $paises=Parametro::where('tipo','paises')->get();
        return view('proveedores.create',compact('proveedor','paises'));
    }
    public function store(ProveedorRequest $request)
    {
        if (!$this->tokenValido('proveedor_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('proveedores.index');
        }

        $proveedor=Proveedor::create($request->all());
         $telefonos = $request->telefonos;
        $direcciones = $request->direcciones;
       if($request->has('contacts')) {
            foreach ($request->contacts as $contact) {
                if(!empty($contact['valor'])) {
                    $proveedor->contacts()->create($contact);
                }
            }
        }
       
        foreach ($telefonos as $telefono) {
            if (!empty($telefono)) {
                $proveedor->contacts()->create([
                    'tipo' => 'telefono',
                    'valor' => $telefono,
                ]);
             
            }
        }   
       
        foreach ($direcciones as $direccion) {
            if (!empty($direccion)) {
                $proveedor->contacts()->create([
                    'tipo' => 'direccion',
                    'valor' => $direccion,
                ]);
            }
        }   

        $proveedor->save();
    
        Alert::success('Registro', 'proveedor Registrado con exito!!!');
        return redirect()->route('proveedores.index');
    }

    public function edit($uuid)
    {
        $proveedor=Proveedor::where('uuid',$uuid)->firstOrFail();
        $paises=Parametro::where('tipo','paises')->get();
        return view('proveedores.edit',compact('proveedor','paises'));
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor)
        {
        $proveedor->update($request->only(['nombre','nit','pais_id','email','tipo_producto','tipo_proveedor']));
        $ids = [];
        foreach ($request->telefonos ?? [] as $tel) {
            if (!$tel) continue;
            $contact = $proveedor->contacts()->where('tipo', 'telefono')->where('valor', $tel)->first();
            if ($contact) {
                $contact->update([
                    'valor' => $tel
                ]);
            } else {
                $contact = $proveedor->contacts()->create([
                    'tipo' => 'telefono',
                    'valor' => $tel
                ]);
            }

            $ids[] = $contact->id;
        }

        foreach ($request->direcciones ?? [] as $dir) {
            if (!$dir) continue;
            $contact = $proveedor->contacts()->where('tipo', 'direccion')->where('valor', $dir)->first();
            if ($contact) {
                $contact->update([
                    'valor' => $dir
                ]);
            } else {
                $contact = $proveedor->contacts()->create([
                    'tipo' => 'direccion',
                    'valor' => $dir
                ]);
            }

            $ids[] = $contact->id;
        }
        $proveedor->contacts()->whereNotIn('id', $ids)->delete();
        Alert::success('Actualizacion', 'Proveedor Actualizado con exito!!!');
        return redirect()->route('proveedores.index');   
        }


    public function destroy($uuid)
    {
        $proveedor = Proveedor::where('uuid', $uuid)->firstOrFail();

        if (Contrato::where('proveedor_id', $proveedor->id)->exists()) {
            Alert::error('No permitido', 'Este proveedor no puede eliminarse porque está asociado a uno o más contratos.');
            return redirect()->route('proveedores.index');
        }

        $proveedor->delete();
        Alert::success('Eliminacion', 'Proveedor Eliminado con exito!!!');
        return redirect()->route('proveedores.index');
    }   
}
