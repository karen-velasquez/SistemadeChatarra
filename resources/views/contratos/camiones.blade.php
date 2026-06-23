@extends('layouts.app')
@section('titulo', 'Camiones — ' . $contrato->numero_contrato)
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>CONTRATO {{ $contrato->numero_contrato }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('contratos.index') }}">Contratos</a></li>
                    <li class="breadcrumb-item active">Camiones y Tramos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"🚚 Esta es la pantalla de <b>Gestión de Camiones y Tramos</b> del contrato. Aquí asignas los camiones que transportan la chatarra y registras su recorrido. Te muestro cómo está organizada."},
                        {"element":"#detalle-contrato","intro":"📋 A la izquierda ves el <b>detalle del contrato</b>: proveedor, fechas, monto y un gráfico con las toneladas <b>entregadas</b> (verde), <b>en ruta</b> (celeste) y <b>pendientes</b>.","position":"right"},
                        {"element":"#camionesTab","intro":"🗂️ A la derecha hay dos pestañas: <b>Camiones y Tramos</b> (lo ya asignado) y <b>Asignar Camión</b> (para agregar uno nuevo).","position":"bottom"},
                        {"element":"#pane-lista","intro":"📦 Aquí se listan los camiones asignados. Cada tarjeta muestra la placa, su estado (En ruta / Entregado) y los <b>tramos</b> del recorrido.","position":"top"},
                        {"element":"#tab-asignar","intro":"➕ Para agregar un camión nuevo, entra a esta pestaña <b>Asignar Camión</b>. Ahí encontrarás otra guía ❓ que explica cada campo.","position":"bottom"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            <a href="{{ route('contratos.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<section class="section">
    <div class="row">

        {{-- ===== COLUMNA IZQUIERDA ===== --}}
        <div class="col-lg-4">
            <div class="card" id="detalle-contrato">
                <div class="card-body">
                    <h5 class="card-title">Detalle del Contrato</h5>
                    <table class="table table-sm table-borderless">
                        <tr><th>N° Contrato</th><td><span class="fw-bold text-primary">{{ $contrato->numero_contrato }}</span></td></tr>
                        <tr><th>Tipo</th><td>{{ $contrato->tipo_contrato }}</td></tr>
                        <tr><th>Proveedor</th><td>{{ $contrato->proveedor->nombre }}</td></tr>
                        <tr><th>Fecha Inicio</th><td>{{ $contrato->fecha_inicio?->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th>Fecha Fin</th><td>{{ $contrato->fecha_fin?->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th>Monto</th><td>{{ $contrato->moneda }} {{ number_format($contrato->monto_total, 2, ',', '.') }}</td></tr>
                        @if($contrato->costo_unitario)
                        <tr>
                            <th>Costo unitario</th>
                            <td>
                                {{ $contrato->moneda }} {{ number_format($contrato->costo_unitario, 2, ',', '.') }}<small class="text-muted">/t</small>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Estado</th>
                            <td>
                                @php $badgeMap = ['Activo'=>'bg-success','Concluido'=>'bg-dark']; @endphp
                                <span class="badge {{ $badgeMap[$contrato->estado] ?? 'bg-secondary' }}">{{ $contrato->estado }}</span>
                            </td>
                        </tr>
                    </table>

                    {{-- Gráfico toneladas --}}
                    @if($contrato->toneladas_contrato)
                        @php
                            $total      = (float) $contrato->toneladas_contrato;
                            $entregadas = $contrato->toneladas_entregadas;
                            $enTransito = $contrato->toneladas_en_transito;
                            $pctEnt     = min(100, round(($entregadas / $total) * 100, 1));
                            $pctTra     = min(100 - $pctEnt, round(($enTransito / $total) * 100, 1));
                            $pendiente  = max(0, $total - $entregadas - $enTransito);
                        @endphp
                        <hr>
                        <h6 class="fw-bold">Toneladas del Contrato</h6>
                        <div class="progress mb-1" style="height:22px;" title="{{ $pctEnt }}% entregado al cliente · {{ $pctTra }}% en ruta · {{ number_format($pendiente, 2, ',', '.') }} t pendiente">
                            @if($pctEnt > 0)
                            <div class="progress-bar bg-success progress-bar-striped" style="width:{{ $pctEnt }}%">
                                @if($pctEnt >= 10){{ $pctEnt }}%@endif
                            </div>
                            @endif
                            @if($pctTra > 0)
                            <div class="progress-bar progress-bar-striped" style="width:{{ $pctTra }}%; background:#38bdf8;">
                                @if($pctTra >= 10){{ $pctTra }}%@endif
                            </div>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <small>
                                @if($entregadas > 0)
                                    <span class="text-success fw-semibold"><i class="bi bi-check-circle"></i> {{ number_format($entregadas, 2, ',', '.') }} t cliente</span>
                                    &nbsp;·&nbsp;
                                @endif
                                <span style="color:#0ea5e9;"><i class="bi bi-truck"></i> {{ number_format($enTransito, 2, ',', '.') }} t en ruta</span>
                                @if($pendiente > 0)
                                    &nbsp;·&nbsp;
                                    <span class="text-muted"><i class="bi bi-hourglass"></i> {{ number_format($pendiente, 2, ',', '.') }} t pend.</span>
                                @endif
                            </small>
                            <small class="text-muted">Total: <strong>{{ number_format($total, 2, ',', '.') }} t</strong></small>
                        </div>
                        @if($pctEnt >= 100)
                            <span class="badge bg-success w-100 text-center py-2 mt-2">✓ Contrato completado</span>
                        @endif
                    @else
                        <div class="alert alert-warning py-2 mt-2">
                            <small><i class="bi bi-exclamation-triangle"></i> Sin toneladas definidas en el contrato.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== COLUMNA DERECHA ===== --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    @if($contrato->envios_cerrados)
                    <div class="alert alert-warning py-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-lock-fill fs-5"></i>
                        <span><strong>Envíos cerrados.</strong> Este contrato está en modo solo lectura. No se pueden agregar ni modificar camiones o tramos.</span>
                    </div>
                    @endif
                    <ul class="nav nav-tabs" id="camionesTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-lista" type="button">
                                <i class="bi bi-diagram-3"></i> Camiones y Tramos
                                <span class="badge bg-primary ms-1">{{ $contrato->contratoCamiones->count() }}</span>
                            </button>
                        </li>
                        @can('contratos.edit')
                        @if(!$contrato->envios_cerrados)
                        <li class="nav-item">
                            <button id="tab-asignar" class="nav-link @if($errors->any()) active @endif" data-bs-toggle="tab" data-bs-target="#pane-agregar" type="button">
                                <i class="bi bi-plus-circle"></i> Asignar Camión
                            </button>
                        </li>
                        @endif
                        @endcan
                    </ul>

                    <div class="tab-content pt-3">

                        {{-- ===== LISTA ===== --}}
                        <div class="tab-pane fade @if(!$errors->any()) show active @endif" id="pane-lista">
                            @if($contrato->contratoCamiones->isEmpty())
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No hay camiones asignados a este contrato aún.
                                </div>
                            @else
                                @foreach($contrato->contratoCamiones as $cc)
                                @php
                                    $tramosRaiz = $cc->tramos->whereNull('tramo_padre_id');
                                    $estadoEntrega = $cc->estado_entrega_calculado;
                                    $ccBorde  = match($estadoEntrega) { 'Entregado' => 'border-success', 'Desactivado' => 'border-secondary', default => 'border-warning' };
                                    $ccFondo  = match($estadoEntrega) { 'Entregado' => 'bg-success bg-opacity-10', 'Desactivado' => 'bg-secondary bg-opacity-10', default => 'bg-warning bg-opacity-10' };
                                    $ccIcono  = match($estadoEntrega) { 'Entregado' => 'text-success', 'Desactivado' => 'text-secondary', default => 'text-warning' };
                                    $ccBadge  = match($estadoEntrega) { 'Entregado' => 'bg-success', 'Desactivado' => 'bg-secondary', default => 'bg-warning text-dark' };
                                @endphp
                                <div class="card border mb-3 {{ $ccBorde }}">
                                    <div class="card-header py-2 {{ $ccFondo }}">
                                        {{-- Línea 1: identificación + estado + acciones --}}
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-truck {{ $ccIcono }}"></i>
                                                <strong>{{ $cc->camion->placa }}</strong>
                                                <span class="text-muted ms-1">{{ $cc->camion->marca->valor ?? '-' }} {{ $cc->camion->modelo }}</span>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                <span class="badge {{ $ccBadge }}">
                                                    {{ $estadoEntrega }}
                                                </span>
                                                @can('contratos.edit')
                                                    @if(!$contrato->envios_cerrados && $estadoEntrega !== 'Entregado')
                                                        <button class="btn btn-sm {{ $cc->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                            onclick="confirmarToggleCC('{{ $cc->uuid }}', '{{ $cc->camion->placa }}', {{ $cc->activo ? 'true' : 'false' }})"
                                                            title="{{ $cc->activo ? 'Desactivar asignación' : 'Reactivar asignación' }}">
                                                            <i class="bi {{ $cc->activo ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' }}"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </div>
                                        {{-- Línea 2: toneladas --}}
                                        <div class="mt-1 d-flex gap-3 flex-wrap">
                                            <small class="text-muted">
                                                <i class="bi bi-tag"></i> Proveedor:
                                                <strong>{{ number_format($cc->toneladas, 2, ',', '.') }} t</strong>
                                            </small>
                                            @if($estadoEntrega === 'Entregado')
                                                <small class="text-success fw-semibold">
                                                    <i class="bi bi-check-circle"></i> Entregado al cliente:
                                                    <strong>{{ number_format($cc->peso_entregado, 2, ',', '.') }} t</strong>
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body py-2">
                                        @if($tramosRaiz->isEmpty())
                                            <small class="text-muted"><i class="bi bi-info-circle"></i> Sin tramos registrados.</small>
                                        @else
                                            @foreach($tramosRaiz as $tramo)
                                                @include('contratos.partials.tramo', ['tramo' => $tramo, 'nivel' => 0, 'camionesDisponibles' => $camionesDisponibles, 'enviosCerrados' => $contrato->envios_cerrados])
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- ===== FORMULARIO ASIGNAR CAMIÓN ===== --}}
                        @can('contratos.edit')
                        <div class="tab-pane fade @if($errors->any()) show active @endif" id="pane-agregar">
                            @if($errors->any())
                                <div class="alert alert-danger py-2">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-start">
                                <p class="text-muted small"><i class="bi bi-info-circle"></i> Al asignar el camión se registra automáticamente el primer tramo de transporte.</p>
                                <button type="button"
                                        class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                                        data-steps='[
                                            {"intro":"📝 Vamos a asignar un <b>camión</b> a este contrato. Al guardar, se crea automáticamente su primer <b>tramo</b> de transporte. Te explico cada campo."},
                                            {"element":"#cc_camion_id","intro":"🚛 <b>Camión</b>: búscalo por placa, marca o modelo. Solo aparecen los camiones <b>disponibles</b>, con su capacidad en toneladas.","position":"bottom"},
                                            {"element":"#cc_conductor_id","intro":"👷 <b>Conductor</b>: se habilita al elegir el camión y muestra solo los conductores <b>relacionados</b> con esa unidad.","position":"bottom"},
                                            {"element":"[name=\"origen\"]","intro":"📍 <b>Origen</b>: desde dónde sale la carga (ej: SÃO PAULO, BRASIL). Se escribe en mayúsculas automáticamente.","position":"top"},
                                            {"element":"[name=\"destino\"]","intro":"🏁 <b>Destino</b>: a dónde va este tramo (ej: FRONTERA CORUMBÁ / LA PAZ).","position":"top"},
                                            {"element":"[name=\"peso_declarado\"]","intro":"⚖️ <b>Peso declarado</b>: las toneladas que el proveedor dice que entrega en este camión.","position":"top"},
                                            {"element":"[name=\"fecha_asignacion\"]","intro":"📅 <b>Fecha de Salida</b> del camión. Por defecto toma la fecha de hoy.","position":"top"},
                                            {"element":"[name=\"monto_acordado\"]","intro":"💲 <b>Monto del flete</b> pactado con el transportista, con su moneda. Es opcional.","position":"top"},
                                            {"element":"#btn_asignar_camion","intro":"✅ El botón <b>Asignar Camión</b> se activa cuando completas los campos obligatorios. ¡Y listo!","position":"top"}
                                        ]'>
                                    <i class="bi bi-question-circle"></i>
                                </button>
                            </div>
                            <form id="formContratoCamion" method="POST" action="{{ route('contrato-camion.store') }}">
                                @csrf
                                <input type="hidden" name="_idempotency_token" id="idempotencyTokenContratoCamion" value="{{ $tokenContratoCamion ?? '' }}">
                                <input type="hidden" name="contrato_id" value="{{ $contrato->id }}">
                                <div class="row g-3">

                                    {{-- Camión --}}
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">Camión <span class="text-danger">(*)</span></label>
                                        <select class="form-select @error('camion_id') is-invalid @enderror"
                                            name="camion_id" id="cc_camion_id" required>
                                            <option value="">-- Busque por placa o marca --</option>
                                            @foreach($camionesDisponibles as $cam)
                                                <option value="{{ $cam->id }}" data-uuid="{{ $cam->uuid }}" data-capacidad="{{ $cam->capacidad_kg }}">
                                                    {{ $cam->placa }} — {{ $cam->marca->valor ?? '-' }} {{ $cam->modelo }} ({{ number_format($cam->capacidad_kg / 1000, 2, ',', '.') }} t cap.)
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('camion_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Conductor --}}
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Conductor <span class="text-danger">(*)</span></label>
                                        <select class="form-select @error('conductor_id') is-invalid @enderror"
                                            name="conductor_id" id="cc_conductor_id" disabled required>
                                            <option value="">— Primero seleccione un camión —</option>
                                        </select>
                                        <div id="cc_sin_conductores" class="alert alert-warning py-2 mt-1 d-none">
                                            <i class="bi bi-exclamation-triangle"></i> No hay conductores para este camión.
                                        </div>
                                        @error('conductor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Tipo de tramo --}}
                                    @if($contrato->tipo_contrato === 'Internacional')
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tipo de Tramo <span class="text-danger">(*)</span></label>
                                        <select class="form-select @error('tipo_tramo') is-invalid @enderror" name="tipo_tramo" required>
                                            <option value="Nacional" {{ old('tipo_tramo') == 'Nacional' ? 'selected' : '' }}>Nacional</option>
                                            <option value="Internacional" {{ old('tipo_tramo') == 'Internacional' ? 'selected' : '' }}>Internacional</option>
                                        </select>
                                        @error('tipo_tramo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    @else
                                        <input type="hidden" name="tipo_tramo" value="Nacional">
                                    @endif

                                    {{-- Origen y Destino --}}
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Origen <span class="text-danger">(*)</span></label>
                                        <input type="text" class="form-control @error('origen') is-invalid @enderror"
                                            name="origen" value="{{ old('origen') }}" required maxlength="150" placeholder="Ej: SÃO PAULO, BRASIL"
                                            style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                                        @error('origen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Destino <span class="text-danger">(*)</span></label>
                                        <input type="text" class="form-control @error('destino') is-invalid @enderror"
                                            name="destino" value="{{ old('destino') }}" required maxlength="150" placeholder="Ej: FRONTERA CORUMBÁ / LA PAZ"
                                            style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                                        @error('destino')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Peso declarado y fecha --}}
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Peso declarado por el proveedor (t) <span class="text-danger">(*)</span></label>
                                        <input type="text" inputmode="numeric"
                                            class="form-control @error('peso_declarado') is-invalid @enderror"
                                            id="peso_declarado_display" placeholder="0,00" autocomplete="off" required>
                                        <input type="hidden" name="peso_declarado" id="peso_declarado"
                                            value="{{ old('peso_declarado') }}">
                                        <small class="text-muted">Lo que el proveedor dice que entrega.</small>
                                        @error('peso_declarado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Fecha de Salida <span class="text-danger">(*)</span></label>
                                        <input type="date" class="form-control @error('fecha_asignacion') is-invalid @enderror"
                                            name="fecha_asignacion" value="{{ old('fecha_asignacion', date('Y-m-d')) }}" required>
                                        @error('fecha_asignacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Monto Acordado (flete)</label>
                                        <div class="input-group">
                                            <select class="form-select flex-grow-0" style="width:90px;"
                                                name="moneda_flete" id="moneda_flete">
                                                @foreach($monedas as $moneda)
                                                    <option value="{{ $moneda->valor }}" {{ $moneda->valor === 'BOB' ? 'selected' : '' }}>{{ $moneda->valor }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" inputmode="numeric"
                                                class="form-control @error('monto_acordado') is-invalid @enderror"
                                                id="monto_acordado_display" placeholder="0,00" autocomplete="off">
                                            <input type="hidden" name="monto_acordado" id="monto_acordado"
                                                value="{{ old('monto_acordado') }}">
                                        </div>
                                        <small class="text-muted">Flete pactado con el transportista.</small>
                                        @error('monto_acordado')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Observaciones --}}
                                    <div class="col-12">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="2" maxlength="500"
                                            placeholder="Notas adicionales...">{{ old('observaciones') }}</textarea>
                                    </div>

                                    <div class="col-12 text-end">
                                        <button type="submit" id="btn_asignar_camion" class="btn btn-primary" disabled>
                                            <i class="bi bi-plus-lg"></i> Asignar Camión
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endcan

                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ===== MODAL REGISTRAR LLEGADA ===== --}}
<div class="modal fade" id="modalLlegada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-geo-alt"></i> Registrar Llegada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formLlegada" action="" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="origen" value="camiones">
                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                    @if($errors->llegada->any())
                    <div class="alert alert-danger py-2 mb-3" id="errores_llegada_backend">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->llegada->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="alert alert-light border mb-3 py-2">
                        <small class="text-muted">Tramo:</small><br>
                        <strong id="llegada_tramo_info"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" id="lbl_peso_llegada">Peso al llegar (t) <span class="text-danger">(*)</span></label>
                            <input type="text" inputmode="numeric" class="form-control"
                                id="inp_peso_llegada_display" required placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="peso_llegada" id="inp_peso_llegada">
                            <small class="text-muted">Carga estipulada en el origen: <strong id="llegada_peso_max"></strong> t</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de llegada <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_llegada" id="inp_fecha_llegada" required>
                            <small class="text-muted">No puede ser anterior a la fecha de salida.</small>
                        </div>

                        {{-- ¿Qué ocurrió al llegar? --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">¿Qué ocurrió al llegar? <span class="text-danger">(*)</span></label>
                            <div id="aviso_peso_requerido" class="text-muted small mb-2">
                                <i class="bi bi-lock text-warning"></i> Ingresa primero el peso al llegar para habilitar estas opciones.
                            </div>
                            <div class="d-flex flex-column gap-2 mt-1">

                                {{-- Opción: Entregado al cliente --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="entregado" id="accion_entregado" required disabled
                                            onchange="accionCamionCambiada('entregado')">
                                        <label class="form-check-label" for="accion_entregado">
                                            <i class="bi bi-check-circle text-success"></i>
                                            <strong>Entregado al cliente</strong>
                                            <small class="d-block text-muted">La carga llegó a su destino final.</small>
                                        </label>
                                    </div>
                                    {{-- Cliente receptor --}}
                                    <div class="d-none mt-3" id="sec_cliente">
                                        <label class="form-label fw-semibold">Cliente que recibe la carga <span class="text-danger">(*)</span></label>
                                        <select class="form-select" name="cliente_id" id="sel_cliente">
                                            <option value="">-- Seleccione cliente y dirección --</option>
                                            @foreach($clientes as $cli)
                                                @if($cli->contacts->isEmpty())
                                                    <option value="{{ $cli->id }}" data-direccion="">
                                                        {{ $cli->nombre }} — Sin dirección registrada
                                                    </option>
                                                @else
                                                    @foreach($cli->contacts as $dir)
                                                        <option value="{{ $cli->id }}" data-direccion="{{ $dir->valor }}">
                                                            {{ $cli->nombre }} — {{ $dir->valor }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="direccion_entrega" id="inp_direccion_entrega">
                                    </div>
                                    {{-- Empresa que facturará --}}
                                    <div class="d-none mt-3" id="sec_empresa_factura">
                                        <label class="form-label fw-semibold">Empresa que facturará <span class="text-danger">(*)</span></label>
                                        <select class="form-select" name="empresa_facturadora_id" id="sel_empresa_factura">
                                            <option value="">-- Seleccione empresa --</option>
                                            @foreach($empresas as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- Precio de venta al cliente --}}
                                    <div class="d-none mt-3" id="sec_precio_venta">
                                        <div class="border rounded-3 p-3 bg-light">
                                            <div class="fw-semibold mb-2"><i class="bi bi-tag text-success"></i> Precio de venta al cliente</div>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Moneda</label>
                                                    <select class="form-select form-select-sm" name="moneda_venta" id="sel_moneda_venta">
                                                        <option value="BOB">BOB</option>
                                                        <option value="USD">USD</option>
                                                        <option value="BRL">BRL</option>
                                                        <option value="ARS">ARS</option>
                                                        <option value="EUR">EUR</option>
                                                        <option value="PEN">PEN</option>
                                                        <option value="CLP">CLP</option>
                                                        <option value="PYG">PYG</option>
                                                        <option value="COP">COP</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Precio por tonelada</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" inputmode="numeric" class="form-control"
                                                            id="inp_precio_ton_display"
                                                            placeholder="0,00" autocomplete="off">
                                                        <input type="hidden" name="precio_por_tonelada" id="inp_precio_ton">
                                                        <span class="input-group-text">/t</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label mb-1">Total estimado</label>
                                                    <div id="lbl_total_venta" class="form-control form-control-sm bg-white text-success fw-semibold">—</div>
                                                </div>
                                            </div>
                                            <small class="text-muted mt-1 d-block" id="precio_venta_base_msg">Basado en el peso de llegada ingresado arriba.</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Opción: Div. Carga --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="div_carga" id="accion_div_carga" required disabled
                                            onchange="accionCamionCambiada('div_carga')">
                                        <label class="form-check-label" for="accion_div_carga">
                                            <i class="bi bi-pie-chart text-info"></i>
                                            <strong>Div. Carga</strong>
                                            <small class="d-block text-muted">Entrega parte al cliente 1 y el restante continúa en otro camión al cliente 2. Se generan 2 tramos automáticamente.</small>
                                        </label>
                                    </div>
                                    {{-- Sección entrega parcial --}}
                                    <div class="d-none mt-3" id="sec_parcial_cam">
                                        <div class="border rounded-3 p-3 bg-light">
                                            <div class="alert alert-info py-2 mb-3">
                                                <small><i class="bi bi-info-circle"></i> El campo <strong>"Peso al llegar"</strong> arriba indica el total que llegó. Ingresa abajo cuántas toneladas se entregan ahora a este cliente — el resto continuará en un nuevo tramo.</small>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Cliente que recibe esta parte <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="cliente_id" id="sel_cliente_div">
                                                        <option value="">-- Seleccione cliente y dirección --</option>
                                                        @foreach($clientes as $cli)
                                                            @if($cli->contacts->isEmpty())
                                                                <option value="{{ $cli->id }}" data-direccion="">
                                                                    {{ $cli->nombre }} — Sin dirección registrada
                                                                </option>
                                                            @else
                                                                @foreach($cli->contacts as $dir)
                                                                    <option value="{{ $cli->id }}" data-direccion="{{ $dir->valor }}">
                                                                        {{ $cli->nombre }} — {{ $dir->valor }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="direccion_entrega" id="inp_direccion_entrega_div">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Empresa que facturará <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="empresa_facturadora_id" id="sel_empresa_factura_div">
                                                        <option value="">-- Seleccione empresa --</option>
                                                        @foreach($empresas as $emp)
                                                            <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">TN entregadas a este cliente <span class="text-danger">*</span></label>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        id="cam_inp_tn_parcial_display"
                                                        placeholder="0,00" autocomplete="off">
                                                    <input type="hidden" name="tn_parcial" id="cam_inp_tn_parcial">
                                                    <small class="text-muted">TN para el nuevo tramo: <strong id="cam_lbl_tn_restante">—</strong></small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Destino del nuevo tramo <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="destino_nuevo_tramo"
                                                        maxlength="150" placeholder="Ciudad / punto de entrega">
                                                </div>
                                                <div class="col-12">
                                                    <div class="border rounded-3 p-3 bg-white">
                                                        <div class="fw-semibold mb-2"><i class="bi bi-tag text-success"></i> Precio de venta al cliente (esta entrega)</div>
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-md-4">
                                                                <label class="form-label mb-1">Moneda</label>
                                                                <select class="form-select form-select-sm" name="moneda_venta" id="sel_moneda_venta_div">
                                                                    <option value="BOB">BOB</option>
                                                                    <option value="USD">USD</option>
                                                                    <option value="BRL">BRL</option>
                                                                    <option value="ARS">ARS</option>
                                                                    <option value="EUR">EUR</option>
                                                                    <option value="PEN">PEN</option>
                                                                    <option value="CLP">CLP</option>
                                                                    <option value="PYG">PYG</option>
                                                                    <option value="COP">COP</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label mb-1">Precio por tonelada</label>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" inputmode="numeric" class="form-control"
                                                                        id="inp_precio_ton_div_display"
                                                                        placeholder="0,00" autocomplete="off">
                                                                    <input type="hidden" name="precio_por_tonelada" id="inp_precio_ton_div">
                                                                    <span class="input-group-text">/t</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label mb-1">Total estimado</label>
                                                                <div id="lbl_total_venta_div" class="form-control form-control-sm bg-white text-success fw-semibold">—</div>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted mt-1 d-block">Basado en las TN entregadas a este cliente.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="camion_nuevo_id" id="cam_hidden_camion">
                                            <input type="hidden" name="conductor_nuevo_id" id="cam_hidden_conductor">
                                            <input type="hidden" name="fecha_salida_nuevo_tramo" id="cam_hidden_fecha">
                                            <input type="hidden" name="tipo_tramo_nuevo" id="cam_hidden_tipo_tramo">
                                        </div>
                                    </div>
                                </div>

                                {{-- Opción: Transbordando --}}
                                <div class="border rounded p-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="accion" value="transbordo" id="accion_transbordo" required disabled
                                            onchange="accionCamionCambiada('transbordo')">
                                        <label class="form-check-label" for="accion_transbordo">
                                            <i class="bi bi-arrow-left-right text-warning"></i>
                                            <strong>Transbordando a otro(s) camión(es)</strong>
                                            <small class="d-block text-muted">La carga continúa en otros camiones (frontera o cambio de unidad).</small>
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Descuento --}}
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="chk_descuento"
                                    onchange="document.getElementById('sec_descuento').classList.toggle('d-none', !this.checked); if(!this.checked) document.getElementById('inp_descuento').value='';">
                                <label class="form-check-label fw-semibold" for="chk_descuento">
                                    <i class="bi bi-percent text-danger"></i> Aplicar descuento al pago del camionero
                                </label>
                            </div>
                            <div id="sec_descuento" class="d-none">
                                <label class="form-label">Porcentaje de descuento (%)</label>
                                <input type="number" step="0.01" min="0" max="60" class="form-control"
                                    name="descuento_porcentaje" id="inp_descuento" placeholder="Ej: 10.00"
                                    oninput="if(parseFloat(this.value)>60) this.value=60;">
                                <small class="text-muted">Máximo 60%. Por chatarra en mal estado, faltante u otro motivo.</small>
                            </div>
                        </div>

                        {{-- Lote de entrega semanal --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-collection text-primary"></i>
                                Lote de entrega semanal
                                <span class="text-danger">(*)</span>
                            </label>
                            <select class="form-select" name="lote_entrega_id" id="sel_lote_entrega" required>
                                <option value="">Cargando lotes...</option>
                            </select>
                            <small class="text-muted">Se asigna automáticamente al lote de esta semana. Puedes cambiar a una semana anterior si el lote aún está abierto.</small>
                        </div>

                        {{-- Documento de entrega --}}
                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-arrow-up text-success"></i>
                                Documento de entrega
                                <span class="text-muted small">(recomendado)</span>
                            </label>
                            <input type="file" class="form-control" name="documento_entrega"
                                id="inp_documento_entrega"
                                accept=".pdf,.png,.jpg,.jpeg">
                            <small class="text-muted">PDF, PNG o JPG. Máx. 20 MB. No obligatorio pero recomendable.</small>
                        </div>

                        {{-- Observaciones de llegada --}}
                        <div class="col-12">
                            <label class="form-label">Observaciones de llegada</label>
                            <textarea class="form-control" name="observaciones_llegada" rows="2" maxlength="500"
                                placeholder="Notas sobre el estado de la carga, incidentes, etc."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2">
                    <div class="text-muted text-center" style="font-size:12px;">
                        <i class="bi bi-lock"></i> El botón se activará cuando todos los campos obligatorios (<span class="text-danger fw-bold">*</span>) estén completos.
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn_confirmar_llegada" class="btn btn-secondary" disabled>
                            <i class="bi bi-check-lg"></i> Confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL TRANSBORDO ===== --}}
<div class="modal fade" id="modalTransbordo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title"><i class="bi bi-arrow-down-right"></i> Registrar Transbordo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTramoTransbordo" method="POST" action="{{ route('tramo.store') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenTransbordo" value="{{ $tokenTramoTransbordo ?? '' }}">
                <input type="hidden" name="contrato_camion_id" id="tsb_cc_id">
                <input type="hidden" name="tramo_padre_id"     id="tsb_padre_id">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        Transbordo desde: <strong id="tsb_info_padre"></strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Camión <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="camion_id" id="tsb_camion_id" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($camionesDisponibles as $cam)
                                    <option value="{{ $cam->id }}" data-uuid="{{ $cam->uuid }}">
                                        {{ $cam->placa }} — {{ $cam->marca->valor ?? '-' }} {{ $cam->modelo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conductor <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="conductor_id" id="tsb_conductor_id" disabled required>
                                <option value="">— Seleccione un camión primero —</option>
                            </select>
                            <div id="tsb_sin_conductor_aviso" class="alert alert-warning py-1 px-2 mt-1 mb-0 d-none" style="font-size:13px;">
                                <i class="bi bi-exclamation-triangle"></i> Este camión no tiene conductores asignados. Asigne un conductor antes de registrar el transbordo.
                            </div>
                        </div>
                        @if($contrato->tipo_contrato === 'Internacional')
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Tramo <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_tramo" required>
                                <option value="Nacional" selected>Nacional</option>
                                <option value="Internacional">Internacional</option>
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="tipo_tramo" value="Nacional">
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Destino <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="destino" required maxlength="150" placeholder="Ej: LA PAZ"
                                style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Peso que lleva este camión (t) <span class="text-danger">(*)</span></label>
                            <input type="number" step="0.001" min="0.001" class="form-control"
                                name="peso_salida" id="tsb_peso_salida" required placeholder="Toneladas que carga este camión">
                            <small class="text-muted">Disponible para transbordo: <strong id="tsb_peso_disponible"></strong> t</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Salida <span class="text-danger">(*)</span></label>
                            <input type="date" class="form-control" name="fecha_salida" id="tsb_fecha_salida" required>
                            <small class="text-muted">No puede ser anterior a la llegada del camión anterior.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2">
                    <div class="text-muted text-center" style="font-size:12px;">
                        <i class="bi bi-lock"></i> El botón se activará cuando todos los campos obligatorios (<span class="text-danger fw-bold">*</span>) estén completos.
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn_registrar_transbordo" class="btn btn-secondary" disabled>
                            <i class="bi bi-save"></i> Registrar Transbordo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL TOGGLE ACTIVO CAMIÓN ===== --}}
<div class="modal fade" id="modalToggleCC" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalToggleCC_header">
                <h5 class="modal-title" id="modalToggleCC_titulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalToggleCC_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="modalToggleCC_btn" href="#" class="btn">Confirmar</a>
            </div>
        </div>
    </div>
</div>

{{-- ===== MODAL TOGGLE TRAMO ===== --}}
<div class="modal fade" id="modalToggleTramo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modalToggleTramo_header">
                <h5 class="modal-title" id="modalToggleTramo_titulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalToggleTramo_body"></p>
                <div class="border rounded p-2 bg-light">
                    <small class="text-muted">Camión:</small> <strong id="modalToggleTramo_placa"></strong><br>
                    <small class="text-muted">Ruta:</small> <span id="modalToggleTramo_ruta"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="modalToggleTramo_btn" href="#" class="btn">Confirmar</a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>

document.addEventListener('DOMContentLoaded', function () {
    // Select2 camión principal — sin dropdownParent para evitar problemas dentro del tab
    $('#cc_camion_id').select2({
        placeholder: 'Busque por placa, marca o modelo...',
        allowClear: true,
        width: '100%',
        language: { noResults: () => 'No se encontró ningún camión.', searching: () => 'Buscando...' }
    });
    $('#cc_camion_id').on('change', function () {
        const opt  = $('#cc_camion_id option:selected');
        const uuid = opt.data('uuid') || '';
        // Si se limpia el camión, destruir Select2 del conductor y resetear
        if (!uuid) {
            const condSel = document.getElementById('cc_conductor_id');
            if ($.fn.select2 && $(condSel).data('select2')) $(condSel).select2('destroy');
            condSel.innerHTML = '<option value="">— Primero seleccione un camión —</option>';
            condSel.disabled  = true;
            document.getElementById('cc_sin_conductores').classList.add('d-none');
            return;
        }
        cargarConductores(uuid, 'cc_conductor_id', 'cc_sin_conductores', null);
    });

    // Select2 camión transbordo
    $('#tsb_camion_id').select2({
        placeholder: 'Busque por placa...',
        width: '100%',
        dropdownParent: $('#modalTransbordo'),
        language: { noResults: () => 'No se encontró ningún camión.', searching: () => 'Buscando...' }
    });
    $('#tsb_camion_id').on('change', function () {
        const opt  = $('#tsb_camion_id option:selected');
        const uuid = opt.data('uuid');
        cargarConductores(uuid, 'tsb_conductor_id', null, '#modalTransbordo');
        validarFormTransbordo();
    });

    document.getElementById('modalTransbordo').addEventListener('input', validarFormTransbordo);
    document.getElementById('tsb_conductor_id').addEventListener('change', validarFormTransbordo);

    document.getElementById('modalLlegada').addEventListener('input', validarFormLlegada);
    document.getElementById('modalLlegada').addEventListener('change', validarFormLlegada);
    document.getElementById('formLlegada').addEventListener('submit', submitLlegada);

    // Re-inicializar Select2 cuando se abre el tab de agregar
    $('button[data-bs-target="#pane-agregar"]').on('shown.bs.tab', function () {
        $('#cc_camion_id').select2({
            placeholder: 'Busque por placa, marca o modelo...',
            allowClear: true,
            width: '100%',
            language: { noResults: () => 'No se encontró ningún camión.', searching: () => 'Buscando...' }
        });
    });
});

function cargarConductores(uuid, selectId, sinConductoresId, dropdownParent) {
    const sel = document.getElementById(selectId);
    const sin = sinConductoresId ? document.getElementById(sinConductoresId) : null;

    // Destruir instancia Select2 previa si existe
    if ($.fn.select2 && $(sel).data('select2')) {
        $(sel).select2('destroy');
    }

    sel.innerHTML = '<option value="">— Cargando... —</option>';
    sel.disabled  = true;
    if (sin) sin.classList.add('d-none');

    if (!uuid) {
        sel.innerHTML = '<option value="">— Primero seleccione un camión —</option>';
        return;
    }

    fetch('{{ url("api/camion") }}/' + uuid + '/conductores-relacionados', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(conductores => {
        const btnSubmit = document.getElementById('btn_registrar_transbordo');
        const aviso     = document.getElementById('tsb_sin_conductor_aviso');
        if (conductores.length === 0) {
            sel.innerHTML = '<option value="">— Sin conductores —</option>';
            if (sin) sin.classList.remove('d-none');
            if (aviso) aviso.classList.remove('d-none');
            if (btnSubmit) btnSubmit.disabled = true;
            return;
        }
        if (aviso) aviso.classList.add('d-none');
        if (btnSubmit) btnSubmit.disabled = false;
        sel.innerHTML = '<option value="">— Seleccione conductor —</option>';
        conductores.forEach(c => {
            const op = document.createElement('option');
            op.value       = c.id;
            op.textContent = c.nombre + ' — Lic: ' + (c.licencia || 'S/N') + ' [' + c.tipo + ']';
            sel.appendChild(op);
        });
        sel.disabled = false;

        // Inicializar Select2 con buscador
        const s2opts = {
            placeholder: 'Busque por nombre...',
            allowClear: true,
            width: '100%',
            language: { noResults: () => 'No se encontró ningún conductor.', searching: () => 'Buscando...' }
        };
        if (dropdownParent) s2opts.dropdownParent = $(dropdownParent);
        $(sel).select2(s2opts);

        // Propagar cambio a validaciones
        $(sel).on('change', function () {
            if (selectId === 'cc_conductor_id') {
                validarFormAsignar();
            } else {
                validarFormTransbordo();
            }
        });
    })
    .catch(() => { sel.innerHTML = '<option value="">— Error al cargar —</option>'; });
}

function abrirModalLlegada(tramoUuid, info, pesoSalida, fechaSalida, camionId, conductorId, tipoTramo) {
    document.getElementById('llegada_tramo_info').textContent = info;
    document.getElementById('formLlegada').action            = '{{ url("tramo") }}/' + tramoUuid + '/llegada';
    document.getElementById('llegada_peso_max').textContent  = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(pesoSalida);
    document.querySelectorAll('input[name="accion"]').forEach(r => r.checked = false);

    document.getElementById('inp_peso_llegada').value         = '';
    document.getElementById('inp_peso_llegada_display').value = '';
    ['accion_entregado', 'accion_div_carga', 'accion_transbordo'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) { el.disabled = true; el.checked = false; }
    });
    document.getElementById('aviso_peso_requerido').style.display = '';

    document.getElementById('inp_fecha_llegada').min   = fechaSalida;
    document.getElementById('inp_fecha_llegada').value = fechaSalida;

    // Resetear todos los campos
    document.getElementById('sec_cliente').classList.add('d-none');
    document.getElementById('sel_cliente').value         = '';
    document.getElementById('sel_cliente_div').value     = '';
    document.getElementById('inp_direccion_entrega').value     = '';
    document.getElementById('inp_direccion_entrega_div').value = '';
    document.getElementById('sec_empresa_factura').classList.add('d-none');
    document.getElementById('sel_empresa_factura').value     = '';
    document.getElementById('sel_empresa_factura_div').value = '';
    document.getElementById('sec_precio_venta').classList.add('d-none');
    document.getElementById('inp_precio_ton').value             = '';
    document.getElementById('inp_precio_ton_display').value     = '';
    document.getElementById('lbl_total_venta').textContent      = '—';
    document.getElementById('inp_precio_ton_div').value         = '';
    document.getElementById('inp_precio_ton_div_display').value = '';
    document.getElementById('lbl_total_venta_div').textContent  = '—';
    document.getElementById('sec_parcial_cam').classList.add('d-none');
    document.getElementById('cam_inp_tn_parcial').value         = '';
    document.getElementById('cam_inp_tn_parcial_display').value = '';
    document.getElementById('cam_lbl_tn_restante').textContent = '—';
    document.getElementById('cam_hidden_camion').value     = camionId || '';
    document.getElementById('cam_hidden_conductor').value  = conductorId || '';
    document.getElementById('cam_hidden_fecha').value      = fechaSalida;
    document.getElementById('cam_hidden_tipo_tramo').value = tipoTramo || '';
    _camPesoSalida = pesoSalida;
    const chk = document.getElementById('chk_descuento');
    chk.checked = false;
    document.getElementById('sec_descuento').classList.add('d-none');
    document.getElementById('inp_descuento').value = '';
    document.querySelector('#formLlegada textarea[name="observaciones_llegada"]').value = '';
    const inpDoc = document.getElementById('inp_documento_entrega');
    if (inpDoc) inpDoc.value = '';

    // Cargar lotes de entrega del proveedor de este contrato
    const selLote = document.getElementById('sel_lote_entrega');
    selLote.innerHTML = '<option value="">Cargando lotes...</option>';
    fetch('{{ route("lotes_entrega.proveedor", ["proveedorId" => "__PID__"]) }}'.replace('__PID__', {{ $contrato->proveedor_id }}))
        .then(r => r.json())
        .then(lotes => {
            selLote.innerHTML = '';
            if (lotes.length === 0) {
                selLote.innerHTML = '<option value="">— Sin lotes disponibles —</option>';
                return;
            }
            lotes.forEach((l, i) => {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = l.nombre;
                if (i === 0) opt.selected = true; // semana más reciente primero
                selLote.appendChild(opt);
            });
        })
        .catch(() => { selLote.innerHTML = '<option value="">— Error al cargar —</option>'; });

    const btnConf = document.getElementById('btn_confirmar_llegada');
    btnConf.disabled  = true;
    btnConf.className = 'btn btn-secondary';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLlegada')).show();
}

let _camPesoSalida = 0;

function validarFormAsignar() {
    const conductor   = document.getElementById('cc_conductor_id')?.value;
    const peso        = document.getElementById('peso_declarado')?.value;
    const origen      = document.getElementById('formContratoCamion')?.querySelector('[name="origen"]')?.value?.trim();
    const destino     = document.getElementById('formContratoCamion')?.querySelector('[name="destino"]')?.value?.trim();
    const fecha       = document.getElementById('formContratoCamion')?.querySelector('[name="fecha_asignacion"]')?.value;
    const btn         = document.getElementById('btn_asignar_camion');
    if (!btn) return;
    const ok = !!conductor && parseFloat(peso) > 0 && !!origen && !!destino && !!fecha;
    btn.disabled = !ok;
}

function validarFormLlegada() {
    const peso   = document.getElementById('inp_peso_llegada')?.value;
    const fecha  = document.getElementById('inp_fecha_llegada')?.value;
    const accion = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    const btn    = document.getElementById('btn_confirmar_llegada');
    if (!btn) return;
    const ok = !!(peso && fecha && accion);
    btn.disabled  = !ok;
    btn.className = ok ? 'btn btn-success' : 'btn btn-secondary';
}

function submitLlegada(e) {
    e.preventDefault();

    // Limpiar errores anteriores
    document.querySelectorAll('#formLlegada .error-llegada').forEach(el => el.remove());
    document.querySelectorAll('#formLlegada .is-invalid-llegada').forEach(el => el.classList.remove('is-invalid-llegada', 'border-danger'));

    const accion    = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    const errores   = [];

    function marcarError(elId, msg) {
        errores.push(msg);
        const el = document.getElementById(elId);
        if (!el) return;
        el.classList.add('is-invalid-llegada', 'border-danger');
        const div = document.createElement('div');
        div.className = 'text-danger small mt-1 error-llegada';
        div.textContent = msg;
        el.parentNode.appendChild(div);
    }

    if (accion === 'entregado') {
        const cliente = document.getElementById('sel_cliente')?.value;
        const empresa = document.getElementById('sel_empresa_factura')?.value;
        if (!cliente) marcarError('sel_cliente', 'Debe seleccionar el cliente que recibe la carga.');
        if (!empresa) marcarError('sel_empresa_factura', 'Debe seleccionar la empresa que facturará.');
    }

    if (accion === 'div_carga') {
        const clienteDiv = document.getElementById('sel_cliente_div')?.value;
        const empresaDiv = document.getElementById('sel_empresa_factura_div')?.value;
        const tnParcial  = document.getElementById('cam_inp_tn_parcial')?.value;
        const destNuevo  = document.querySelector('#sec_parcial_cam [name="destino_nuevo_tramo"]')?.value.trim();
        if (!clienteDiv) marcarError('sel_cliente_div', 'Debe seleccionar el cliente que recibe la carga.');
        if (!empresaDiv) marcarError('sel_empresa_factura_div', 'Debe seleccionar la empresa que facturará.');
        if (!tnParcial || parseFloat(tnParcial) <= 0) marcarError('cam_inp_tn_parcial_display', 'Debe ingresar las toneladas entregadas.');
        if (!destNuevo) marcarError('cam_inp_destino_nuevo', 'Debe ingresar el destino del nuevo tramo.');
    }

    if (errores.length > 0) {
        const primero = document.querySelector('#formLlegada .error-llegada');
        if (primero) primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Deshabilitar campos de la sección oculta para que no se envíen
    if (accion === 'entregado') {
        ['sel_cliente_div', 'sel_empresa_factura_div', 'inp_direccion_entrega_div'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });
        document.querySelectorAll('#sec_parcial_cam [name="tn_parcial"], #sec_parcial_cam [name="destino_nuevo_tramo"]').forEach(function(el) {
            el.disabled = true;
        });
    } else if (accion === 'div_carga') {
        ['sel_cliente', 'sel_empresa_factura', 'inp_direccion_entrega'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });
    }

    document.getElementById('formLlegada').removeEventListener('submit', submitLlegada);
    document.getElementById('formLlegada').submit();
}

function accionCamionCambiada(accion) {
    document.querySelectorAll('#formLlegada .error-llegada').forEach(el => el.remove());
    document.querySelectorAll('#formLlegada .is-invalid-llegada').forEach(el => el.classList.remove('is-invalid-llegada', 'border-danger'));
    const secCliente        = document.getElementById('sec_cliente');
    const secEmpresa        = document.getElementById('sec_empresa_factura');
    const secPrecio         = document.getElementById('sec_precio_venta');
    const secParcial        = document.getElementById('sec_parcial_cam');

    // Ocultar todo primero
    secCliente.classList.add('d-none');
    secEmpresa.classList.add('d-none');
    secPrecio.classList.add('d-none');
    secParcial.classList.add('d-none');
    document.getElementById('inp_precio_ton').value             = '';
    document.getElementById('inp_precio_ton_display').value     = '';
    document.getElementById('lbl_total_venta').textContent      = '—';
    document.getElementById('inp_precio_ton_div').value         = '';
    document.getElementById('inp_precio_ton_div_display').value = '';
    document.getElementById('lbl_total_venta_div').textContent  = '—';
    document.getElementById('sel_cliente').value             = '';
    document.getElementById('sel_cliente_div').value         = '';
    document.getElementById('inp_direccion_entrega').value     = '';
    document.getElementById('inp_direccion_entrega_div').value = '';
    document.getElementById('sel_empresa_factura').value     = '';
    document.getElementById('sel_empresa_factura_div').value = '';

    if (accion === 'entregado') {
        secCliente.classList.remove('d-none');
        secEmpresa.classList.remove('d-none');
        secPrecio.classList.remove('d-none');
        calcTotalVenta();
    } else if (accion === 'div_carga') {
        secParcial.classList.remove('d-none');
        calcTotalVenta();
    }
    validarFormLlegada();
}

function calcRestanteCam() {
    const tnEntregadas = parseFloat(document.getElementById('cam_inp_tn_parcial').value) || 0;
    const pesoTotal    = parseFloat(document.getElementById('inp_peso_llegada').value) || 0;
    const restante     = Math.max(0, pesoTotal - tnEntregadas);
    document.getElementById('cam_lbl_tn_restante').textContent = restante > 0
        ? new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(restante) + ' t'
        : '—';
}

function validarFormTransbordo() {
    const camion    = document.getElementById('tsb_camion_id')?.value;
    const conductor = document.getElementById('tsb_conductor_id')?.value;
    const destino   = document.querySelector('#modalTransbordo [name="destino"]')?.value.trim();
    const peso      = document.getElementById('tsb_peso_salida')?.value;
    const fecha     = document.getElementById('tsb_fecha_salida')?.value;
    const btn       = document.getElementById('btn_registrar_transbordo');
    const aviso     = document.getElementById('tsb_sin_conductor_aviso');
    const sinConductor = aviso && !aviso.classList.contains('d-none');

    const completo = camion && conductor && destino && peso && fecha && !sinConductor;
    btn.disabled = !completo;
    btn.className = completo ? 'btn btn-info' : 'btn btn-secondary';
}

function abrirModalTransbordo(ccId, tramoPadreId, destino, infoPadre, disponible, fechaLlegadaPadre) {
    document.getElementById('tsb_cc_id').value                   = ccId;
    document.getElementById('tsb_padre_id').value                = tramoPadreId;
    document.getElementById('tsb_info_padre').textContent        = infoPadre;
    document.getElementById('tsb_peso_disponible').textContent   = disponible;

    const inp = document.getElementById('tsb_peso_salida');
    inp.max   = disponible;
    inp.value = '';
    inp.oninput = function () {
        if (parseFloat(this.value) > parseFloat(disponible)) this.value = disponible;
    };

    document.getElementById('tsb_fecha_salida').min   = fechaLlegadaPadre ?? '';
    document.getElementById('tsb_fecha_salida').value = fechaLlegadaPadre ?? '';

    const sel = document.getElementById('tsb_conductor_id');
    sel.innerHTML = '<option value="">— Seleccione un camión primero —</option>';
    sel.disabled  = true;

    const aviso = document.getElementById('tsb_sin_conductor_aviso');
    if (aviso) aviso.classList.add('d-none');
    const btnSubmit = document.getElementById('btn_registrar_transbordo');
    if (btnSubmit) btnSubmit.disabled = true;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTransbordo')).show();
}

function confirmarToggleCC(uuid, placa, activo) {
    const header = document.getElementById('modalToggleCC_header');
    const titulo = document.getElementById('modalToggleCC_titulo');
    const body   = document.getElementById('modalToggleCC_body');
    const btn    = document.getElementById('modalToggleCC_btn');

    if (activo) {
        header.className = 'modal-header bg-warning';
        titulo.innerHTML = '<i class="bi bi-slash-circle"></i> Desactivar Asignación';
        body.innerHTML   = 'El camión <strong>' + placa + '</strong> quedará desactivado en este contrato. El registro se conserva en el historial.';
        btn.className    = 'btn btn-warning';
        btn.textContent  = 'Sí, desactivar';
    } else {
        header.className = 'modal-header bg-success text-white';
        titulo.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Reactivar Asignación';
        body.innerHTML   = '¿Reactivar la asignación del camión <strong>' + placa + '</strong>?';
        btn.className    = 'btn btn-success';
        btn.textContent  = 'Sí, reactivar';
    }

    btn.href = '{{ url("contrato-camion") }}/' + uuid + '/toggle-activo';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalToggleCC')).show();
}

function confirmarToggleTramo(url, placa, ruta, activo) {
    const header = document.getElementById('modalToggleTramo_header');
    const titulo = document.getElementById('modalToggleTramo_titulo');
    const body   = document.getElementById('modalToggleTramo_body');
    const btn    = document.getElementById('modalToggleTramo_btn');

    if (activo) {
        header.className = 'modal-header bg-warning';
        titulo.innerHTML = '<i class="bi bi-slash-circle"></i> Desactivar Tramo';
        body.textContent = 'El tramo quedará desactivado. Las toneladas que llevaba quedarán disponibles para reasignar. El registro se conserva en el historial.';
        btn.className    = 'btn btn-warning';
        btn.textContent  = 'Sí, desactivar';
    } else {
        header.className = 'modal-header bg-success text-white';
        titulo.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Reactivar Tramo';
        body.textContent = '¿Reactivar este tramo? Las toneladas volverán a contabilizarse en el cálculo del padre.';
        btn.className    = 'btn btn-success';
        btn.textContent  = 'Sí, reactivar';
    }

    document.getElementById('modalToggleTramo_placa').textContent = placa;
    document.getElementById('modalToggleTramo_ruta').textContent  = ruta;
    btn.href = url;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalToggleTramo')).show();
}

function calcTotalVenta() {
    const accion = document.querySelector('#formLlegada input[name="accion"]:checked')?.value;
    const fmt    = v => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);

    if (accion === 'div_carga') {
        const precio    = parseFloat(document.getElementById('inp_precio_ton_div').value) || 0;
        const toneladas = parseFloat(document.getElementById('cam_inp_tn_parcial').value) || 0;
        const lbl       = document.getElementById('lbl_total_venta_div');
        if (lbl) lbl.textContent = (toneladas > 0 && precio > 0) ? fmt(toneladas * precio) : '—';
    } else {
        const precio    = parseFloat(document.getElementById('inp_precio_ton').value) || 0;
        const toneladas = parseFloat(document.getElementById('inp_peso_llegada').value) || 0;
        const lbl       = document.getElementById('lbl_total_venta');
        const msg       = document.getElementById('precio_venta_base_msg');
        if (msg) msg.textContent = 'Basado en el peso de llegada ingresado arriba.';
        if (lbl) lbl.textContent = (toneladas > 0 && precio > 0) ? fmt(toneladas * precio) : '—';
    }
}

// ===== Input estilo cajero (formato boliviano) =====
(function () {
    function textoANumero(txt) {
        return parseFloat(txt.replace(/\./g, '').replace(',', '.')) || 0;
    }

    function formatear(num) {
        return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    }

    function onInput(displayId, hiddenId) {
        return function (e) {
            const input = e.target;
            let raw = input.value.replace(/[^0-9,]/g, '');

            const partes = raw.split(',');
            if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');

            const [ent, dec] = raw.split(',');
            if (dec !== undefined && dec.length > 2) raw = ent + ',' + dec.substring(0, 2);

            const [e2, d2] = raw.split(',');
            const entF  = e2.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const nuevo = d2 !== undefined ? entF + ',' + d2 : entF;

            const diff = nuevo.length - input.value.length;
            const pos  = input.selectionStart + diff;
            input.value = nuevo;
            try { input.setSelectionRange(pos, pos); } catch (_) {}

            document.getElementById(hiddenId).value = textoANumero(nuevo) || '';
        };
    }

    function onBlur(displayId, hiddenId) {
        return function () {
            const num = parseFloat(document.getElementById(hiddenId).value);
            if (!num) return;
            document.getElementById(displayId).value = formatear(num);
        };
    }

    function initCajero(displayId, hiddenId, onChange) {
        const display = document.getElementById(displayId);
        const hidden  = document.getElementById(hiddenId);
        if (!display || !hidden) return;

        // Si hay valor previo (old() tras error de validación), mostrarlo formateado
        if (hidden.value) display.value = formatear(parseFloat(hidden.value));

        display.addEventListener('input', function (e) {
            onInput(displayId, hiddenId)(e);
            if (typeof onChange === 'function') onChange();
        });
        display.addEventListener('blur',  onBlur(displayId, hiddenId));
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCajero('peso_declarado_display', 'peso_declarado', validarFormAsignar);
        initCajero('monto_acordado_display', 'monto_acordado');

        // Validar form asignar al cambiar origen, destino o fecha
        document.getElementById('formContratoCamion')?.querySelectorAll('[name="origen"],[name="destino"],[name="fecha_asignacion"]').forEach(function (el) {
            el.addEventListener('input', validarFormAsignar);
            el.addEventListener('change', validarFormAsignar);
        });

        // Sincronizar dirección de entrega al elegir cliente+dirección (Entregado)
        document.getElementById('sel_cliente')?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('inp_direccion_entrega').value = opt ? (opt.dataset.direccion ?? '') : '';
            validarFormLlegada();
        });

        // Sincronizar dirección de entrega al elegir cliente+dirección (Div. Carga)
        document.getElementById('sel_cliente_div')?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('inp_direccion_entrega_div').value = opt ? (opt.dataset.direccion ?? '') : '';
            validarFormLlegada();
        });

        // Precio por tonelada (Entregado)
        const dispPrecio = document.getElementById('inp_precio_ton_display');
        if (dispPrecio) {
            dispPrecio.addEventListener('input', function (e) {
                onInput('inp_precio_ton_display', 'inp_precio_ton')(e);
                calcTotalVenta();
            });
            dispPrecio.addEventListener('blur', onBlur('inp_precio_ton_display', 'inp_precio_ton'));
        }

        // Precio por tonelada (Div. Carga)
        const dispPrecioDiv = document.getElementById('inp_precio_ton_div_display');
        if (dispPrecioDiv) {
            dispPrecioDiv.addEventListener('input', function (e) {
                onInput('inp_precio_ton_div_display', 'inp_precio_ton_div')(e);
                calcTotalVenta();
            });
            dispPrecioDiv.addEventListener('blur', onBlur('inp_precio_ton_div_display', 'inp_precio_ton_div'));
        }

        // Peso llegada — habilita/deshabilita opciones de acción según si hay valor
        function _actualizarRadiosAccion() {
            const tiene = parseFloat(document.getElementById('inp_peso_llegada').value) > 0;
            document.getElementById('aviso_peso_requerido').style.display = tiene ? 'none' : '';
            ['accion_entregado', 'accion_div_carga', 'accion_transbordo'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.disabled = !tiene;
                if (!tiene && el.checked) {
                    el.checked = false;
                    accionCamionCambiada(null);
                }
            });
        }

        const dispLlegada = document.getElementById('inp_peso_llegada_display');
        if (dispLlegada) {
            dispLlegada.addEventListener('input', function (e) {
                onInput('inp_peso_llegada_display', 'inp_peso_llegada')(e);
                _actualizarRadiosAccion();
                calcTotalVenta();
                calcRestanteCam();
            });
            dispLlegada.addEventListener('blur', onBlur('inp_peso_llegada_display', 'inp_peso_llegada'));
        }

        // TN parcial entrega
        const dispParcial = document.getElementById('cam_inp_tn_parcial_display');
        if (dispParcial) {
            dispParcial.addEventListener('input', function (e) {
                onInput('cam_inp_tn_parcial_display', 'cam_inp_tn_parcial')(e);
                calcRestanteCam();
                calcTotalVenta();
            });
            dispParcial.addEventListener('blur', onBlur('cam_inp_tn_parcial_display', 'cam_inp_tn_parcial'));
        }
    });
})();

