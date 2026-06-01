<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Requests\ClienteRequest;
use RealRashid\SweetAlert\Facades\Alert;

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $clientes = Cliente::with(['contacts', 'pais'])->whereNull('deleted_at')->orderby('created_at','desc')->get();
        $paises = Parametro::where('tipo','paises')->get();
        return view('clientes.index',compact('clientes','paises'));
    }

 public function create()
{
    $clientes = Cliente::with(['contacts', 'pais'])->get();
    $paises = Parametro::where('tipo','paises')->get();
    return view('clientes.index', ['clientes' => $clientes,'paises' => $paises,'abrirModal' => 'create']);
}

public function store(ClienteRequest $request)
    {
        $telefonos = $request->telefonos;
        $direcciones = $request->direcciones;
        $cliente=Cliente::create($request->all());
        if($request->has('contacts')) {
            foreach ($request->contacts as $contact) {
                if(!empty($contact['valor'])) {
                    $cliente->contacts()->create($contact);
                }
            }
        }
        foreach ($telefonos as $telefono) {
            if (!empty($telefono)) {
                $cliente->contacts()->create([
                    'tipo' => 'telefono',
                    'valor' => $telefono,
                ]);
             
            }
        }   
        foreach ($direcciones as $direccion) {
            if (!empty($direccion)) {
                $cliente->contacts()->create([
                    'tipo' => 'direccion',
                    'valor' => $direccion,
                ]);
            }
        }   
        $cliente->save();
        Alert::success('Registro', 'Cliente Registrado con exito!!!');
        return redirect()->route('clientes.index');
    }

 
    public function edit($uuid)
    {
        $cliente=Cliente::with(['contacts', 'pais'])->where('uuid',$uuid)->firstOrFail();
        $clientes = Cliente::with(['contacts', 'pais'])->get();
        $paises=Parametro::where('tipo','paises')->get();
        return view('clientes.index', ['clientes' => $clientes,'paises' => $paises,'abrirModal' => 'edit','clienteEditar' => $cliente]);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->only(['nombre','nit','pais_id','email']));
        $ids = [];
        foreach ($request->telefonos ?? [] as $tel) {
            if (!$tel) continue;
            $contact = $cliente->contacts()->where('tipo', 'telefono')->where('valor', $tel)->first();
            if ($contact) {
                $contact->update([
                    'valor' => $tel
                ]);
            } else {
                $contact = $cliente->contacts()->create([
                    'tipo' => 'telefono',
                    'valor' => $tel
                ]);
            }

            $ids[] = $contact->id;
        }

        foreach ($request->direcciones ?? [] as $dir) {
            if (!$dir) continue;
            $contact = $cliente->contacts()->where('tipo', 'direccion')->where('valor', $dir)->first();
            if ($contact) {
                $contact->update([
                    'valor' => $dir
                ]);
            } else {
                $contact = $cliente->contacts()->create([
                    'tipo' => 'direccion',
                    'valor' => $dir
                ]);
            }

            $ids[] = $contact->id;
        }
        $cliente->contacts()->whereNotIn('id', $ids)->delete();
        Alert::success('Actualizacion', 'Cliente Actualizado con exito!!!');
        return redirect()->route('clientes.index');   
    }
    public function destroy($uuid)
    {
        $cliente=Cliente::where('uuid',$uuid)->firstOrFail();
        $cliente->delete();
        Alert::success('Eliminacion', 'Cliente Eliminado con exito!!!');
        return redirect()->route('clientes.index');
    }   
}
