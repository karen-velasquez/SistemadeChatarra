@extends('layouts.app')
@section('titulo','Transporte')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>GESTIÓN DE TRANSPORTE</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Transporte</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                data-steps='[
                    {"intro":"🚚 Bienvenido al módulo de <b>Transporte</b>. Aquí administras la flota de la empresa. Está dividido en pestañas; veamos la de <b>Camiones</b>."},
                    {"element":"#transporteTabs","intro":"🗂️ El módulo tiene 3 pestañas: <b>Camiones</b> (los vehículos), <b>Propietarios / Conductores</b> (las personas) y <b>Asignación de Conductores</b> (qué chofer maneja cada camión).","position":"bottom"},
                    {"element":"#tab-camiones","intro":"🚛 Esta es la pestaña de <b>Camiones</b>, donde registras cada vehículo de la flota.","position":"bottom"},
                    {"element":"#tablaCamiones","intro":"📋 La lista de camiones registrados con su placa, tipo, marca, capacidad, propietario y estado.","position":"top"},
                    {"element":"#tablaCamiones tbody tr:first-child .btn-group","intro":"⚙️ Con <b>Opciones</b> en cada fila puedes Modificar el camión, ver sus Fotos, ver el documento RUAT o Eliminarlo.","position":"left"},
                    {"element":"#btnNuevoCamion","intro":"➕ Para registrar un camión nuevo, pulsa <b>Nuevo Camión</b>. El formulario tiene su propia guía ❓.","position":"left"}
                ]'>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body pt-3">

                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Módulo de gestión del parque vehicular de la empresa. Administra los camiones, sus propietarios y conductores,
                        y controla qué conductor está asignado a cada vehículo en cada momento.
                    </p>

                    {{-- ===== SLIDER / TABS ===== --}}
                    <ul class="nav nav-tabs" id="transporteTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-camiones" data-bs-toggle="tab" data-bs-target="#pane-camiones" type="button" role="tab">
                                <i class="bi bi-truck"></i> Camiones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-operadores" data-bs-toggle="tab" data-bs-target="#pane-operadores" type="button" role="tab">
                                <i class="bi bi-person-workspace"></i> Propietarios / Conductores
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-asignaciones" data-bs-toggle="tab" data-bs-target="#pane-asignaciones" type="button" role="tab">
                                <i class="bi bi-person-check"></i> Asignación de Conductores
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="transporteTabContent">

                        {{-- ========== TAB CAMIONES ========== --}}
                        <div class="tab-pane fade" id="pane-camiones" role="tabpanel">
                            <p class="text-muted small mb-2">
                                <i class="bi bi-truck me-1"></i>
                                Registra los vehículos de la flota con sus datos técnicos, propietario asignado, documento RUAT y fotos.
                                Puedes ver el conductor activo de cada camión en tiempo real.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Camiones Registrados</h5>
                                @can('camiones.create')
                                <button type="button" id="btnNuevoCamion" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCamion" onclick="resetModalCamion()">
                                    <i class="bi bi-plus-lg"></i> Nuevo Camión
                                </button>
                                @endcan
                            </div>
                            <div class="table-responsive">
                                <table id="tablaCamiones" class="table table-hover table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Tipo</th>
                                            <th>Marca / Modelo</th>
                                            <th>Año</th>
                                            <th>Cap. (t)</th>
                                            <th>Color</th>
                                            <th>Propietario</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($camiones as $c)
                                        <tr>
                                            <td>{{ $c->placa }}</td>
                                            <td>{{ $c->tipoVehiculo->valor ?? '-' }}</td>
                                            <td>{{ $c->marca->valor ?? '-' }} {{ $c->modelo }}</td>
                                            <td>{{ $c->anio }}</td>
                                            <td>{{ number_format($c->capacidad_kg / 1000, 3) }} t</td>
                                            <td>{{ $c->color ?? '-' }}</td>
                                            <td>{{ $c->propietario?->nombre_completo ?? '-' }}</td>
                                            <td>
                                                @if($c->estado === 'Activo')
                                                    <span class="badge bg-success">{{ $c->estado }}</span>
                                                @elseif($c->estado === 'Inactivo')
                                                    <span class="badge bg-danger">{{ $c->estado }}</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">{{ $c->estado }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                                    <ul class="dropdown-menu">
                                                        @can('camiones.edit')
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                               onclick="editarCamionPorUuid('{{ $c->uuid }}'); return false;">
                                                                <i class="bi bi-pencil"></i> Modificar
                                                            </a>
                                                        </li>
                                                        @endcan
                                                        @can('camiones.index')
                                                        <li>
                                                            @if($c->fotos->count())
                                                            <a class="dropdown-item" href="#"
                                               onclick="verFotosDesdeData(this); return false;"
                                               data-fotos="{{ htmlspecialchars(json_encode($c->fotos), ENT_QUOTES, 'UTF-8') }}"
                                               data-placa="{{ $c->placa }}">
                                                                <i class="bi bi-images"></i> Ver Fotos ({{ $c->fotos->count() }})
                                                            </a>
                                                            @else
                                                            <span class="dropdown-item text-muted">
                                                                <i class="bi bi-images"></i> Sin fotos
                                                            </span>
                                                            @endif
                                                        </li>
                                                        <li>
                                                            @if($c->documento_ruat)
                                                            <a class="dropdown-item" href="{{ route('camiones.ruat', $c->uuid) }}" target="_blank">
                                                                <i class="bi bi-file-earmark-pdf"></i> Ver RUAT
                                                            </a>
                                                            @else
                                                            <span class="dropdown-item text-muted">
                                                                <i class="bi bi-file-earmark-pdf"></i> Sin RUAT
                                                            </span>
                                                            @endif
                                                        </li>
                                                        @endcan
                                                        @can('camiones.destroy')
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="{{ route('camiones.destroy', $c->uuid) }}"
                                                                onclick="return confirm('¿Eliminar este camión?')">
                                                                <i class="bi bi-trash"></i> Eliminar
                                                            </a>
                                                        </li>
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

                        {{-- ========== TAB OPERADORES ========== --}}
                        <div class="tab-pane fade" id="pane-operadores" role="tabpanel">
                            <p class="text-muted small mb-2">
                                <i class="bi bi-person-workspace me-1"></i>
                                Administra las personas vinculadas al transporte: propietarios de vehículos y conductores.
                                Puedes registrar sus datos personales, licencia de conducir y documentos de identidad.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Propietarios y Conductores</h5>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                                            data-steps='[
                                                {"intro":"👷 En esta pestaña registras a las <b>personas</b> del transporte: los <b>propietarios</b> de los camiones y los <b>conductores</b> (choferes). Una misma persona puede ser ambas cosas."},
                                                {"element":"#tablaOperadores","intro":"📋 Lista de propietarios y conductores con su documento, teléfono, tipo, licencia y estado.","position":"top"},
                                                {"element":"#tablaOperadores thead th:nth-child(4)","intro":"🏷️ La columna <b>Tipo</b> indica si la persona es Propietario, Chofer o ambos.","position":"bottom"},
                                                {"element":"#tablaOperadores thead th:nth-child(7)","intro":"📅 El <b>vencimiento de licencia</b>: si ya venció, se marca en rojo con ⚠ para que renueves a tiempo.","position":"bottom"},
                                                {"element":"#tablaOperadores tbody tr:first-child .btn-group","intro":"⚙️ Con <b>Opciones</b> puedes Modificar, ver el Carnet, ver la Licencia o Eliminar.","position":"left"},
                                                {"element":"#btnNuevoOperador","intro":"➕ Para registrar una persona nueva, pulsa <b>Nuevo Operador</b>. El formulario tiene su propia guía ❓.","position":"left"}
                                            ]'>
                                        <i class="bi bi-question-circle"></i>
                                    </button>
                                    @can('operadores.create')
                                    <button type="button" id="btnNuevoOperador" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOperador" onclick="resetModalOperador()">
                                        <i class="bi bi-plus-lg"></i> Nuevo Operador
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="tablaOperadores" class="table table-hover table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Doc. Identidad</th>
                                            <th>Teléfono</th>
                                            <th>Tipo</th>
                                            <th>Licencia</th>
                                            <th>País Lic.</th>
                                            <th>Venc. Licencia</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($operadores as $o)
                                        <tr>
                                            <td>{{ $o->nombre_completo }}</td>
                                            <td>
                                                {{ $o->ci }}
                                                @if($o->ciPais)
                                                    <small class="text-muted d-block">{{ $o->ciPais->valor }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $o->telefono ?? '-' }}</td>
                                            <td>
                                                @if($o->tipo_operador === 'ambos')
                                                    <span class="badge bg-primary">Propietario / Chofer</span>
                                                @elseif($o->tipo_operador === 'propietario')
                                                    <span class="badge bg-info text-dark">Propietario</span>
                                                @else
                                                    <span class="badge bg-secondary">Chofer</span>
                                                @endif
                                            </td>
                                            <td>{{ $o->licencia_numero ?? '-' }}</td>
                                            <td>{{ $o->licenciaPais->valor ?? '-' }}</td>
                                            <td>
                                                @if($o->licencia_vencimiento)
                                                    @if($o->licencia_vencimiento->isPast())
                                                        <span class="text-danger fw-bold">{{ $o->licencia_vencimiento->format('d/m/Y') }} ⚠</span>
                                                    @else
                                                        {{ $o->licencia_vencimiento->format('d/m/Y') }}
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $o->estado === 'Activo' ? 'bg-success' : 'bg-danger' }}">{{ $o->estado }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                                    <ul class="dropdown-menu">
                                                        @can('operadores.edit')
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="editarOperadorPorUuid('{{ $o->uuid }}'); return false;">
                                                                <i class="bi bi-pencil"></i> Modificar
                                                            </a>
                                                        </li>
                                                        @endcan
                                                        <li>
                                                            @if($o->doc_carnet)
                                                            <a class="dropdown-item" href="{{ route('operadores.carnet', $o->uuid) }}" target="_blank">
                                                                <i class="bi bi-person-badge"></i> Ver Carnet
                                                            </a>
                                                            @else
                                                            <span class="dropdown-item text-muted"><i class="bi bi-person-badge"></i> Sin carnet</span>
                                                            @endif
                                                        </li>
                                                        @if(in_array($o->tipo_operador, ['chofer','ambos']))
                                                        <li>
                                                            @if($o->doc_licencia)
                                                            <a class="dropdown-item" href="{{ route('operadores.licencia', $o->uuid) }}" target="_blank">
                                                                <i class="bi bi-card-text"></i> Ver Licencia
                                                            </a>
                                                            @else
                                                            <span class="dropdown-item text-muted"><i class="bi bi-card-text"></i> Sin licencia</span>
                                                            @endif
                                                        </li>
                                                        @endif
                                                        @can('operadores.destroy')
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="{{ route('operadores.destroy', $o->uuid) }}"
                                                                onclick="return confirm('¿Eliminar este operador?')">
                                                                <i class="bi bi-trash"></i> Eliminar
                                                            </a>
                                                        </li>
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

                        {{-- ========== TAB ASIGNACIONES ========== --}}
                        <div class="tab-pane fade" id="pane-asignaciones" role="tabpanel">
                            <p class="text-muted small mb-2">
                                <i class="bi bi-person-check me-1"></i>
                                Controla qué conductor opera cada camión y en qué período. Al finalizar una asignación queda registrada en el historial,
                                permitiendo tener trazabilidad completa del uso de cada vehículo.
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Asignación de Conductores a Camiones</h5>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                                            data-steps='[
                                                {"intro":"🔗 En esta pestaña controlas <b>qué conductor maneja cada camión</b> y en qué período. Al finalizar una asignación queda en el historial, así tienes trazabilidad completa."},
                                                {"element":"#tablaAsignaciones","intro":"📋 Lista de asignaciones: el camión, su conductor, la licencia y las fechas de inicio y fin.","position":"top"},
                                                {"element":"#tablaAsignaciones thead th:nth-child(6)","intro":"🚦 La columna <b>Estado</b>: <b>Activo</b> (el conductor maneja el camión ahora) o <b>Finalizado</b> (asignación pasada, ya en el historial).","position":"bottom"},
                                                {"element":"#tablaAsignaciones tbody tr:first-child td:last-child","intro":"⏹️ En las asignaciones <b>activas</b> aparece el botón <b>Finalizar</b>, que cierra la asignación y la pasa al historial.","position":"left"},
                                                {"element":"#btnNuevaAsignacion","intro":"➕ Para asignar un conductor a un camión, pulsa <b>Nueva Asignación</b>. El formulario tiene su propia guía ❓.","position":"left"}
                                            ]'>
                                        <i class="bi bi-question-circle"></i>
                                    </button>
                                    @can('conductores.create')
                                    <button type="button" id="btnNuevaAsignacion" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAsignacion">
                                        <i class="bi bi-plus-lg"></i> Nueva Asignación
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="tablaAsignaciones" class="table table-hover table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Camión</th>
                                            <th>Conductor</th>
                                            <th>Licencia</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                            <th>Observaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($asignaciones as $a)
                                        <tr>
                                            <td>{{ $a->camion->placa }} - {{ $a->camion->marca->valor ?? '-' }}</td>
                                            <td>{{ $a->conductor->nombre_completo }}</td>
                                            <td>{{ $a->conductor->licencia_numero }}</td>
                                            <td>{{ $a->fecha_inicio->format('d/m/Y') }}</td>
                                            <td>{{ $a->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                            <td>
                                                @if(is_null($a->fecha_fin))
                                                    <span class="badge bg-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary">Finalizado</span>
                                                @endif
                                            </td>
                                            <td>{{ $a->observaciones ?? '-' }}</td>
                                            <td class="text-center">
                                                @if(is_null($a->fecha_fin))
                                                    @can('conductores.edit')
                                                    <a href="{{ route('conductores.finalizar', $a->uuid) }}"
                                                        class="btn btn-warning btn-sm"
                                                        onclick="return confirm('¿Finalizar esta asignación?')">
                                                        <i class="bi bi-stop-circle"></i> Finalizar
                                                    </a>
                                                    @endcan
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>{{-- fin tab-content --}}
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== MODAL CAMIÓN ===== --}}
<div class="modal fade" id="modalCamion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck"></i> <span id="tituloCamion">Nuevo Camión</span></h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalCamion"
                            data-steps='[
                                {"intro":"📝 Formulario para registrar un <b>camión</b>. Te explico cada campo. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#cam_placa_pais_id","intro":"🌎 <b>País de la Placa</b>: elígelo primero, porque define el <b>formato</b> que tendrá la placa abajo.","position":"bottom"},
                                {"element":"#cam_placa","intro":"🔖 <b>Placa</b> del vehículo. El sistema aplica automáticamente el formato del país (ej. Bolivia: 2345-ABC).","position":"bottom"},
                                {"element":"#cam_tipo_vehiculo_id","intro":"🚛 <b>Tipo de Vehículo</b>: tracto, semirremolque, plataforma, volqueta, etc.","position":"bottom"},
                                {"element":"#cam_estado","intro":"🚦 <b>Estado</b> del camión: Activo, Inactivo o En mantenimiento.","position":"bottom"},
                                {"element":"#cam_marca_id","intro":"🏭 <b>Marca</b> del camión (Volvo, Scania, Mercedes-Benz...).","position":"bottom"},
                                {"element":"#cam_modelo","intro":"🔧 <b>Modelo</b> del camión (ej: FH 460).","position":"bottom"},
                                {"element":"#cam_anio","intro":"📆 <b>Año</b> del vehículo (entre 1970 y el año actual).","position":"bottom"},
                                {"element":"#cam_capacidad","intro":"⚖️ <b>Capacidad</b> de carga en toneladas (mínimo 3.5 t). Es clave para asignar cargas que no superen su límite.","position":"top"},
                                {"element":"#cam_color","intro":"🎨 <b>Color</b> del camión (opcional).","position":"top"},
                                {"element":"#cam_propietario","intro":"👤 <b>Propietario</b>: a quién pertenece el camión. Se elige de los operadores registrados como propietarios.","position":"top"},
                                {"element":"#cam_ruat","intro":"📎 <b>Documento RUAT</b> en PDF (opcional): el registro del vehículo como respaldo.","position":"top"},
                                {"element":"#btnAgregarFoto","intro":"📷 <b>Fotos</b>: puedes subir hasta 5 fotos del camión para identificarlo fácilmente.","position":"top"},
                                {"element":"#btnCamion","intro":"💾 Cuando termines, pulsa <b>Registrar</b> para guardar el camión.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formCamion" method="POST" action="{{ route('camiones.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodCamion" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenCamion" value="{{ $tokenCamion ?? '' }}">
                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                    <div class="row g-3">

                        {{-- Fila 1: País placa / Placa / Tipo / Estado --}}
                        <div class="col-md-3">
                            <label class="form-label">País de la Placa <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('placa_pais_id') is-invalid @enderror"
                                name="placa_pais_id" id="cam_placa_pais_id"
                                onchange="actualizarFormatoPlaca()" required>
                                <option value="">-- SELECCIONE UN PAIS --</option>
                                @foreach($paises as $pais)
                                    <option value="{{ $pais->id }}">{{ $pais->valor }}</option>
                                @endforeach
                            </select>
                            @error('placa_pais_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Placa <span class="text-danger">(*)</span></label>
                            <input type="text"
                                class="form-control @error('placa') is-invalid @enderror"
                                name="placa" id="cam_placa"
                                placeholder="Ej: 2345-ABC"
                                oninput="aplicarMascaraPlaca(this)"
                                maxlength="10"
                                required>
                            <small id="cam_placa_hint" class="text-muted">Bolivia: 2345-ABC (4 dígitos-3 letras)</small>
                            @error('placa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Vehículo <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('tipo_vehiculo_id') is-invalid @enderror" name="tipo_vehiculo_id" id="cam_tipo_vehiculo_id" required>
                                <option value="">-- SELECCIONE UN TIPO --</option>
                                @foreach($tiposVehiculo as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->valor }}</option>
                                @endforeach
                            </select>
                            @error('tipo_vehiculo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('estado') is-invalid @enderror" name="estado" id="cam_estado" required>
                                @foreach(['Activo','Inactivo','En mantenimiento'] as $est)
                                    <option value="{{ $est }}">{{ $est }}</option>
                                @endforeach
                            </select>
                            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 2: Marca / Modelo / Año --}}
                        <div class="col-md-4">
                            <label class="form-label">Marca <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('marca_id') is-invalid @enderror" name="marca_id" id="cam_marca_id" required>
                                <option value="">-- SELECCIONE UNA MARCA --</option>
                                @foreach($marcas as $marca)
                                    <option value="{{ $marca->id }}">{{ $marca->valor }}</option>
                                @endforeach
                            </select>
                            @error('marca_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Modelo <span class="text-danger">(*)</span></label>
                            <input type="text"
                                class="form-control @error('modelo') is-invalid @enderror"
                                name="modelo" id="cam_modelo"
                                onkeyup="this.value=this.value.toUpperCase()"
                                maxlength="50"
                                placeholder="Ej: FH 460"
                                required>
                            @error('modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Año <span class="text-danger">(*)</span></label>
                            <input type="text"
                                class="form-control @error('anio') is-invalid @enderror"
                                name="anio" id="cam_anio"
                                maxlength="4"
                                placeholder="{{ date('Y') }}"
                                inputmode="numeric"
                                autocomplete="off"
                                required>
                            <small id="anio_hint" class="text-muted">Entre 1970 y {{ date('Y') }}</small>
                            @error('anio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 3: Capacidad / Color / Propietario --}}
                        <div class="col-md-4">
                            <label class="form-label">Capacidad (toneladas) <span class="text-danger">(*)</span></label>
                            <input type="text"
                                class="form-control @error('capacidad_tn') is-invalid @enderror"
                                name="capacidad_tn" id="cam_capacidad"
                                inputmode="decimal"
                                placeholder="Ej: 25.5"
                                autocomplete="off"
                                required>
                            <small id="capacidad_hint" class="text-muted">Mínimo 3.5 tn — máximo 3 decimales.</small>
                            @error('capacidad_tn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="text"
                                class="form-control"
                                name="color" id="cam_color"
                                onkeyup="this.value=this.value.toUpperCase()"
                                maxlength="30"
                                placeholder="Ej: BLANCO">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Propietario <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('propietario_id') is-invalid @enderror"
                                name="propietario_id" id="cam_propietario" required>
                                <option value="">-- Seleccione propietario --</option>
                                @foreach($operadores->whereIn('tipo_operador', ['propietario','ambos']) as $op)
                                    <option value="{{ $op->id }}">{{ $op->nombre_completo }} ({{ $op->ci }})</option>
                                @endforeach
                            </select>
                            @error('propietario_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Documento RUAT --}}
                        <div class="col-md-6">
                            <label class="form-label">Documento RUAT <small class="text-muted">(PDF, máx. 5 MB)</small></label>
                            <input type="file" class="form-control @error('documento_ruat') is-invalid @enderror"
                                name="documento_ruat" id="cam_ruat" accept=".pdf">
                            <div id="ruatActualInfo" class="mt-1 d-none">
                                <span class="text-success"><i class="bi bi-file-earmark-pdf"></i> Ya tiene RUAT cargado.</span>
                                <small class="text-muted">Seleccionar uno nuevo lo reemplazará.</small>
                            </div>
                            @error('documento_ruat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fotos del camión --}}
                        <div class="col-12">
                            <label class="form-label">Fotos del Camión <small class="text-muted">(JPG/PNG/WEBP, máx. 5 fotos, 4 MB c/u)</small></label>

                            {{-- Input oculto — se activa con el botón --}}
                            <input type="file" id="cam_fotos_input" accept=".jpg,.jpeg,.png,.webp"
                                class="d-none" onchange="agregarFotosNuevas(this)">

                            {{-- Botón para agregar fotos --}}
                            <div class="mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarFoto"
                                    onclick="document.getElementById('cam_fotos_input').click()">
                                    <i class="bi bi-plus-lg"></i> Agregar foto
                                </button>
                                <small class="text-muted ms-2" id="fotosContadorLabel">0 / 5 fotos</small>
                            </div>

                            {{-- Lista visual de fotos a subir (nuevas) --}}
                            <div id="fotosNuevasLista" class="d-flex flex-wrap gap-2 mb-2"></div>

                            {{-- Fotos ya guardadas en BD (modo edición) --}}
                            <div id="galeriaFotos" class="d-none">
                                <label class="form-label text-muted mb-1"><i class="bi bi-images"></i> Fotos guardadas</label>
                                <div id="galeriaFotosContenido" class="d-flex flex-wrap gap-2"></div>
                            </div>

                            @error('fotos')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @error('fotos.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnCamion">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL GALERÍA DE FOTOS ===== --}}
<div class="modal fade" id="modalGaleria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-images"></i> Fotos — <span id="galeriaPlaca"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="galeriaViewer" class="row g-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- ===== MODAL OPERADOR ===== --}}
<div class="modal fade" id="modalOperador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-workspace"></i> <span id="tituloOperador">Nuevo Operador</span></h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalOperador"
                            data-steps='[
                                {"intro":"📝 Formulario para registrar a una persona del transporte (propietario y/o chofer). Te explico cada campo. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#op_nombre","intro":"🧑 <b>Nombre(s)</b> de la persona. Solo admite letras y se pasa a mayúsculas.","position":"bottom"},
                                {"element":"#op_apellido_paterno","intro":"👤 <b>Apellido Paterno</b>. Obligatorio.","position":"bottom"},
                                {"element":"#op_apellido_materno","intro":"👤 <b>Apellido Materno</b>. Obligatorio.","position":"bottom"},
                                {"element":"#op_email","intro":"✉️ <b>Email</b> (opcional). Se valida el formato automáticamente.","position":"bottom"},
                                {"element":"#op_ci_pais_id","intro":"🌎 <b>País del Documento</b>: elígelo primero, porque define el formato del número de documento.","position":"bottom"},
                                {"element":"#op_ci","intro":"🪪 <b>N° de Documento</b> de identidad (CI, DNI, etc.), según el país elegido.","position":"bottom"},
                                {"element":"#op_tipo","intro":"🏷️ <b>Tipo de Operador</b>: Propietario, Chofer o ambos. Si es chofer, aparecerán los datos de su <b>licencia</b>.","position":"bottom"},
                                {"element":"#op_telefono","intro":"📞 <b>Teléfono</b>: elige el país (prefijo) y escribe el número; se valida según el formato del país.","position":"top"},
                                {"element":"#op_direccion","intro":"📍 <b>Dirección</b> (opcional).","position":"top"},
                                {"element":"#op_estado","intro":"🚦 <b>Estado</b>: Activo o Inactivo.","position":"top"},
                                {"element":"#seccionLicencia","intro":"🪪 <b>Datos de Licencia</b>: esta sección aparece solo si la persona es <b>Chofer</b> o ambos. Incluye N° de licencia, país y vencimiento.","position":"top"},
                                {"element":"#btnOperador","intro":"💾 Cuando termines, pulsa <b>Registrar</b> para guardar.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formOperador" method="POST" action="{{ route('operadores.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="methodOperador" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenOperador" value="{{ $tokenOperador ?? '' }}">
                <div class="modal-body">
                    <p>Los campos con <strong class="text-danger">(*)</strong> son obligatorios.</p>
                    <div class="row g-3">

                        {{-- Fila 1: Nombre | Apellido Paterno | Apellido Materno | Email --}}
                        <div class="col-md-3">
                            <label class="form-label">Nombre(s) <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" id="op_nombre"
                                onkeypress="return /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]$/.test(event.key)"
                                oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'').toUpperCase()"
                                required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Apellido Paterno <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control @error('apellido_paterno') is-invalid @enderror" name="apellido_paterno" id="op_apellido_paterno"
                                onkeypress="return /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]$/.test(event.key)"
                                oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'').toUpperCase()"
                                required>
                            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Apellido Materno <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control @error('apellido_materno') is-invalid @enderror" name="apellido_materno" id="op_apellido_materno"
                                onkeypress="return /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]$/.test(event.key)"
                                oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'').toUpperCase()"
                                required>
                            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" id="op_email"
                                oninput="validarEmail(this)"
                                placeholder="correo@ejemplo.com">
                            <div id="op_email_feedback" class="form-text d-none"></div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 2: País del Documento | N° Documento | Tipo Operador --}}
                        <div class="col-md-4">
                            <label class="form-label">País del Documento <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('ci_pais_id') is-invalid @enderror" name="ci_pais_id" id="op_ci_pais_id" onchange="actualizarFormatoCI()" required>
                                <option value="">-- SELECCIONE UN PAÍS --</option>
                                @foreach($paisesDocumento as $pais)
                                    <option value="{{ $pais->id }}">{{ $pais->valor }}</option>
                                @endforeach
                            </select>
                            @error('ci_pais_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">N° Documento de Identidad <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control @error('ci') is-invalid @enderror" name="ci" id="op_ci" maxlength="20" required>
                            <small id="op_ci_hint" class="text-muted">Seleccione primero el país del documento</small>
                            @error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo Operador <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('tipo_operador') is-invalid @enderror" name="tipo_operador" id="op_tipo" onchange="toggleLicencia()" required>
                                <option value="propietario">Propietario</option>
                                <option value="chofer">Chofer</option>
                                <option value="ambos">Propietario y Chofer</option>
                            </select>
                            @error('tipo_operador')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fila 3: Teléfono | Dirección | Estado --}}
                        <div class="col-md-5">
                            <label class="form-label">Teléfono <span class="text-danger">(*)</span></label>
                            <div class="input-group">
                                <select id="op_telefono_pais" class="form-select flex-grow-0" style="width:115px; min-width:115px; max-width:115px;"
                                    onchange="actualizarPrefijoTelefono()">
                                    <option value="Bolivia" data-code="+591" data-maxlen="8" data-placeholder="Ej: 70000000">BO +591</option>
                                    <option value="Argentina" data-code="+54" data-maxlen="10" data-placeholder="Ej: 1150000000">AR +54</option>
                                    <option value="Brasil" data-code="+55" data-maxlen="11" data-placeholder="Ej: 11900000000">BR +55</option>
                                    <option value="Chile" data-code="+56" data-maxlen="9" data-placeholder="Ej: 912345678">CL +56</option>
                                    <option value="Paraguay" data-code="+595" data-maxlen="9" data-placeholder="Ej: 981000000">PY +595</option>
                                    <option value="Perú" data-code="+51" data-maxlen="9" data-placeholder="Ej: 912345678">PE +51</option>
                                    <option value="Colombia" data-code="+57" data-maxlen="10" data-placeholder="Ej: 3001234567">CO +57</option>
                                    <option value="Ecuador" data-code="+593" data-maxlen="10" data-placeholder="Ej: 0991234567">EC +593</option>
                                    <option value="Uruguay" data-code="+598" data-maxlen="8" data-placeholder="Ej: 91234567">UY +598</option>
                                    <option value="Venezuela" data-code="+58" data-maxlen="10" data-placeholder="Ej: 4121234567">VE +58</option>
                                    <option value="México" data-code="+52" data-maxlen="10" data-placeholder="Ej: 5512345678">MX +52</option>
                                    <option value="Estados Unidos" data-code="+1" data-maxlen="10" data-placeholder="Ej: 2025550100">US +1</option>
                                    <option value="Canadá" data-code="+1" data-maxlen="10" data-placeholder="Ej: 4165550100">CA +1</option>
                                    <option value="España" data-code="+34" data-maxlen="9" data-placeholder="Ej: 612345678">ES +34</option>
                                </select>
                                <input type="hidden" name="telefono_prefijo" id="op_telefono_prefijo" value="+591">
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                    name="telefono" id="op_telefono"
                                    maxlength="8" placeholder="Ej: 70000000"
                                    oninput="validarTelefono(this)"
                                    required>
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div id="op_telefono_feedback" class="form-text d-none"></div>
                            <small id="op_telefono_hint" class="text-muted">Bolivia: 8 dígitos comenzando en 6 o 7</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion" id="op_direccion" onkeyup="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="estado" id="op_estado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>

                    </div>

                    {{-- Sección licencia (se muestra/oculta según tipo) --}}
                    <div id="seccionLicencia" class="row g-3 mt-2 border rounded p-2 bg-light" style="display:none!important">
                        <h6 class="text-primary mb-0"><i class="bi bi-card-text"></i> Datos de Licencia de Conducir</h6>
                        <div class="col-md-4">
                            <label class="form-label">N° Licencia <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control @error('licencia_numero') is-invalid @enderror"
                                name="licencia_numero" id="op_licencia_num" maxlength="30"
                                onkeyup="this.value=this.value.toUpperCase()">
                            @error('licencia_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">País de Expedición <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('licencia_pais_id') is-invalid @enderror" name="licencia_pais_id" id="op_licencia_pais_id">
                                <option value="">-- SELECCIONE UN PAÍS --</option>
                                @foreach($paisesDocumento as $pais)
                                    <option value="{{ $pais->id }}">{{ $pais->valor }}</option>
                                @endforeach
                            </select>
                            @error('licencia_pais_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Vencimiento <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control @error('licencia_vencimiento') is-invalid @enderror"
                                name="licencia_vencimiento" id="op_licencia_venc">
                            @error('licencia_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Documentos --}}
                    <div class="row g-3 mt-2 border rounded p-2">
                        <h6 class="text-secondary mb-0"><i class="bi bi-paperclip"></i> Documentos <small class="text-muted">(PDF, JPG o PNG, máx. 5 MB c/u)</small></h6>

                        {{-- Carnet de identidad — siempre visible --}}
                        <div class="col-md-6">
                            <label class="form-label">Carnet de Identidad</label>
                            <input type="file" class="form-control @error('doc_carnet') is-invalid @enderror"
                                name="doc_carnet" id="op_doc_carnet"
                                accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div id="op_carnet_info" class="mt-1 d-none">
                                <span class="text-success small"><i class="bi bi-check-circle"></i> Ya tiene carnet cargado.</span>
                                <small class="text-muted">Seleccionar uno nuevo lo reemplazará.</small>
                            </div>
                            @error('doc_carnet')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Licencia — solo visible si es chofer o ambos --}}
                        <div class="col-md-6 d-none" id="seccionDocLicencia">
                            <label class="form-label">Documento de Licencia de Conducir</label>
                            <input type="file" class="form-control @error('doc_licencia') is-invalid @enderror"
                                name="doc_licencia" id="op_doc_licencia"
                                accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div id="op_licencia_info" class="mt-1 d-none">
                                <span class="text-success small"><i class="bi bi-check-circle"></i> Ya tiene licencia cargada.</span>
                                <small class="text-muted">Seleccionar una nueva la reemplazará.</small>
                            </div>
                            @error('doc_licencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnOperador">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL ASIGNACIÓN CONDUCTOR ===== --}}
<div class="modal fade" id="modalAsignacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-check"></i> Asignar Conductor a Camión</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalAsignacion"
                            data-steps='[
                                {"intro":"📝 Aquí asignas un <b>conductor</b> a un <b>camión</b>. Es muy rápido: solo 2 campos. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#asig_camion_id","intro":"🚛 <b>Camión</b>: elige primero el camión. Al seleccionarlo se cargan los conductores disponibles para él.","position":"bottom"},
                                {"element":"#asig_conductor_id","intro":"👷 <b>Conductor</b>: se habilita tras elegir el camión. Muestra los conductores disponibles, excluyendo los ya asignados a ese camión.","position":"bottom"},
                                {"element":"[name=\"observaciones\"]","intro":"📝 <b>Observaciones</b> (opcional): cualquier nota sobre esta asignación.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formConductor" method="POST" action="{{ route('conductores.store') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenConductor" value="{{ $tokenConductor ?? '' }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Camión <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('camion_id') is-invalid @enderror"
                                name="camion_id" id="asig_camion_id"
                                onchange="cargarConductoresCamion(this)" required>
                                <option value="">-- Seleccione camión --</option>
                                @foreach($camiones as $c)
                                    <option value="{{ $c->id }}" data-uuid="{{ $c->uuid }}">
                                        {{ $c->placa }} — {{ $c->marca->valor ?? '-' }} {{ $c->modelo }} ({{ number_format($c->capacidad_kg/1000,1) }} t)
                                    </option>
                                @endforeach
                            </select>
                            @error('camion_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Conductor <span class="text-danger">(*)</span></label>
                            <select class="form-select @error('conductor_id') is-invalid @enderror"
                                name="conductor_id" id="asig_conductor_id" required disabled>
                                <option value="">— Primero seleccione un camión —</option>
                            </select>
                            <div id="asig_conductor_hint" class="form-text text-muted d-none">
                                <i class="bi bi-info-circle"></i>
                                Se muestran todos los conductores disponibles, excluyendo los que ya están asignados a este camión.
                            </div>
                            @error('conductor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const _dtOpciones = {
        language: {
            processing: "Procesando...",
            lengthMenu: 'Filtrar <select>' +
                '<option value="10">10</option>' +
                '<option value="20">20</option>' +
                '<option value="30">30</option>' +
                '<option value="50">50</option>' +
                '<option value="-1">Todos</option>' +
                '</select> Registros',
            paginate:   { sFirst: "Primero", sLast: "Último" },
            info:       "Pagina _PAGE_ de _PAGES_",
            search:     "Buscador Gral",
            emptyTable: "No existen datos registrados.",
            infoEmpty:  "",
        },
        orderCellsTop: true,
        fixedHeader:   true,
        ordering:      false,
        pageLength:    50,
    };

    let _dtCamiones, _dtOperadores, _dtAsignaciones;

    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar cada tabla la primera vez que se muestra su pestaña (incluye la activación inicial por ?tab=)
        document.querySelectorAll('#transporteTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
            btn.addEventListener('shown.bs.tab', function () {
                const target = btn.getAttribute('data-bs-target');
                if (target === '#pane-camiones' && !_dtCamiones) {
                    _dtCamiones = $('#tablaCamiones').DataTable(_dtOpciones);
                } else if (target === '#pane-operadores' && !_dtOperadores) {
                    _dtOperadores = $('#tablaOperadores').DataTable(_dtOpciones);
                } else if (target === '#pane-asignaciones' && !_dtAsignaciones) {
                    _dtAsignaciones = $('#tablaAsignaciones').DataTable(_dtOpciones);
                }
            });
        });
    });
</script>
<script>
    // Activar tab correcto al redirigir con parámetro ?tab=
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        const tabMap = {
            'camiones'    : 'tab-camiones',
            'operadores'  : 'tab-operadores',
            'asignaciones': 'tab-asignaciones',
        };
        const tabId = tabMap[tab] || 'tab-camiones';
        const trigger = document.getElementById(tabId);
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();

        // Si hay errores de validación, reabrir el modal correspondiente
        @if($errors->has('placa') || $errors->has('tipo_vehiculo') || $errors->has('marca'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCamion')).show();
            bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-camiones')).show();
        @endif
        @if($errors->has('nombre') || $errors->has('ci') || $errors->has('tipo_operador'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOperador')).show();
            bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-operadores')).show();
            toggleLicencia();
        @endif
        @if($errors->has('camion_id') || $errors->has('conductor_id'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAsignacion')).show();
            bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-asignaciones')).show();
        @endif
    });

    // Mostrar/ocultar sección licencia según tipo operador
    // Prefijos telefónicos por país
    const _prefijos = {
        'Bolivia': { code: '+591', hint: '8 dígitos, comienza en 6 o 7', pattern: /^[67]\d{7}$/ },
        'Argentina': { code: '+54', hint: 'Ej: 1150000000', pattern: /^\d{8,10}$/ },
        'Brasil': { code: '+55', hint: 'Ej: 11900000000', pattern: /^\d{9,11}$/ },
        'Chile': { code: '+56', hint: 'Ej: 912345678', pattern: /^\d{9}$/ },
        'Paraguay': { code: '+595', hint: 'Ej: 0981000000', pattern: /^\d{8,10}$/ },
        'Perú': { code: '+51', hint: 'Ej: 912345678', pattern: /^\d{9}$/ },
        'Colombia': { code: '+57', hint: 'Ej: 3001234567', pattern: /^\d{10}$/ },
        'Ecuador': { code: '+593', hint: 'Ej: 0991234567', pattern: /^\d{9,10}$/ },
        'Uruguay': { code: '+598', hint: 'Ej: 91234567', pattern: /^\d{8}$/ },
        'Venezuela': { code: '+58', hint: 'Ej: 4121234567', pattern: /^\d{10}$/ },
        'México': { code: '+52', hint: 'Ej: 5512345678', pattern: /^\d{10}$/ },
        'Estados Unidos': { code: '+1', hint: 'Ej: 2025550100', pattern: /^\d{10}$/ },
        'Canadá': { code: '+1', hint: 'Ej: 4165550100', pattern: /^\d{10}$/ },
        'España': { code: '+34', hint: 'Ej: 612345678', pattern: /^\d{9}$/ },
    };

    function actualizarPrefijoTelefono() {
        const sel    = document.getElementById('op_telefono_pais');
        const opt    = sel.options[sel.selectedIndex];
        const code   = opt ? (opt.dataset.code || '+') : '+';
        const pais   = opt ? opt.value : '';
        const maxlen = opt && opt.dataset.maxlen ? parseInt(opt.dataset.maxlen) : 15;
        const ph     = opt && opt.dataset.placeholder ? opt.dataset.placeholder : 'Número';
        const info   = _prefijos[pais] || { hint: 'Ingrese el número sin código de país' };

        const inp = document.getElementById('op_telefono');
        document.getElementById('op_telefono_prefijo').value = code;
        document.getElementById('op_telefono_hint').textContent = info.hint;
        inp.maxLength   = maxlen;
        inp.placeholder = ph;
        inp.value       = ''; // limpiar al cambiar país
        // limpiar feedback al cambiar país
        const fb = document.getElementById('op_telefono_feedback');
        fb.className = 'form-text d-none';
        inp.classList.remove('is-valid', 'is-invalid');
    }

    function validarTelefono(input) {
        // solo dígitos
        input.value = input.value.replace(/[^0-9]/g, '');

        const pais   = document.getElementById('op_telefono_pais').value;
        const info   = _prefijos[pais];
        const fb     = document.getElementById('op_telefono_feedback');
        const val    = input.value;
        const maxlen = parseInt(input.maxLength) || 15;

        if (!val) {
            fb.className = 'form-text d-none';
            input.classList.remove('is-valid', 'is-invalid');
            return;
        }

        if (!info || !info.pattern) {
            fb.className = 'form-text d-none';
            input.classList.remove('is-valid', 'is-invalid');
            return;
        }

        const completo = val.length === maxlen;
        const valido   = info.pattern.test(val);

        if (valido && completo) {
            // longitud correcta Y patrón OK → verde
            fb.className   = 'form-text text-success';
            fb.textContent = '✓ Número válido';
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else if (!valido && completo) {
            // longitud completa pero patrón falla → rojo
            fb.className   = 'form-text text-danger';
            fb.textContent = '✗ Formato incorrecto — ' + info.hint;
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        } else {
            // aún escribiendo → sin color, sin mensaje
            fb.className = 'form-text d-none';
            input.classList.remove('is-valid', 'is-invalid');
        }
    }

    // Reglas de formato de documento por país
    const _formatosCI = {
        // Solo dígitos
        'BOLIVIA':            { tipo: 'digits', maxlen: 10, hint: 'Solo números, ej: 1234567' },
        'BRASIL':             { tipo: 'digits', maxlen: 11, hint: 'CPF: 11 dígitos, ej: 12345678901' },
        'PARAGUAY':           { tipo: 'digits', maxlen: 8,  hint: '8 dígitos, ej: 12345678' },
        'ECUADOR':            { tipo: 'digits', maxlen: 10, hint: '10 dígitos (cédula), ej: 1234567890' },
        'URUGUAY':            { tipo: 'digits', maxlen: 8,  hint: '8 dígitos, ej: 12345678' },
        // Alfanumérico (letras y números)
        'ARGENTINA':          { tipo: 'alphanum', maxlen: 9,  hint: 'DNI: 7-9 dígitos, ej: 12345678' },
        'CHILE':              { tipo: 'alphanum', maxlen: 10, hint: 'RUN con dígito verificador, ej: 12345678K' },
        'PERU':               { tipo: 'alphanum', maxlen: 9,  hint: 'DNI: 8 dígitos o Pasaporte, ej: 12345678' },
        'COLOMBIA':           { tipo: 'alphanum', maxlen: 11, hint: 'Cédula de ciudadanía, ej: 1234567890' },
        'VENEZUELA':          { tipo: 'alphanum', maxlen: 9,  hint: 'V-12345678 (sin el prefijo)' },
        'GUYANA':             { tipo: 'alphanum', maxlen: 15, hint: 'Pasaporte o ID nacional' },
        'GUAYANA_FRANCESA':   { tipo: 'alphanum', maxlen: 15, hint: 'Pasaporte o ID nacional' },
        'SURINAM':            { tipo: 'alphanum', maxlen: 15, hint: 'ID nacional o Pasaporte' },
        'El Salvador':        { tipo: 'alphanum', maxlen: 10, hint: 'DUI: 9 dígitos' },
        'Nicaragua':          { tipo: 'alphanum', maxlen: 16, hint: 'Cédula: 14-16 caracteres' },
        'Cuba':               { tipo: 'alphanum', maxlen: 11, hint: '11 dígitos' },
    };

    function actualizarFormatoCI() {
        const selectPais = document.getElementById('op_ci_pais_id');
        const paisTexto = selectPais.options[selectPais.selectedIndex]?.text || '';
        const pais = paisTexto;
        const input  = document.getElementById('op_ci');
        const hint   = document.getElementById('op_ci_hint');
        const regla  = _formatosCI[pais];

        input.value = ''; // limpiar al cambiar país

        if (!regla) {
            input.removeAttribute('maxlength');
            input.removeAttribute('pattern');
            input.oninput = null;
            hint.textContent = 'Seleccione primero el país del documento';
            return;
        }

        input.maxLength = regla.maxlen;
        hint.textContent = regla.hint;

        if (regla.tipo === 'digits') {
            input.oninput = function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            };
            input.pattern = '[0-9]+';
        } else {
            input.oninput = function() {
                this.value = this.value.replace(/[^a-zA-Z0-9\-]/g, '').toUpperCase();
            };
            input.pattern = '[a-zA-Z0-9\\-]+';
        }
    }

    function validarEmail(input) {
        const fb = document.getElementById('op_email_feedback');
        const val = input.value.trim();
        if (!val) {
            fb.className = 'form-text d-none';
            input.classList.remove('is-valid', 'is-invalid');
            return;
        }
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        if (ok) {
            fb.className = 'form-text text-success';
            fb.textContent = '✓ Formato de correo válido';
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            fb.className = 'form-text text-danger';
            fb.textContent = '✗ Formato incorrecto. Ej: nombre@dominio.com';
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    }

    // Formatos de placa por país
    const _formatosPlaca = {
        'BOLIVIA':    { maxlen: 10, placeholder: 'Ej: 2345-ABC',    hint: '4 dígitos + guion + 3 letras (2345-ABC)',          pattern: /^[0-9]{4}-[A-Z]{3}$/ },
        'ARGENTINA':  { maxlen: 8,  placeholder: 'Ej: AB123CD',     hint: '2 letras + 3 dígitos + 2 letras (AB123CD) o 3+3',  pattern: /^[A-Z]{2}[0-9]{3}[A-Z]{2}$|^[A-Z]{3}[0-9]{3}$/ },
        'BRASIL':     { maxlen: 8,  placeholder: 'Ej: ABC1D23',     hint: '3 letras + 1 dígito + 1 letra + 2 dígitos',        pattern: /^[A-Z]{3}[0-9][A-Z][0-9]{2}$|^[A-Z]{3}[0-9]{4}$/ },
        'CHILE':      { maxlen: 7,  placeholder: 'Ej: ABCD12',      hint: '4 letras + 2 dígitos o 2 letras + 4 dígitos',      pattern: /^[A-Z]{4}[0-9]{2}$|^[A-Z]{2}[0-9]{4}$/ },
        'PARAGUAY':   { maxlen: 8,  placeholder: 'Ej: ABCD123',     hint: '4 letras + 3 dígitos',                             pattern: /^[A-Z]{4}[0-9]{3}$/ },
        'Otro':       { maxlen: 15, placeholder: 'Ingrese la placa', hint: 'Ingrese la placa tal como aparece',               pattern: /^[A-Z0-9\-]{2,15}$/ },
    };

    function actualizarFormatoPlaca() {
        const selectPais = document.getElementById('cam_placa_pais_id');
        const paisTexto = selectPais.options[selectPais.selectedIndex]?.text || '';
        const input = document.getElementById('cam_placa');
        const hint  = document.getElementById('cam_placa_hint');
        const regla = _formatosPlaca[paisTexto];

        input.value = ''; // limpiar al cambiar país
        input.classList.remove('is-valid', 'is-invalid');

        if (!regla) {
            input.maxLength   = 15;
            input.placeholder = 'Ingrese la placa';
            hint.textContent  = 'Seleccione primero el país';
            return;
        }

        input.maxLength   = regla.maxlen;
        input.placeholder = regla.placeholder;
        hint.textContent  = regla.hint;
    }

    function aplicarMascaraPlaca(input) {
        const selectPais = document.getElementById('cam_placa_pais_id');
        const pais = selectPais.options[selectPais.selectedIndex]?.text || '';
        const regla = _formatosPlaca[pais];
        let val     = input.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
        const cur   = input.selectionStart;

        switch (pais) {
            case 'BOLIVIA':
                // 4 dígitos + guion + 3 letras → 2345-ABC
                val = val.replace(/[^0-9A-Z\-]/g, '');
                // extraer solo dígitos y letras sin el guión
                let digits = val.replace(/\-/g, '');
                let nums   = digits.replace(/[^0-9]/g, '').slice(0, 4);
                let lets   = digits.replace(/[^A-Z]/g, '').slice(0, 3);
                val = nums.length === 4 ? nums + '-' + lets : nums;
                break;

            case 'ARGENTINA':
                // AB123CD (nuevo) o ABC123 (viejo) — solo alfanumérico, sin guión
                val = val.replace(/[^A-Z0-9]/g, '').slice(0, 8);
                break;

            case 'BRASIL':
                // ABC1D23 (Mercosur) o ABC1234 — alfanumérico
                val = val.replace(/[^A-Z0-9]/g, '').slice(0, 8);
                break;

            case 'CHILE':
                // ABCD12 o AB1234 — alfanumérico
                val = val.replace(/[^A-Z0-9]/g, '').slice(0, 7);
                break;

            case 'PARAGUAY':
                // ABCD123 — 4 letras + 3 dígitos
                val = val.replace(/[^A-Z0-9]/g, '');
                let pyLets = val.replace(/[^A-Z]/g, '').slice(0, 4);
                let pyNums = val.replace(/[^0-9]/g, '').slice(0, 3);
                val = pyLets + pyNums;
                break;

            default:
                // Otros: solo alfanumérico y guión
                val = val.replace(/[^A-Z0-9\-]/g, '');
                if (regla) val = val.slice(0, regla.maxlen);
                break;
        }

        input.value = val;

        // Validación visual al completar
        if (regla && regla.pattern && val.length >= (regla.maxlen - 2)) {
            if (regla.pattern.test(val)) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else if (val.length === regla.maxlen) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-valid', 'is-invalid');
            }
        } else {
            input.classList.remove('is-valid', 'is-invalid');
        }
    }

    function toggleLicencia() {
        const tipo = document.getElementById('op_tipo').value;
        const seccionLic = document.getElementById('seccionLicencia');
        const seccionDocLic = document.getElementById('seccionDocLicencia');
        const esCondutor = tipo === 'chofer' || tipo === 'ambos';
        if (esCondutor) {
            seccionLic.style.removeProperty('display');
            seccionDocLic.classList.remove('d-none');
        } else {
            seccionLic.style.display = 'none';
            seccionDocLic.classList.add('d-none');
        }
    }

    // Validación en tiempo real del año
    document.addEventListener('DOMContentLoaded', function () {
        const anioInput = document.getElementById('cam_anio');
        const anioHint  = document.getElementById('anio_hint');
        const anioMin   = 1970;
        const anioMax   = {{ date('Y') }};

        anioInput.addEventListener('input', function () {
            // Solo permitir dígitos
            this.value = this.value.replace(/[^0-9]/g, '');

            const val = parseInt(this.value);

            if (this.value.length < 4) {
                anioHint.className = 'text-muted';
                anioHint.textContent = 'Entre ' + anioMin + ' y ' + anioMax;
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }

            if (val < anioMin) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                anioHint.className = 'text-danger';
                anioHint.textContent = '⚠ El año mínimo es ' + anioMin;
            } else if (val > anioMax) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                anioHint.className = 'text-danger';
                anioHint.textContent = '⚠ El año máximo es ' + anioMax;
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                anioHint.className = 'text-success';
                anioHint.textContent = '✓ Año válido';
            }
        });

        // Impedir tipear más de 4 caracteres
        anioInput.addEventListener('keypress', function (e) {
            if (this.value.length >= 4 || !/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Validación en tiempo real de capacidad en toneladas
        const capInput = document.getElementById('cam_capacidad');
        const capHint  = document.getElementById('capacidad_hint');
        const capMin   = 3.5;

        capInput.addEventListener('keydown', function (e) {
            const teclaControl = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'].includes(e.key);
            if (teclaControl) return;
            if (!/[0-9.]/.test(e.key)) { e.preventDefault(); return; }
            if (e.key === '.' && this.value.includes('.')) { e.preventDefault(); return; }
            const partes = (this.value + e.key).split('.');
            if (partes[1] !== undefined && partes[1].length >= 3 && e.key !== '.') { e.preventDefault(); return; }
        });

        capInput.addEventListener('input', function () {
            let val = this.value.replace(/[^0-9.]/g, '');
            const partes = val.split('.');
            if (partes.length > 2) val = partes[0] + '.' + partes.slice(1).join('');
            if (partes[1] !== undefined && partes[1].length > 3)
                val = partes[0] + '.' + partes[1].substring(0, 3);
            this.value = val;

            const num = parseFloat(val);
            if (val === '' || isNaN(num)) {
                this.classList.remove('is-invalid', 'is-valid');
                capHint.className   = 'text-muted';
                capHint.textContent = 'Mínimo 3.5 tn — máximo 3 decimales.';
                return;
            }
            if (num < capMin) {
                this.classList.add('is-invalid'); this.classList.remove('is-valid');
                capHint.className   = 'text-danger';
                capHint.textContent = '⚠ La capacidad mínima es 3.5 tn.';
            } else {
                this.classList.remove('is-invalid'); this.classList.add('is-valid');
                capHint.className   = 'text-success';
                capHint.textContent = '✓ Capacidad válida (' + num.toFixed(3) + ' tn)';
            }
        });
    });

    // ── Gestión de fotos nuevas (lista visual) ────────────────────────────────
    let _fotosNuevas = []; // DataTransfer acumulado de archivos nuevos
    let _fotosGuardadas = 0; // cuántas fotos ya existen en BD

    function _actualizarContador() {
        const total = _fotosNuevas.length + _fotosGuardadas;
        document.getElementById('fotosContadorLabel').innerText = total + ' / 5 fotos';
        const btn = document.getElementById('btnAgregarFoto');
        btn.disabled = total >= 5;
    }

    function agregarFotosNuevas(input) {
        const maxTotal = 5;
        const disponibles = maxTotal - _fotosNuevas.length - _fotosGuardadas;
        const lista = document.getElementById('fotosNuevasLista');

        Array.from(input.files).slice(0, disponibles).forEach(function(file) {
            const idx = _fotosNuevas.length;
            _fotosNuevas.push(file);

            const reader = new FileReader();
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.className = 'position-relative border rounded';
                card.style.cssText = 'width:90px;height:80px;overflow:hidden;';
                card.id = 'nueva-foto-' + idx;
                card.innerHTML = `
                    <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;" title="${file.name}">
                    <button type="button" onclick="quitarFotoNueva(${idx})"
                        class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 px-1"
                        style="font-size:10px;line-height:1.5;" title="Quitar">✕</button>
                    <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-50 text-white text-center"
                        style="font-size:9px;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        ${file.name}
                    </div>`;
                lista.appendChild(card);
                _actualizarContador();
                _sincronizarInputFotos();
            };
            reader.readAsDataURL(file);
        });

        input.value = ''; // limpiar para permitir seleccionar el mismo archivo
    }

    function quitarFotoNueva(idx) {
        _fotosNuevas.splice(idx, 1);
        // Rebuild lista visual
        const lista = document.getElementById('fotosNuevasLista');
        lista.innerHTML = '';
        const copia = [..._fotosNuevas];
        _fotosNuevas = [];
        _fotosGuardadas = parseInt(document.getElementById('fotosContadorLabel').innerText.split('/')[0]) - 0;
        // recalcular guardadas
        _fotosGuardadas = document.querySelectorAll('#galeriaFotosContenido .position-relative').length;
        copia.forEach(function(file, i) {
            _fotosNuevas.push(file);
            const reader = new FileReader();
            const capturedIdx = i;
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.className = 'position-relative border rounded';
                card.style.cssText = 'width:90px;height:80px;overflow:hidden;';
                card.id = 'nueva-foto-' + capturedIdx;
                card.innerHTML = `
                    <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;" title="${file.name}">
                    <button type="button" onclick="quitarFotoNueva(${capturedIdx})"
                        class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 px-1"
                        style="font-size:10px;line-height:1.5;" title="Quitar">✕</button>
                    <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-50 text-white text-center"
                        style="font-size:9px;padding:1px 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        ${file.name}
                    </div>`;
                lista.appendChild(card);
                _actualizarContador();
            };
            reader.readAsDataURL(file);
        });
        _sincronizarInputFotos();
    }

    function _sincronizarInputFotos() {
        // Asignar los archivos acumulados al input real usando DataTransfer
        const input = document.getElementById('cam_fotos_input');
        const dt = new DataTransfer();
        _fotosNuevas.forEach(f => dt.items.add(f));
        input.files = dt.files;
        // Cambiar el name para que se envíe como fotos[]
        input.name = 'fotos[]';
        input.multiple = true;
    }

    // Reset y abrir modal camión en modo Nuevo
    function resetModalCamion() {
        document.getElementById('tituloCamion').innerText = 'Nuevo Camión';
        var btnC = document.getElementById('btnCamion');
        btnC.innerText = 'Registrar';
        btnC.disabled  = false;
        document.getElementById('methodCamion').value     = 'POST';
        document.getElementById('formCamion').action      = '{{ route("camiones.store") }}';
        fetch('{{ route("camiones.nuevo-token") }}')
            .then(r => r.json())
            .then(d => {
                document.getElementById('idempotencyTokenCamion').value    = d.camion_token;
                document.getElementById('idempotencyTokenOperador').value  = d.operador_token;
                document.getElementById('idempotencyTokenConductor').value = d.conductor_token;
            });
        document.getElementById('formCamion').reset();
        document.getElementById('ruatActualInfo').classList.add('d-none');
        document.getElementById('galeriaFotos').classList.add('d-none');
        document.getElementById('galeriaFotosContenido').innerHTML = '';
        document.getElementById('fotosNuevasLista').innerHTML = '';
        _fotosNuevas = [];
        _fotosGuardadas = 0;
        _actualizarContador();
        // Limpiar el input de fotos
        const inputFotos = document.getElementById('cam_fotos_input');
        inputFotos.value = '';
        const dtVacio = new DataTransfer();
        inputFotos.files = dtVacio.files;
    }

    // Llenar modal camión en modo Editar
    function editarCamion(camion, fotos) {
        // Limpiar estado de fotos nuevas
        _fotosNuevas = [];
        document.getElementById('fotosNuevasLista').innerHTML = '';
        const inputFotos = document.getElementById('cam_fotos_input');
        inputFotos.value = '';
        const dtVacio = new DataTransfer();
        inputFotos.files = dtVacio.files;

        document.getElementById('tituloCamion').innerText = 'Editar Camión';
        document.getElementById('btnCamion').innerText    = 'Actualizar';
        document.getElementById('methodCamion').value     = 'PUT';
        document.getElementById('formCamion').action      = url_global + '/camion/' + camion.id;
        document.getElementById('cam_placa_pais_id').value   = camion.placa_pais_id ?? '';
        actualizarFormatoPlaca();
        document.getElementById('cam_placa').value        = camion.placa;
        document.getElementById('cam_tipo_vehiculo_id').value = camion.tipo_vehiculo_id ?? '';
        document.getElementById('cam_marca_id').value     = camion.marca_id ?? '';
        document.getElementById('cam_modelo').value       = camion.modelo;
        document.getElementById('cam_anio').value         = camion.anio;
        document.getElementById('cam_capacidad').value    = (camion.capacidad_kg / 1000).toFixed(3);
        document.getElementById('cam_color').value        = camion.color ?? '';
        document.getElementById('cam_estado').value       = camion.estado;
        document.getElementById('cam_propietario').value  = camion.propietario_id ?? '';
        document.getElementById('cam_ruat').value         = '';

        // Info RUAT
        const ruatInfo = document.getElementById('ruatActualInfo');
        camion.documento_ruat ? ruatInfo.classList.remove('d-none') : ruatInfo.classList.add('d-none');

        // Fotos guardadas en BD
        const galeria   = document.getElementById('galeriaFotos');
        const contenido = document.getElementById('galeriaFotosContenido');
        contenido.innerHTML = '';

        if (fotos && fotos.length > 0) {
            galeria.classList.remove('d-none');
            _fotosGuardadas = fotos.length;
            fotos.forEach(function(foto) {
                const wrapper = document.createElement('div');
                wrapper.className = 'position-relative border rounded';
                wrapper.style.cssText = 'width:90px;height:80px;overflow:hidden;';
                wrapper.id = 'foto-' + foto.id;
                wrapper.innerHTML = `
                    <img src="/storage/${foto.ruta}" style="width:100%;height:100%;object-fit:cover;" title="Foto guardada">
                    <button type="button" onclick="eliminarFoto(${foto.id})"
                        class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 px-1"
                        style="font-size:10px;line-height:1.5;" title="Eliminar foto">✕</button>
                    <div class="position-absolute bottom-0 start-0 end-0 bg-success bg-opacity-75 text-white text-center"
                        style="font-size:9px;padding:1px;">Guardada</div>`;
                contenido.appendChild(wrapper);
            });
        } else {
            galeria.classList.add('d-none');
            _fotosGuardadas = 0;
        }

        _actualizarContador();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCamion')).show();
    }

    function editarCamionPorUuid(uuid) {
        const url = '{{ route('camiones.edit', ['uuid' => ':uuid']) }}'.replace(':uuid', uuid);
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(data => editarCamion(data, data.fotos))
        .catch(err => alert('Error al cargar datos: ' + err.message));
    }

    function verFotos(fotos, placa) {
        document.getElementById('galeriaPlaca').innerText = placa;
        const viewer = document.getElementById('galeriaViewer');
        viewer.innerHTML = '';
        fotos.forEach(function(foto) {
            viewer.innerHTML += `
                <div class="col-6 col-md-4">
                    <a href="/storage/${foto.ruta}" target="_blank">
                        <img src="/storage/${foto.ruta}"
                            class="img-fluid rounded border"
                            style="width:100%;height:180px;object-fit:cover;"
                            title="Ver foto completa">
                    </a>
                </div>`;
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGaleria')).show();
    }

    function eliminarFoto(fotoId) {
        if (!confirm('¿Eliminar esta foto?')) return;
        fetch('{{ url("camion/foto") }}/' + fotoId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        }).then(r => r.json()).then(data => {
            if (data.ok) {
                const el = document.getElementById('foto-' + fotoId);
                if (el) el.remove();
                _fotosGuardadas = document.querySelectorAll('#galeriaFotosContenido .position-relative').length;
                if (_fotosGuardadas === 0) {
                    document.getElementById('galeriaFotos').classList.add('d-none');
                }
                _actualizarContador();
            }
        });
    }

    // Reset modal operador
    function resetModalOperador() {
        document.getElementById('tituloOperador').innerText = 'Nuevo Operador';
        var btnO = document.getElementById('btnOperador');
        btnO.innerText = 'Registrar';
        btnO.disabled  = false;
        document.getElementById('methodOperador').value = 'POST';
        document.getElementById('formOperador').action = '{{ route("operadores.store") }}';
        fetch('{{ route("camiones.nuevo-token") }}')
            .then(r => r.json())
            .then(d => {
                document.getElementById('idempotencyTokenCamion').value    = d.camion_token;
                document.getElementById('idempotencyTokenOperador').value  = d.operador_token;
                document.getElementById('idempotencyTokenConductor').value = d.conductor_token;
            });
        document.getElementById('formOperador').reset();
        document.getElementById('seccionLicencia').style.display = 'none';
        document.getElementById('seccionDocLicencia').classList.add('d-none');
        document.getElementById('op_carnet_info').classList.add('d-none');
        document.getElementById('op_licencia_info').classList.add('d-none');
        // Reset formato CI y selector de teléfono
        actualizarFormatoCI();
        document.getElementById('op_telefono_pais').value = 'Bolivia';
        document.getElementById('op_telefono_prefijo').value = '+591';
        document.getElementById('op_telefono_hint').textContent = 'Bolivia: 8 dígitos comenzando en 6 o 7';
        const telFb = document.getElementById('op_telefono_feedback');
        telFb.className = 'form-text d-none';
        document.getElementById('op_telefono').classList.remove('is-valid', 'is-invalid');
        const emailFb = document.getElementById('op_email_feedback');
        emailFb.className = 'form-text d-none';
        document.getElementById('op_email').classList.remove('is-valid', 'is-invalid');
    }

    function editarOperadorPorUuid(uuid) {
        // Usamos route() para que la URL sea correcta tanto en local como
        // en Hostinger (donde el sitio vive en una subcarpeta). El :uuid se
        // reemplaza por el valor real.
        const url = '{{ route('operadores.edit', ['uuid' => ':uuid']) }}'.replace(':uuid', uuid);
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(op => editarOperador(op))
        .catch(err => alert('Error al cargar datos: ' + err.message));
    }

    // Llenar modal operador en modo Editar
    function editarOperador(op) {
        document.getElementById('tituloOperador').innerText = 'Editar Operador';
        document.getElementById('btnOperador').innerText = 'Actualizar';
        document.getElementById('methodOperador').value = 'PUT';
        document.getElementById('formOperador').action = '{{ route('operadores.update', ['operador' => ':id']) }}'.replace(':id', op.id);
        document.getElementById('op_nombre').value            = op.nombre;
        document.getElementById('op_apellido_paterno').value  = op.apellido_paterno;
        document.getElementById('op_apellido_materno').value  = op.apellido_materno ?? '';
        document.getElementById('op_ci_pais_id').value   = op.ci_pais_id ?? '';
        actualizarFormatoCI();
        document.getElementById('op_ci').value        = op.ci;
        document.getElementById('op_email').value     = op.email ?? '';
        document.getElementById('op_telefono_pais').value = 'Bolivia';
        actualizarPrefijoTelefono();
        document.getElementById('op_telefono').value  = op.telefono ?? '';
        document.getElementById('op_direccion').value= op.direccion ?? '';
        document.getElementById('op_tipo').value     = op.tipo_operador;
        document.getElementById('op_estado').value   = op.estado;
        document.getElementById('op_licencia_num').value  = op.licencia_numero ?? '';
        document.getElementById('op_licencia_pais_id').value = op.licencia_pais_id ?? '';
        document.getElementById('op_licencia_venc').value = op.licencia_vencimiento ?? '';

        // Mostrar info documentos existentes
        op.doc_carnet
            ? document.getElementById('op_carnet_info').classList.remove('d-none')
            : document.getElementById('op_carnet_info').classList.add('d-none');
        op.doc_licencia
            ? document.getElementById('op_licencia_info').classList.remove('d-none')
            : document.getElementById('op_licencia_info').classList.add('d-none');

        toggleLicencia();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOperador')).show();
    }

    // ── Asignación: carga conductores relacionados al camión seleccionado ────
    function cargarConductoresCamion(select) {
        const option = select.options[select.selectedIndex];
        const uuid   = option ? option.dataset.uuid : null;
        const selectConductor = document.getElementById('asig_conductor_id');
        const hint            = document.getElementById('asig_conductor_hint');

        selectConductor.innerHTML = '<option value="">— Cargando... —</option>';
        selectConductor.disabled  = true;
        hint.classList.add('d-none');

        if (!uuid) {
            selectConductor.innerHTML = '<option value="">— Primero seleccione un camión —</option>';
            return;
        }

        fetch('{{ url("api/camion") }}/' + uuid + '/conductores-disponibles', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(conductores => {
            selectConductor.innerHTML = '';

            if (conductores.length === 0) {
                selectConductor.innerHTML = '<option value="">— Todos los conductores ya están asignados a este camión —</option>';
                selectConductor.disabled = true;
                hint.classList.add('d-none');
                return;
            }

            selectConductor.innerHTML = '<option value="">— Seleccione conductor —</option>';
            conductores.forEach(function(c) {
                const op = document.createElement('option');
                op.value       = c.id;
                op.textContent = c.nombre + ' — Lic: ' + (c.licencia || 'S/N');
                selectConductor.appendChild(op);
            });

            selectConductor.disabled = false;
            hint.classList.remove('d-none');
        })
        .catch(() => {
            selectConductor.innerHTML = '<option value="">— Error al cargar conductores —</option>';
            selectConductor.disabled = true;
        });
    }

    // Limpiar conductor al abrir el modal de asignación
    document.getElementById('modalAsignacion').addEventListener('show.bs.modal', function () {
        const sel = document.getElementById('asig_camion_id');
        sel.value = '';
        document.getElementById('asig_conductor_id').innerHTML = '<option value="">— Primero seleccione un camión —</option>';
        document.getElementById('asig_conductor_id').disabled = true;
        document.getElementById('asig_conductor_hint').classList.add('d-none');
    });

    // Bloqueo anti-doble-submit
    ['formCamion','formOperador','formConductor'].forEach(function(formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            var btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) { e.preventDefault(); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        });
    });
</script>
@endsection
