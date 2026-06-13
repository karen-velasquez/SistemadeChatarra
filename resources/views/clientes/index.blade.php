@extends('layouts.app')
@section('titulo','Cliente')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>GESTIÓN DE CLIENTES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"👥 Bienvenido al módulo <b>Clientes</b>: las personas o empresas a las que la empresa <b>vende</b> chatarra. Aquí los registras y mantienes sus datos al día."},
                        {"element":"#datos","intro":"📋 La lista de todos los clientes registrados, con su NIT, país, teléfonos, direcciones y email.","position":"top"},
                        {"element":"#datos thead th:nth-child(4)","intro":"📞 En <b>Teléfonos</b> y <b>Direcciones</b> un mismo cliente puede tener varios; se muestran como etiquetas.","position":"bottom"},
                        {"element":"#datos tbody tr:first-child .btn-group","intro":"⚙️ Con el botón <b>Opciones</b> de cada fila puedes <b>Modificar</b> o <b>Eliminar</b> el cliente.","position":"left"},
                        {"element":"#btnNuevoCliente","intro":"➕ Para registrar uno nuevo, pulsa <b>Nuevo Cliente</b>. Se abrirá un formulario con su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('clientes.create')
            <button type="button" id="btnNuevoCliente" class="btn btn-primary MB-3" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="resetModalCliente()"> <i class="bi bi-plus-lg"></i> Nuevo Cliente</button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h5 class="card-title mb-0">Clientes Registrados</h5>
                    </div>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>Administra el directorio de clientes con quienes la empresa realiza operaciones de compra o venta de chatarra. Aquí puedes registrar sus datos de contacto, documentos de identificación y direcciones para facilitar la gestión comercial.
                    </p>
                    <div class="table-responsive">
                        <table cellspacing="0" width="100%" id="datos" class="table table-hover table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">NOMBRE</th>
                                    <th class="text-center">NIT / CI / RUC</th>
                                    <th class="text-center">PAÍS</th>
                                    <th class="text-center">TELÉFONOS</th>
                                    <th class="text-center">DIRECCIONES</th>
                                    <th class="text-center">EMAIL</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientes as $c)
                                <tr>
                                    <td>{{ $c->nombre }}</td>
                                    <td>{{ $c->nit }}</td>
                                    <td>{{ $c->pais->valor ?? '-' }}</td>
                                    <td>
                                        @forelse($c->contacts->where('tipo','telefono') as $index => $contacto)
                                            <span class="badge bg-primary">{{ $contacto->valor }}</span><br>
                                        @empty
                                            <span class="text-muted">Sin teléfonos</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @forelse($c->contacts->where('tipo','direccion') as $index => $contacto)
                                            <span class="badge bg-secondary">Dir {{ $index + 1 }}: {{ $contacto->valor }}</span><br>
                                        @empty
                                            <span class="text-muted">Sin direcciones</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $c->email }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                            <ul class="dropdown-menu">
                                                @can('clientes.edit')
                                                <li><a class="dropdown-item" href="#" onclick="editarCliente({{ $c }})"><i class="bi bi-pencil"></i> Modificar</a></li>
                                                @endcan
                                                @can('clientes.destroy')
                                                <li><a class="dropdown-item text-danger" href="{{ route('clientes.destroy', $c->uuid) }}" onclick="return confirm('¿Eliminar este cliente?')"><i class="bi bi-trash"></i> Eliminar</a></li>
                                                @endcan
                                            </ul>
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
@include('clientes.modal')
@endsection
@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type ="text/javascript"></script>
<script src="{{ asset('assets/js/forms/contactosVarios.js') }}" type ="text/javascript"></script>
<script>
window.limpiarFormularioCliente = function () {
    document.getElementById('formCliente').reset();
    document.getElementById('telefonos-container').innerHTML = '';
    document.getElementById('direcciones-container').innerHTML = '';
    agregarTelefonoInput();
    agregarDireccionInput();
};
function resetModalCliente() {
    document.getElementById('tituloCliente').innerHTML ='<i class="bi bi-person-plus "></i> Nuevo Cliente';
    var btn = document.getElementById('btnCliente');
    btn.innerText = 'Registrar';
    btn.disabled = false;
    document.getElementById('methodCliente').value = 'POST';
    document.getElementById('formCliente').action = '{{ route("clientes.store")}}';
    fetch('{{ route("clientes.nuevo-token") }}')
        .then(r => r.json())
        .then(d => { document.getElementById('idempotencyTokenCliente').value = d.token; });
    limpiarFormularioCliente();
}
document.getElementById('formCliente').addEventListener('submit', function(e) {
    var btn = document.getElementById('btnCliente');
    if (btn.disabled) { e.preventDefault(); return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
});
function editarCliente(cliente) {
    const baseUrl = "{{ url('/') }}";
    document.getElementById('tituloCliente').innerHTML = '<i class="bi bi-pencil-square"></i> Editar Cliente';
    document.getElementById('btnCliente').innerText = 'Actualizar';
    document.getElementById('methodCliente').value = 'PUT';
    document.getElementById('formCliente').action = baseUrl + '/clientes/' + cliente.id;
    document.getElementById('cli_nombre').value = cliente.nombre ?? '';
    document.getElementById('cli_nit').value = cliente.nit ?? '';
    document.getElementById('cli_pais_id').value = cliente.pais_id ?? '';
    document.getElementById('cli_email').value = cliente.email ?? '';
    const telContainer = document.getElementById('telefonos-container');
    const dirContainer = document.getElementById('direcciones-container');
    telContainer.innerHTML = '';
    dirContainer.innerHTML = '';
    let telefonos = cliente.contacts?.filter(c => c.tipo === 'telefono') ?? [];
    let direcciones = cliente.contacts?.filter(c => c.tipo === 'direccion') ?? [];

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
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCliente')).show();
}
</script>
@endsection
