@extends('layouts.app')
@section('titulo','proveedores')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>     
            <h1>REGISTRO proveedores</h1>
             <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Proveedores</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"📦 Bienvenido al módulo <b>Proveedores</b>: las personas o empresas que le venden chatarra a la empresa. Aquí los registras y mantienes sus datos al día. Te muestro cómo funciona."},
                        {"element":"#datos","intro":"📋 Esta es la lista de todos los proveedores registrados, con su NIT, país, teléfonos, direcciones y tipo de producto.","position":"top"},
                        {"element":"#datos thead th:nth-child(4)","intro":"📞 En <b>Teléfonos</b> y <b>Direcciones</b> un mismo proveedor puede tener varios; se muestran como etiquetas.","position":"bottom"},
                        {"element":"#datos tbody tr:first-child .btn-group","intro":"⚙️ Con el botón <b>Opciones</b> de cada fila puedes <b>Ver información</b>, <b>Modificar</b> o <b>Eliminar</b> el proveedor.","position":"left"},
                        {"element":"#btnNuevoProveedor","intro":"➕ Para registrar uno nuevo, pulsa <b>Nuevo Proveedor</b>. Se abrirá un formulario con su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('proveedores.create')
            <button type="button" id="btnNuevoProveedor" class="btn btn-primary MB-3" data-bs-toggle="modal" data-bs-target="#modalProveedor" onclick="resetModalProveedor()"> <i class="bi bi-plus-lg"></i> Nuevo Proveedor</button>
            @endcan
        </div>
    </div>
</div>
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Proveedores Registrados</h5>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Gestiona el registro de proveedores que suministran materiales a la empresa.
                            Mantén actualizados sus datos de contacto, tipo de producto que ofrecen y documentación tributaria para agilizar los procesos de compra.
                        </p>
                            <div class="table-responsive">
                                <table cellspacing="0" width="100%" id="datos" class="table table-hover table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            
                                            <th class="text-left">Nombre</th>
                                            <th class="text-left">NIT / CI / RUC</th>
                                            <th class="text-left">Pais</th>
                                            <th class="text-left">TeléfonoS</th>
                                            <th class="text-left">Direcciones</th>
                                            <th class="text-left">Tipo de Producto</th>
                                            <th class="text-left">Tipo</th>
                                            <th class="text-left">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($proveedores as $e)
                                                <tr>
                                                     <td class="text-left">{{$e->nombre}}</td>
                                                    <td class="text-left">{{$e->nit}}</td>
                                                    <td class="text-left">{{$e->pais->valor ?? '-'}}</td>
                                                      <td>
                                                        @forelse($e->contacts->where('tipo','telefono') as $index => $contacto)
                                                            <span class="badge bg-primary">{{ $contacto->valor }}</span><br>
                                                        @empty
                                                            <span class="text-muted">Sin teléfonos</span>
                                                        @endforelse
                                                    </td>
                                                    <td>
                                                        @forelse($e->contacts->where('tipo','direccion') as $index => $contacto)
                                                            <span class="badge bg-secondary">
                                                                Dir {{ $index + 1 }}: {{ $contacto->valor }}
                                                            </span><br>
                                                        @empty
                                                            <span class="text-muted">Sin direcciones</span>
                                                        @endforelse
                                                    </td>
                                                    <td class="text-left">{{$e->tipo_producto}}</td>
                                                    <td class="text-center">
                                                        @if($e->tipo_proveedor === 'NACIONAL')
                                                            <span class="badge bg-success">NACIONAL</span>
                                                        @elseif($e->tipo_proveedor === 'INTERNACIONAL')
                                                            <span class="badge bg-info text-dark">INTERNACIONAL</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                       <td class="text-center">
                                                        <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    Opciones
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    @can('proveedores.show')
                                                                        <li><a class="dropdown-item" href="#" onclick="verProveedor({{ $e }})"><i class="bi bi-eye"></i> Ver información</a></li>
                                                                    @endcan
                                                                    @can('proveedores.edit')
                                                                        <li><a class="dropdown-item" href="#" onclick="editarProveedor({{ $e }})"><i class="bi bi-pencil"></i> Modificar</a></li>
                                                                    @endcan

                                                                    @can('proveedores.destroy')
                                                                         <li><a class="dropdown-item text-danger" href="{{ route('proveedores.destroy', $e->uuid) }}" onclick="return confirm('¿Eliminar este proveedor?')"><i class="bi bi-trash"></i> Eliminar</a></li>        
                                                                    @endcan
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

{{-- ===== MODAL VER INFORMACIÓN DEL PROVEEDOR ===== --}}
<div class="modal fade" id="modalVerProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-building"></i> Información del Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nombre</small>
                        <strong id="ver_prov_nombre">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">NIT / CI / RUC</small>
                        <strong id="ver_prov_nit">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">País</small>
                        <strong id="ver_prov_pais">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Email</small>
                        <strong id="ver_prov_email">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tipo de Producto</small>
                        <strong id="ver_prov_tipo_producto">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tipo de Proveedor</small>
                        <strong id="ver_prov_tipo_proveedor">—</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Teléfonos</small>
                        <div id="ver_prov_telefonos">—</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Direcciones</small>
                        <div id="ver_prov_direcciones">—</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Fecha de registro</small>
                        <strong id="ver_prov_fecha">—</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@include('proveedores.modal')
@endsection
@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/forms/contactosVarios.js') }}"type ="text/javascript"></script>
<script>
@if($errors->any())
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProveedor')).show();
});
@endif

 window.limpiarFormularioProveedor = function () {
    document.getElementById('formProveedor').reset();
    document.getElementById('telefonos-container').innerHTML = '';
    document.getElementById('direcciones-container').innerHTML = '';
    agregarTelefonoInput();
    agregarDireccionInput();
};
   
