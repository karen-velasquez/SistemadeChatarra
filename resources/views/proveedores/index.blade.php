@extends('layouts.app')
@section('titulo','proveedores')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>     
            <h1>REGISTRO proveedores</h1>
             <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Proveedores</li>
                    <li class="breadcrumb-item active">Listado de Proveedores</li>
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

                        {{-- ===== FILTROS ===== --}}
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold mb-1"><i class="bi bi-globe"></i> Tipo</label>
                                <select class="form-select" id="filtro_tipo_prov" onchange="aplicarFiltrosProv()">
                                    <option value="">— Todos —</option>
                                    <option value="NACIONAL">Nacional</option>
                                    <option value="INTERNACIONAL">Internacional</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold mb-1"><i class="bi bi-box-seam"></i> Tipo de Producto</label>
                                <select class="form-select" id="filtro_tipo_producto_prov" onchange="aplicarFiltrosProv()">
                                    <option value="">— Todos —</option>
                                    @foreach($tiposProducto as $tp)
                                        <option value="{{ $tp }}">{{ $tp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold mb-1"><i class="bi bi-flag"></i> País</label>
                                <select class="form-select" id="filtro_pais_prov" onchange="aplicarFiltrosProv()">
                                    <option value="">— Todos —</option>
                                    @foreach($paises as $pais)
                                        <option value="{{ $pais->id }}">{{ $pais->valor }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosProv()">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </button>
                            </div>
                        </div>

                            <div class="table-responsive" id="prov-contenido">
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
                                                <tr data-tipo="{{ $e->tipo_proveedor }}" data-tipo-producto="{{ strtoupper($e->tipo_producto ?? '') }}" data-pais-id="{{ $e->pais_id }}">
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

// ===== Filtros (Tipo / Tipo de Producto / País) sobre el DataTable ya inicializado por basica.js =====
let tablaProveedores = $('#datos').DataTable();

$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'datos') return true;
    const fila = tablaProveedores.row(dataIndex).node();
    const tipo         = document.getElementById('filtro_tipo_prov').value;
    const tipoProducto = document.getElementById('filtro_tipo_producto_prov').value;
    const paisId        = document.getElementById('filtro_pais_prov').value;

    const okTipo         = !tipo || fila.dataset.tipo === tipo;
    const okTipoProducto = !tipoProducto || fila.dataset.tipoProducto === tipoProducto;
    const okPais          = !paisId || fila.dataset.paisId === paisId;

    return okTipo && okTipoProducto && okPais;
});

function aplicarFiltrosProv() {
    tablaProveedores.draw();
}

function limpiarFiltrosProv() {
    document.getElementById('filtro_tipo_prov').value = '';
    document.getElementById('filtro_tipo_producto_prov').value = '';
    document.getElementById('filtro_pais_prov').value = '';
    aplicarFiltrosProv();
}

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
    e.preventDefault();
    var form = e.target;
    var btn = document.getElementById('btnProveedor');
    if (btn.disabled) return;
    var iconoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: new FormData(form),
    })
        .then(r => {
            if (r.status === 419 || r.redirected) return Promise.reject('sesion_expirada');
            return r.json().then(d => ({ status: r.status, ok: r.ok, d }));
        })
        .then(({ status, ok, d }) => {
            btn.disabled = false;
            btn.innerHTML = iconoOriginal;
            if (!ok) {
                if (status === 422 && d.errors) {
                    const primerError = Object.values(d.errors)[0]?.[0];
                    Swal.fire('Datos inválidos', primerError || 'Revise los datos del formulario.', 'error');
                } else {
                    Swal.fire('No se pudo guardar', d.message || 'No se pudo guardar el proveedor.', 'error');
                }
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('modalProveedor'))?.hide();
            recargarTablaProveedores();
            fetch('{{ route("proveedores.nuevo-token") }}')
                .then(r => r.json())
                .then(dt => { document.getElementById('idempotencyTokenProveedor').value = dt.token; });
            Swal.fire({ title: 'Guardado', text: d.message, icon: 'success', timer: 2000, showConfirmButton: false });
        })
        .catch((err) => {
            btn.disabled = false;
            btn.innerHTML = iconoOriginal;
            if (err === 'sesion_expirada') {
                Swal.fire({
                    title: 'Sesión expirada',
                    text: 'Tu sesión expiró. Recarga la página e inicia sesión de nuevo para continuar.',
                    icon: 'warning',
                    confirmButtonText: 'Recargar',
                }).then(() => window.location.reload());
                return;
            }
            Swal.fire('Error', 'Error al guardar el proveedor. Intente de nuevo.', 'error');
        });
});

// Recarga la tabla completa (manteniendo el filtro, que vive en los mismos
// inputs del DOM) reconstruyendo DataTables, igual que en pagos a clientes.
function recargarTablaProveedores() {
    fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevo = doc.getElementById('prov-contenido');
            const actual = document.getElementById('prov-contenido');
            if (!nuevo || !actual) return;

            if ($.fn.DataTable.isDataTable('#datos')) {
                tablaProveedores.destroy();
            }
            actual.innerHTML = nuevo.innerHTML;
            tablaProveedores = $('#datos').DataTable();
            aplicarFiltrosProv();
        });
}
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