// Validación del botón Asignar Camión
(function () {
    const camposReq = ['cc_camion_id', 'cc_conductor_id', 'origen', 'destino', 'peso_declarado', 'fecha_asignacion'];

    function verificarFormCC() {
        const btn = document.getElementById('btn_asignar_camion');
        if (!btn) return;
        const completo = camposReq.every(function (id) {
            const el = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
            return el && el.value && el.value.trim() !== '';
        });
        btn.disabled = !completo;
    }

    document.addEventListener('DOMContentLoaded', function () {
        camposReq.forEach(function (id) {
            const el = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
            if (el) el.addEventListener('change', verificarFormCC);
            if (el) el.addEventListener('input', verificarFormCC);
        });
        // Escuchar cambios de Select2 en camión y conductor (se re-une en cada carga dinámica)
        $('#cc_camion_id').on('change', verificarFormCC);
        // El conductor Select2 se inicializa dinámicamente; usamos delegación a nivel documento
        $(document).on('change', '#cc_conductor_id', verificarFormCC);
        verificarFormCC();
    });
})();

// Bloqueo anti-doble-submit
['formContratoCamion', 'formTramoTransbordo'].forEach(function(fid) {
    var f = document.getElementById(fid);
    if (!f) return;
    f.addEventListener('submit', function(e) {
        var btn = f.querySelector('button[type="submit"]');
        if (!btn || btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
});

@if(isset($tramoErrorLlegada) && $tramoErrorLlegada && $errors->llegada->any())
// Reabrir modal de llegada con los datos del tramo que falló
document.addEventListener('DOMContentLoaded', function () {
    const t = {
        uuid:       '{{ $tramoErrorLlegada->uuid }}',
        info:       '{{ addslashes($tramoErrorLlegada->origen . " → " . $tramoErrorLlegada->destino . " (" . $tramoErrorLlegada->camion->placa . ")") }}',
        pesoSalida: {{ $tramoErrorLlegada->peso_salida }},
        fechaSalida:'{{ $tramoErrorLlegada->fecha_salida->format("Y-m-d") }}',
        camionId:   {{ $tramoErrorLlegada->camion_id }},
        conductorId:{{ $tramoErrorLlegada->conductor_id ?? 'null' }},
        tipoTramo:  '{{ $tramoErrorLlegada->tipo_tramo }}',
    };
    abrirModalLlegada(t.uuid, t.info, t.pesoSalida, t.fechaSalida, t.camionId, t.conductorId, t.tipoTramo);

    // Restaurar campos del old input
    @if(old('peso_llegada'))
        document.getElementById('inp_peso_llegada').value         = '{{ old("peso_llegada") }}';
        document.getElementById('inp_peso_llegada_display').value = new Intl.NumberFormat('es-BO', {minimumFractionDigits:2,maximumFractionDigits:2}).format({{ old("peso_llegada") }});
        document.getElementById('aviso_peso_requerido').style.display = 'none';
        ['accion_entregado','accion_div_carga','accion_transbordo'].forEach(function(id){
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
    @endif
    @if(old('fecha_llegada'))
        document.getElementById('inp_fecha_llegada').value = '{{ old("fecha_llegada") }}';
    @endif
    @if(old('accion'))
        const radioOld = document.querySelector('#formLlegada input[name="accion"][value="{{ old("accion") }}"]');
        if (radioOld) { radioOld.checked = true; accionCamionCambiada('{{ old("accion") }}'); }
    @endif
    @if(old('cliente_id'))
        document.getElementById('sel_cliente').value = '{{ old("cliente_id") }}';
    @endif
    @if(old('empresa_facturadora_id'))
        document.getElementById('sel_empresa_factura').value = '{{ old("empresa_facturadora_id") }}';
    @endif

    validarFormLlegada();
});
@endif
</script>
@endsection