function resetModalProveedor() {
    document.getElementById('tituloProveedor').innerHTML ='<i class="bi bi-building "></i> Nuevo Proveedor';
    var btn = document.getElementById('btnProveedor');
    btn.innerText = 'Registrar';
    btn.disabled = true;
    document.getElementById('methodProveedor').value = 'POST';
    document.getElementById('formProveedor').action = '{{ route("proveedores.store")}}';
    fetch('{{ route("proveedores.nuevo-token") }}')
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(d => {
            document.getElementById('idempotencyTokenProveedor').value = d.token;
            document.getElementById('btnProveedor').disabled = false;
        })
        .catch(() => { document.getElementById('btnProveedor').disabled = false; });
    limpiarFormularioProveedor();
}
document.getElementById('formProveedor').addEventListener('submit', function(e) {
    var btn = document.getElementById('btnProveedor');
    if (btn.disabled) { e.preventDefault(); return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
});
function verProveedor(proveedor) {
    document.getElementById('ver_prov_nombre').textContent          = proveedor.nombre || '—';
    document.getElementById('ver_prov_nit').textContent             = proveedor.nit || '—';
    document.getElementById('ver_prov_pais').textContent            = proveedor.pais?.valor || '—';
    document.getElementById('ver_prov_email').textContent           = proveedor.email || '—';
    document.getElementById('ver_prov_tipo_producto').textContent   = proveedor.tipo_producto || '—';
    document.getElementById('ver_prov_tipo_proveedor').textContent  = proveedor.tipo_proveedor || '—';
    document.getElementById('ver_prov_fecha').textContent           = proveedor.created_at
        ? new Date(proveedor.created_at).toLocaleDateString('es-BO')
        : '—';

    const telefonos   = proveedor.contacts?.filter(c => c.tipo === 'telefono') ?? [];
    const direcciones = proveedor.contacts?.filter(c => c.tipo === 'direccion') ?? [];

    document.getElementById('ver_prov_telefonos').innerHTML = telefonos.length
        ? telefonos.map(t => `<span class="badge bg-primary me-1 mb-1">${t.valor}</span>`).join('')
        : '<span class="text-muted">Sin teléfonos</span>';

    document.getElementById('ver_prov_direcciones').innerHTML = direcciones.length
        ? direcciones.map((d, i) => `<span class="badge bg-secondary d-block mb-1">Dir ${i + 1}: ${d.valor}</span>`).join('')
        : '<span class="text-muted">Sin direcciones</span>';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerProveedor')).show();
}

function editarProveedor(proveedor) {
    const baseUrl = "{{ url('/') }}";
    document.getElementById('tituloProveedor').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Proveedor';
    document.getElementById('btnProveedor').innerText = 'Actualizar';
    document.getElementById('methodProveedor').value = 'PUT';
    document.getElementById('formProveedor').action = baseUrl + '/proveedores/' + proveedor.id;
    document.getElementById('prov_nombre').value = proveedor.nombre ?? '';
    document.getElementById('prov_nit').value = proveedor.nit ?? '';
    document.getElementById('prov_pais').value = proveedor.pais_id ?? '';
    document.getElementById('prov_email').value = proveedor.email ?? '';
    document.getElementById('prov_tipo_producto').value = proveedor.tipo_producto ?? '';
    document.getElementById('prov_tipo_proveedor').value = proveedor.tipo_proveedor ?? '';

    const telContainer = document.getElementById('telefonos-container');
    const dirContainer = document.getElementById('direcciones-container');
    telContainer.innerHTML = '';
    dirContainer.innerHTML = '';
    let telefonos = proveedor.contacts?.filter(c => c.tipo === 'telefono') ?? [];
    let direcciones = proveedor.contacts?.filter(c => c.tipo === 'direccion') ?? [];

    if (telefonos.length === 0) {
        agregarTelefonoInput('', true);
    } else {
        telefonos.forEach((t, i) => agregarTelefonoInput(t.valor, i === 0));
    }

    if (direcciones.length === 0) {
        agregarDireccionInput('', true);
    } else {
        direcciones.forEach((d, i) => agregarDireccionInput(d.valor, i === 0));
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProveedor')).show();
}
</script>
@endsection
