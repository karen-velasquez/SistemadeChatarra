@extends('layouts.app')
@section('titulo','Empresas')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>GESTIÓN DE EMPRESAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Empresas</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"🏢 En <b>Gestión de Empresas</b> administras las empresas del grupo y sus cuentas bancarias o de efectivo. Cada cuenta lleva un saldo que se actualiza solo con los pagos."},
                        {"element":"#emp-cards-resumen","intro":"📊 Resumen global: total de empresas, total de cuentas y el saldo general de todo el grupo.","position":"bottom"},
                        {"element":"#emp-grid","intro":"🏢 Cada tarjeta es una <b>empresa</b> con su saldo y sus cuentas. Haz clic en una cuenta para ver sus movimientos.","position":"top"},
                        {"element":"#emp-grid .btn-group:first-child","intro":"⚙️ El menú de <b>opciones</b> (tres puntos) de cada empresa: ver movimientos, agregar una cuenta, ver información, editar o eliminar.","position":"left"},
                        {"element":"#btnNuevaEmpresa","intro":"➕ Con <b>Nueva Empresa</b> registras una empresa nueva. El formulario tiene su propia guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('empresas.create')
            <button id="btnNuevaEmpresa" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmpresa" onclick="resetModal()">
                <i class="bi bi-plus-lg"></i> Nueva Empresa
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">

    {{-- Banner informativo --}}
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex gap-3 align-items-start small">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Gestión de Empresas</strong> — Aquí se administran las empresas y sus cuentas bancarias o de efectivo.
            Cada cuenta tiene un saldo que se actualiza automáticamente con los pagos registrados en el sistema.
            Puedes ver los movimientos de cada cuenta haciendo clic sobre ella, o usar <strong>Ver Movimientos</strong> para ver el historial completo de una empresa.
            Para agregar una nueva cuenta a una empresa existente, usa el menú de opciones (<i class="bi bi-three-dots"></i>) de cada tarjeta.
        </div>
    </div>

    {{-- Card resumen general --}}
    @php
        $saldoTotal    = $empresas->sum(fn($e) => $e->cuentas->sum('saldo_actual'));
        $totalCuentas  = $empresas->sum(fn($e) => $e->cuentas->count());
    @endphp
    <div class="row g-3 mb-4" id="emp-cards-resumen">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-building fs-5 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Empresas</div>
                        <div class="fs-5 fw-bold text-primary">{{ $empresas->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-info bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-wallet2 fs-5 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Cuentas</div>
                        <div class="fs-5 fw-bold text-info">{{ $totalCuentas }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-cash-stack fs-5 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Saldo General</div>
                        <div class="fs-5 fw-bold {{ $saldoTotal >= 0 ? 'text-success' : 'text-danger' }}">
                            BOB {{ number_format($saldoTotal, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards por empresa --}}
    <div class="row g-3" id="emp-grid">
        @forelse($empresas as $empresa)
        @php
            $saldoEmpresa   = $empresa->cuentas->sum('saldo_actual');
            $cuentasOrden   = $empresa->cuentas->sortByDesc('saldo_actual');
            $pctCuentas     = $saldoTotal > 0 ? min(100, round($saldoEmpresa / $saldoTotal * 100)) : 0;
        @endphp
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100" data-empresa-uuid="{{ $empresa->uuid }}" data-total-cuentas="{{ $empresa->cuentas->count() }}" data-saldo-total="{{ number_format($saldoEmpresa, 2, ',', '.') }}">
                <div class="card-body d-flex flex-column pt-4">

                    {{-- Header empresa --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-0">{{ $empresa->nombre }}</h5>
                            @if($empresa->nit)
                                <small class="text-muted">NIT: {{ $empresa->nit }}</small>
                            @endif
                            @if($empresa->razon_social)
                                <div class="text-muted small">{{ $empresa->razon_social }}</div>
                            @endif
                        </div>
                        <div class="btn-group ms-2">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('empresas.cuentas', $empresa->uuid) }}">
                                        <i class="bi bi-graph-up-arrow me-2"></i> Ver Movimientos
                                    </a>
                                </li>
                                @can('cuentas_bancarias.create')
                                <li>
                                    <button class="dropdown-item" onclick="abrirModalCuenta('{{ $empresa->uuid }}', '{{ addslashes($empresa->nombre) }}')">
                                        <i class="bi bi-plus-circle me-2 text-success"></i> Nueva Cuenta
                                    </button>
                                </li>
                                @endcan
                                <li><hr class="dropdown-divider"></li>
                                @can('empresas.index')
                                <li>
                                    <button class="dropdown-item" onclick="verInfoEmpresa('{{ $empresa->uuid }}')">
                                        <i class="bi bi-info-circle me-2 text-primary"></i> Ver Información
                                    </button>
                                </li>
                                @endcan
                                @can('empresas.edit')
                                <li>
                                    <button class="dropdown-item" onclick="editarEmpresa('{{ $empresa->uuid }}')">
                                        <i class="bi bi-pencil me-2"></i> Editar Empresa
                                    </button>
                                </li>
                                @endcan
                                @can('empresas.destroy')
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    @if($empresa->puedeEliminar())
                                        <a class="dropdown-item text-danger" href="{{ route('empresas.destroy', $empresa->uuid) }}"
                                            onclick="return confirm('¿Eliminar empresa {{ $empresa->nombre }}?')">
                                            <i class="bi bi-trash me-2"></i> Eliminar
                                        </a>
                                    @else
                                        <span class="dropdown-item text-muted"
                                              style="cursor: not-allowed;"
                                              title="Esta empresa tiene movimientos, no se puede eliminar">
                                            <i class="bi bi-trash me-2"></i> Eliminar
                                        </span>
                                    @endif
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- Saldo total + barra de participación --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <small class="text-muted">Saldo total</small>
                            @if($saldoTotal > 0)
                            <small class="text-muted">{{ $pctCuentas }}% del total</small>
                            @endif
                        </div>
                        <div class="fs-5 fw-bold {{ $saldoEmpresa >= 0 ? 'text-success' : 'text-danger' }} mb-1">
                            BOB {{ number_format($saldoEmpresa, 2, ',', '.') }}
                        </div>
                        @if($saldoTotal > 0)
                        <div class="progress" style="height:5px;">
                            <div class="progress-bar bg-primary" style="width:{{ $pctCuentas }}%"></div>
                        </div>
                        @endif
                    </div>

                    {{-- Cuentas --}}
                    @if($cuentasOrden->count())
                    <div class="d-flex flex-column gap-1 mt-1 flex-grow-1">
                        @foreach($cuentasOrden as $cuenta)
                        @php $pctCuenta = $saldoEmpresa > 0 ? min(100, round($cuenta->saldo_actual / $saldoEmpresa * 100)) : 0; @endphp
                        <div class="rounded border px-2 py-2 {{ !$cuenta->activo ? 'opacity-50' : '' }}"
                             style="background:#f8f9fa; transition:background .15s;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <a href="{{ route('tesoreria.cuenta', $cuenta->uuid) }}" class="text-decoration-none flex-grow-1 me-2">
                                    <div>
                                        <div class="small fw-semibold text-dark d-flex align-items-center gap-1">
                                            <i class="bi bi-wallet2 text-primary"></i>
                                            {{ $cuenta->nombre_cuenta }}
                                            @if(!$cuenta->activo)
                                                <span class="badge bg-secondary" style="font-size:.6rem">Inactiva</span>
                                            @endif
                                        </div>
                                        @if($cuenta->banco)
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-bank me-1"></i>{{ $cuenta->banco->nombre }}
                                            @if($cuenta->numero_cuenta)
                                                &nbsp;·&nbsp;<span class="font-monospace">{{ $cuenta->numero_cuenta }}</span>
                                            @endif
                                        </div>
                                        @else
                                        <div class="text-muted" style="font-size:.72rem">
                                            <i class="bi bi-cash me-1"></i>Efectivo / Sin banco
                                        </div>
                                        @endif
                                        @if($cuenta->descripcion)
                                        <div class="text-muted fst-italic" style="font-size:.68rem" title="{{ $cuenta->descripcion }}">
                                            {{ \Illuminate\Support\Str::limit($cuenta->descripcion, 50) }}
                                        </div>
                                        @endif
                                    </div>
                                </a>
                                <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                    <div class="text-end">
                                        <div class="small fw-bold {{ $cuenta->saldo_actual >= 0 ? 'text-success' : 'text-danger' }}">
                                            BOB {{ number_format($cuenta->saldo_actual, 2, ',', '.') }}
                                        </div>
                                        @if($saldoEmpresa > 0)
                                        <div class="text-muted" style="font-size:.65rem">{{ $pctCuenta }}%</div>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-column gap-1">
                                        @can('cuentas_bancarias.edit')
                                        <button class="btn btn-outline-secondary btn-sm p-0 px-1"
                                                onclick="editarCuenta('{{ $cuenta->uuid }}')"
                                                title="Editar cuenta"
                                                style="font-size:.7rem; line-height:1.4;">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @endcan
                                        @can('cuentas_bancarias.destroy')
                                            @if($cuenta->puedeEliminar())
                                            <a class="btn btn-outline-danger btn-sm p-0 px-1"
                                               href="{{ route('empresas.cuenta.destroy', $cuenta->uuid) }}"
                                               onclick="return confirm('¿Eliminar la cuenta {{ $cuenta->nombre_cuenta }}?')"
                                               title="Eliminar cuenta"
                                               style="font-size:.7rem; line-height:1.4;">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @if($saldoEmpresa > 0 && $cuenta->activo)
                            <div class="progress" style="height:3px;">
                                <div class="progress-bar bg-info" style="width:{{ $pctCuenta }}%"></div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-3 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-wallet2 fs-3 mb-1"></i>
                        <div class="small">Sin cuentas registradas</div>
                        @can('cuentas_bancarias.create')
                        <button class="btn btn-sm btn-outline-primary mt-2"
                            onclick="abrirModalCuenta('{{ $empresa->uuid }}', '{{ addslashes($empresa->nombre) }}')">
                            <i class="bi bi-plus-lg"></i> Agregar cuenta
                        </button>
                        @endcan
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-building fs-1"></i>
            <p class="mt-2">No hay empresas registradas.</p>
        </div>
        @endforelse
    </div>
</section>

{{-- MODAL EDITAR CUENTA --}}
<div class="modal fade" id="modalEditarCuenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header position-relative">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Cuenta</h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-3" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarCuenta" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre de la cuenta <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_cuenta" id="ec_nombre_cuenta" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banco <span class="text-danger">*</span></label>
                            <select name="banco_id" id="ec_banco" class="form-select" required>
                                <option value="">-- Seleccione un banco --</option>
                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° de cuenta <span class="text-danger">*</span></label>
                            <input type="text" name="numero_cuenta" id="ec_numero_cuenta" class="form-control font-monospace"
                                inputmode="numeric" maxlength="20" required
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,20)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saldo Inicial <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" id="ec_saldo_inicial_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="saldo_inicial" id="ec_saldo_inicial" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="activo" id="ec_activo" class="form-select">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                        <div class="col-12 d-none" id="ec_aviso_bloqueo">
                            <div class="alert alert-warning py-2 mb-0 small">
                                <i class="bi bi-lock-fill me-1"></i>
                                Esta cuenta ya tiene movimientos registrados: el banco, el N° de cuenta y el saldo inicial ya no se pueden editar.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="ec_descripcion" class="form-control" rows="2" maxlength="60"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEditarCuenta"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL NUEVA CUENTA --}}
<div class="modal fade" id="modalNuevaCuenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header position-relative">
                <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Nueva Cuenta — <span id="nc_empresa_nombre"></span></h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-3" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaCuenta" method="POST" action="">
                @csrf
                <input type="hidden" name="redirect_to" value="index">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenEmpresaCuenta" value="{{ $tokenEmpresaCuenta ?? '' }}">
                <div class="modal-body">
                    <p class="text-muted small mb-3"><span class="text-danger">*</span> Todos los campos marcados son obligatorios.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre de la cuenta <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_cuenta" id="nc_nombre_cuenta" class="form-control" required
                                placeholder="Ej: Cuenta Principal, Caja Chica..."
                                oninput="validarFormularioCuenta()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banco <span class="text-danger">*</span></label>
                            <select name="banco_id" id="nc_banco" class="form-select" required onchange="validarFormularioCuenta()">
                                <option value="">-- Seleccione un banco --</option>
                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° de cuenta <span class="text-danger">*</span></label>
                            <input type="text" name="numero_cuenta" id="nc_numero_cuenta" class="form-control font-monospace"
                                placeholder="Ej: 1001234567"
                                inputmode="numeric"
                                maxlength="20"
                                required
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,20);document.getElementById('nc_contador').textContent=this.value.length+' / 20';validarFormularioCuenta()">
                            <div class="form-text text-end" id="nc_contador">0 / 20</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Moneda <span class="text-danger">*</span></label>
                            <select name="moneda" id="nc_moneda" class="form-select" required onchange="validarFormularioCuenta()">
                                @foreach($monedas as $moneda)
                                    <option value="{{ $moneda->valor }}" {{ $moneda->valor === 'BOB' ? 'selected' : '' }}>
                                        {{ $moneda->valor }} — {{ $moneda->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saldo inicial <span class="text-danger">*</span></label>
                            <input type="text" inputmode="numeric" id="nc_saldo_inicial_display" class="form-control" placeholder="0,00" autocomplete="off">
                            <input type="hidden" name="saldo_inicial" id="nc_saldo_inicial" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCuenta" disabled><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EMPRESA --}}
<div class="modal fade" id="modalEmpresa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header position-relative">
                <h5 class="modal-title"><i class="bi bi-building"></i> <span id="tituloModal">Nueva Empresa</span></h5>
                <div class="d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-2 me-3">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalEmpresa"
                            data-steps='[
                                {"intro":"📝 Registra una empresa del grupo. Los campos con <span style=\"color:#dc3545\">*</span> son obligatorios; el botón Registrar se activa al completarlos."},
                                {"element":"#nombre","intro":"🏢 <b>Nombre</b> de la empresa (ej: Empresa Ejemplo S.R.L.).","position":"bottom"},
                                {"element":"#nit","intro":"🔢 <b>NIT / RUC</b>: el documento tributario. Solo números.","position":"bottom"},
                                {"element":"#razon_social","intro":"📄 <b>Razón Social</b>: el nombre legal completo de la empresa.","position":"bottom"},
                                {"element":"#telefono","intro":"📞 <b>Teléfono</b> (opcional): elige el país y escribe el número.","position":"top"},
                                {"element":"#email","intro":"✉️ <b>Email</b> (opcional): se valida el formato.","position":"top"},
                                {"element":"#btnGuardar","intro":"💾 Pulsa <b>Registrar</b> para guardar la empresa. Luego podrás agregarle cuentas.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formEmpresa" method="POST" action="{{ route('empresas.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodEmpresa" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenEmpresa" value="{{ $tokenEmpresa ?? '' }}">
                <div class="modal-body">
                    <p class="text-muted small mb-3"><span class="text-danger">*</span> Todos los campos marcados son obligatorios.</p>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required
                                placeholder="Ej: Empresa Ejemplo S.R.L."
                                oninput="validarFormularioEmpresa()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">NIT / RUC <span class="text-danger">*</span></label>
                            <input type="text" name="nit" id="nit" class="form-control font-monospace" required
                                placeholder="Ej: 1234567890"
                                inputmode="numeric"
                                maxlength="15"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,15);document.getElementById('nit_contador').textContent=this.value.length+' / 15';validarFormularioEmpresa()">
                            <div class="form-text text-end" id="nit_contador">0 / 15</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control" required
                                placeholder="Ej: Empresa Ejemplo Sociedad de Responsabilidad Limitada"
                                oninput="validarFormularioEmpresa()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio de referencia (BOB/t)</label>
                            <input type="number" step="0.01" min="0" name="precio_referencia" id="precio_referencia" class="form-control" placeholder="Ej. 3500.00">
                            <small class="text-muted">Precio por tonelada sugerido al registrar una entrega facturada por esta empresa. Se actualiza solo si se factura con un precio distinto.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <div class="input-group">
                                <select id="tel_pais" class="form-select flex-grow-0" style="width:115px; min-width:115px; max-width:115px;"
                                    onchange="actualizarPrefijoTelefonoEmp()">
                                    <option value="Bolivia" data-code="+591" data-maxlen="8" data-placeholder="Ej: 70000000">BO +591</option>
                                    <option value="Argentina" data-code="+54" data-maxlen="10" data-placeholder="Ej: 1150000000">AR +54</option>
                                    <option value="Brasil" data-code="+55" data-maxlen="11" data-placeholder="Ej: 11900000000">BR +55</option>
                                    <option value="Chile" data-code="+56" data-maxlen="9" data-placeholder="Ej: 912345678">CL +56</option>
                                    <option value="Colombia" data-code="+57" data-maxlen="10" data-placeholder="Ej: 3001234567">CO +57</option>
                                    <option value="Perú" data-code="+51" data-maxlen="9" data-placeholder="Ej: 912345678">PE +51</option>
                                    <option value="Paraguay" data-code="+595" data-maxlen="9" data-placeholder="Ej: 981234567">PY +595</option>
                                    <option value="Uruguay" data-code="+598" data-maxlen="9" data-placeholder="Ej: 912345678">UY +598</option>
                                    <option value="Ecuador" data-code="+593" data-maxlen="9" data-placeholder="Ej: 987654321">EC +593</option>
                                    <option value="Venezuela" data-code="+58" data-maxlen="10" data-placeholder="Ej: 4121234567">VE +58</option>
                                    <option value="México" data-code="+52" data-maxlen="10" data-placeholder="Ej: 5512345678">MX +52</option>
                                    <option value="Estados Unidos" data-code="+1" data-maxlen="10" data-placeholder="Ej: 2025550100">US +1</option>
                                    <option value="Canadá" data-code="+1" data-maxlen="10" data-placeholder="Ej: 4165550100">CA +1</option>
                                    <option value="España" data-code="+34" data-maxlen="9" data-placeholder="Ej: 612345678">ES +34</option>
                                </select>
                                <input type="hidden" name="telefono_prefijo" id="telefono_prefijo" value="+591">
                                <input type="text" class="form-control" name="telefono" id="telefono"
                                    maxlength="8" placeholder="Ej: 70000000"
                                    oninput="validarTelefonoEmp(this)">
                            </div>
                            <div id="telefono_feedback" class="form-text d-none"></div>
                            <small id="tel_hint" class="text-muted">Bolivia: 8 dígitos comenzando en 6 o 7</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" name="email" id="email" class="form-control"
                                placeholder="ejemplo@correo.com"
                                oninput="validarEmail(this)"
                                onblur="validarEmail(this)">
                            <div class="form-text" id="email_hint" style="min-height:1.2em"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control"
                                placeholder="Ej: Av. Principal N° 123, La Paz">
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar" disabled>Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL VER INFORMACIÓN DE EMPRESA --}}
<div class="modal fade" id="modalInfoEmpresa" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-gradient text-white border-0">
                <h5 class="modal-title">
                    <i class="bi bi-building-fill me-2"></i>Información de la Empresa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        style="opacity: 1; background: transparent url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27 fill=%27%23fff%27%3e%3cpath d=%27M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z%27/%3e%3c/svg%3e') center/1em auto no-repeat;"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Datos Generales --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                            <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-primary">Datos Generales</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-building me-1"></i>Nombre
                                </label>
                                <div class="fw-semibold" id="info_nombre">-</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-card-text me-1"></i>NIT / RUC
                                </label>
                                <div class="fw-semibold font-monospace" id="info_nit">-</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-file-text me-1"></i>Razón Social
                                </label>
                                <div class="fw-semibold" id="info_razon_social">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contacto --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-2">
                            <i class="bi bi-telephone-fill text-success fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-success">Contacto</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-telephone me-1"></i>Teléfono
                                </label>
                                <div class="fw-semibold" id="info_telefono">-</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-envelope me-1"></i>Email
                                </label>
                                <div class="fw-semibold" id="info_email">-</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded border bg-light">
                                <label class="text-muted small d-block mb-1">
                                    <i class="bi bi-geo-alt me-1"></i>Dirección
                                </label>
                                <div class="fw-semibold" id="info_direccion">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resumen Financiero --}}
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-2">
                            <i class="bi bi-cash-stack text-warning fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-warning">Resumen Financiero</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded border bg-info bg-opacity-10 text-center">
                                <label class="text-muted small d-block mb-2">
                                    <i class="bi bi-wallet2 me-1"></i>Total Cuentas
                                </label>
                                <div class="fw-bold fs-3 text-info" id="info_total_cuentas">-</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="p-3 rounded border bg-light text-center">
                                <label class="text-muted small d-block mb-2">
                                    <i class="bi bi-currency-exchange me-1"></i>Saldo Total
                                </label>
                                <div class="fw-bold fs-3" id="info_saldo_total">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Validación de teléfono por país
const _prefijosEmp = {
    'Bolivia': { code: '+591', hint: 'Bolivia: 8 dígitos comenzando en 6 o 7', pattern: /^[67]\d{7}$/ },
    'Argentina': { code: '+54', hint: 'Argentina: 10 dígitos (ej: 11 + 8 dígitos)', pattern: /^\d{10}$/ },
    'Brasil': { code: '+55', hint: 'Brasil: 11 dígitos (ej: 11 + 9 dígitos)', pattern: /^\d{11}$/ },
    'Chile': { code: '+56', hint: 'Chile: 9 dígitos comenzando en 9', pattern: /^9\d{8}$/ },
    'Colombia': { code: '+57', hint: 'Colombia: 10 dígitos (ej: 300 + 7 dígitos)', pattern: /^\d{10}$/ },
    'Perú': { code: '+51', hint: 'Perú: 9 dígitos comenzando en 9', pattern: /^9\d{8}$/ },
    'Paraguay': { code: '+595', hint: 'Paraguay: 9 dígitos comenzando en 9', pattern: /^9\d{8}$/ },
    'Uruguay': { code: '+598', hint: 'Uruguay: 9 dígitos comenzando en 9', pattern: /^9\d{8}$/ },
    'Ecuador': { code: '+593', hint: 'Ecuador: 9 dígitos comenzando en 9', pattern: /^9\d{8}$/ },
    'Venezuela': { code: '+58', hint: 'Venezuela: 10 dígitos (ej: 412 + 7 dígitos)', pattern: /^\d{10}$/ },
    'México': { code: '+52', hint: 'México: 10 dígitos (ej: 55 + 8 dígitos)', pattern: /^\d{10}$/ },
    'Estados Unidos': { code: '+1', hint: 'Ej: 2025550100', pattern: /^\d{10}$/ },
    'Canadá': { code: '+1', hint: 'Ej: 4165550100', pattern: /^\d{10}$/ },
    'España': { code: '+34', hint: 'Ej: 612345678', pattern: /^\d{9}$/ },
};

function actualizarPrefijoTelefonoEmp() {
    const sel = document.getElementById('tel_pais');
    const opt = sel.options[sel.selectedIndex];
    const code = opt ? (opt.dataset.code || '+') : '+';
    const pais = opt ? opt.value : '';
    const maxlen = opt && opt.dataset.maxlen ? parseInt(opt.dataset.maxlen) : 15;
    const ph = opt && opt.dataset.placeholder ? opt.dataset.placeholder : 'Número';
    const info = _prefijosEmp[pais] || { hint: 'Ingrese el número sin código de país' };

    const inp = document.getElementById('telefono');
    document.getElementById('telefono_prefijo').value = code;
    document.getElementById('tel_hint').textContent = info.hint;
    inp.maxLength = maxlen;
    inp.placeholder = ph;
    inp.value = '';

    const fb = document.getElementById('telefono_feedback');
    fb.className = 'form-text d-none';
    inp.classList.remove('is-valid', 'is-invalid');
}

function validarTelefonoEmp(input) {
    input.value = input.value.replace(/[^0-9]/g, '');

    const pais = document.getElementById('tel_pais').value;
    const info = _prefijosEmp[pais];
    const fb = document.getElementById('telefono_feedback');
    const val = input.value;
    const maxlen = parseInt(input.maxLength) || 15;

    if (!val) {
        fb.className = 'form-text d-none';
        input.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (val.length < maxlen) {
        fb.className = 'form-text text-danger';
        fb.textContent = '⚠ Faltan ' + (maxlen - val.length) + ' dígito(s)';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        return;
    }

    if (info && info.pattern && !info.pattern.test(val)) {
        fb.className = 'form-text text-danger';
        fb.textContent = '⚠ Formato inválido para ' + pais;
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else {
        fb.className = 'form-text text-success';
        fb.textContent = '✓ Teléfono válido';
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }
}

function validarEmail(input) {
    const hint  = document.getElementById('email_hint');
    const val   = input.value.trim();

    if (!val) {
        hint.className   = 'form-text text-muted';
        hint.textContent = '';
        input.classList.remove('is-invalid', 'is-valid');
        return;
    }

    const tieneArroba = val.includes('@');
    const partes      = val.split('@');
    const tieneDominio= partes.length === 2 && partes[1].includes('.');
    const regex       = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    const valido      = regex.test(val);

    if (!tieneArroba) {
        hint.className   = 'form-text text-danger';
        hint.textContent = '⚠ Falta el símbolo @';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else if (!tieneDominio) {
        hint.className   = 'form-text text-danger';
        hint.textContent = '⚠ Falta el dominio (ej: .com, .net)';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else if (!valido) {
        hint.className   = 'form-text text-danger';
        hint.textContent = '⚠ Formato inválido. Ej: nombre@correo.com';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else {
        hint.className   = 'form-text text-success';
        hint.textContent = '✓ Correo válido';
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }
}

function validarFormularioEmpresa() {
    const nombre = document.getElementById('nombre').value.trim();
    const nit = document.getElementById('nit').value.trim();
    const razonSocial = document.getElementById('razon_social').value.trim();
    const btnGuardar = document.getElementById('btnGuardar');

    // Validar que los campos obligatorios tengan contenido
    const formularioValido = nombre !== '' && nit !== '' && razonSocial !== '';

    btnGuardar.disabled = !formularioValido;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formEmpresa').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnGuardar');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
    document.getElementById('formNuevaCuenta').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnGuardarCuenta');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
});

function resetModal() {
    document.getElementById('tituloModal').innerText    = 'Nueva Empresa';
    var btnG = document.getElementById('btnGuardar');
    btnG.innerText = 'Registrar';
    btnG.disabled  = true;
    document.getElementById('methodEmpresa').value      = 'POST';
    document.getElementById('formEmpresa').action       = '{{ route("empresas.store") }}';
    fetch('{{ route("empresas.nuevo-token") }}')
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(d => {
            document.getElementById('idempotencyTokenEmpresa').value = d.token;
            document.getElementById('btnGuardar').disabled = false;
        })
        .catch(() => { document.getElementById('btnGuardar').disabled = false; });
    document.getElementById('formEmpresa').reset();
    document.getElementById('nit_contador').textContent = '0 / 15';
    document.getElementById('tel_pais').value           = 'Bolivia';
    actualizarPrefijoTelefonoEmp();

    // Deshabilitar botón al resetear
    document.getElementById('btnGuardar').disabled = true;

    // Limpiar feedback de email
    const emailHint = document.getElementById('email_hint');
    emailHint.textContent = '';
    emailHint.className = 'form-text';
    document.getElementById('email').classList.remove('is-valid', 'is-invalid');
}

function validarFormularioCuenta() {
    // Si la validación está desactivada temporalmente, salir
    if (window.validacionCuentaActiva === false) {
        return;
    }

    const nombreCuenta = document.getElementById('nc_nombre_cuenta');
    const banco = document.getElementById('nc_banco');
    const numeroCuenta = document.getElementById('nc_numero_cuenta');
    const moneda = document.getElementById('nc_moneda');
    const saldoInicial = document.getElementById('nc_saldo_inicial');
    const btnGuardar = document.getElementById('btnGuardarCuenta');

    // Verificar que todos los elementos existan
    if (!nombreCuenta || !banco || !numeroCuenta || !moneda || !saldoInicial || !btnGuardar) {
        return;
    }

    // Validar que todos los campos obligatorios tengan contenido
    const formularioValido = nombreCuenta.value.trim() !== '' &&
                            banco.value !== '' &&
                            numeroCuenta.value.trim() !== '' &&
                            moneda.value !== '' &&
                            saldoInicial.value !== '' &&
                            !isNaN(parseFloat(saldoInicial.value.replace(',', '.')));

    btnGuardar.disabled = !formularioValido;
}

function abrirModalCuenta(uuid, nombre) {
    // Deshabilitar validación temporalmente
    window.validacionCuentaActiva = false;

    document.getElementById('nc_empresa_nombre').textContent = nombre;
    document.getElementById('formNuevaCuenta').action = url_global + '/empresas/' + uuid + '/cuentas/store';

    // Limpiar campos específicos sin usar reset() para evitar eventos
    document.getElementById('nc_nombre_cuenta').value = '';
    document.getElementById('nc_banco').value = '';
    document.getElementById('nc_numero_cuenta').value = '';
    document.getElementById('nc_moneda').value = 'BOB';
    document.getElementById('nc_saldo_inicial_display').value = '';
    document.getElementById('nc_saldo_inicial').value = '0';
    document.getElementById('nc_contador').textContent = '0 / 20';

    // Forzar botón deshabilitado
    document.getElementById('btnGuardarCuenta').disabled = true;

    // Pedir token fresco al servidor
    fetch('{{ route("empresas.nuevo-token-cuenta") }}')
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(d => { document.getElementById('idempotencyTokenEmpresaCuenta').value = d.token; })
        .catch(() => {});

    // Reactivar validación y ejecutarla
    window.validacionCuentaActiva = true;
    validarFormularioCuenta();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevaCuenta')).show();
}

function editarEmpresa(uuid) {
    fetch(url_global + '/empresas/' + uuid + '/edit')
        .then(r => r.json())
        .then(e => {
            document.getElementById('tituloModal').innerText    = 'Editar Empresa';
            document.getElementById('btnGuardar').innerText     = 'Actualizar';
            document.getElementById('methodEmpresa').value      = 'PUT';
            document.getElementById('formEmpresa').action       = url_global + '/empresas/' + uuid;
            document.getElementById('nombre').value             = e.nombre ?? '';
            document.getElementById('nit').value                = e.nit ?? '';
            document.getElementById('nit_contador').textContent = (e.nit ?? '').length + ' / 15';
            document.getElementById('razon_social').value       = e.razon_social ?? '';
            document.getElementById('precio_referencia').value  = e.precio_referencia ?? '';
            document.getElementById('telefono').value           = e.telefono ?? '';
            document.getElementById('email').value              = e.email ?? '';
            document.getElementById('direccion').value          = e.direccion ?? '';
            document.getElementById('tel_pais').value           = 'Bolivia';
            actualizarPrefijoTelefonoEmp();

            // Validar email si existe
            if (e.email) {
                validarEmail(document.getElementById('email'));
            }

            // Validar formulario para habilitar botón
            validarFormularioEmpresa();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEmpresa')).show();
        });
}

function verInfoEmpresa(uuid) {
    fetch(url_global + '/empresas/' + uuid + '/edit')
        .then(r => r.json())
        .then(e => {
            // Datos generales
            document.getElementById('info_nombre').textContent = e.nombre || '-';
            document.getElementById('info_nit').textContent = e.nit || '-';
            document.getElementById('info_razon_social').textContent = e.razon_social || '-';

            // Contacto
            document.getElementById('info_telefono').textContent = e.telefono || '-';
            document.getElementById('info_email').textContent = e.email || '-';
            document.getElementById('info_direccion').textContent = e.direccion || '-';

            // Resumen financiero (desde data attributes del card)
            const empresaCard = document.querySelector(`[data-empresa-uuid="${uuid}"]`);
            const totalCuentas = empresaCard ? empresaCard.dataset.totalCuentas : '0';
            const saldoTotal = empresaCard ? empresaCard.dataset.saldoTotal : '0.00';

            document.getElementById('info_total_cuentas').textContent = totalCuentas;
            const saldoElement = document.getElementById('info_saldo_total');
            saldoElement.textContent = 'BOB ' + saldoTotal;
            saldoElement.className = 'fw-semibold fs-5 ' + (parseFloat(saldoTotal.replace(/,/g, '')) >= 0 ? 'text-success' : 'text-danger');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalInfoEmpresa')).show();
        });
}
function editarCuenta(uuid) {
    fetch(url_global + '/empresas/cuenta/' + uuid + '/edit')
        .then(r => r.json())
        .then(c => {
            document.getElementById('formEditarCuenta').action = url_global + '/empresas/cuenta/' + uuid;
            document.getElementById('ec_nombre_cuenta').value  = c.nombre_cuenta ?? '';
            document.getElementById('ec_banco').value          = c.banco_id ?? '';
            document.getElementById('ec_numero_cuenta').value  = c.numero_cuenta ?? '';
            document.getElementById('ec_saldo_inicial').value  = c.saldo_inicial ?? 0;
            document.getElementById('ec_saldo_inicial_display').value = formatearSaldo(c.saldo_inicial ?? 0);
            document.getElementById('ec_activo').value         = c.activo ? '1' : '0';
            document.getElementById('ec_descripcion').value    = c.descripcion ?? '';

            const bloqueado = !!c.tiene_movimientos;
            document.getElementById('ec_banco').disabled                = bloqueado;
            document.getElementById('ec_numero_cuenta').disabled        = bloqueado;
            document.getElementById('ec_saldo_inicial_display').disabled = bloqueado;
            document.getElementById('ec_aviso_bloqueo').classList.toggle('d-none', !bloqueado);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarCuenta')).show();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formEditarCuenta').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnGuardarEditarCuenta');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
});

// ── Cajero de saldo: formatea con "." de miles y "," de decimales (2 decimales) ──
function textoANumero(v) {
    return parseFloat(v.replace(/\./g, '').replace(',', '.')) || 0;
}
function formatearSaldo(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}
function attachCajeroSaldo(displayId, hiddenId, onChange) {
    var display = document.getElementById(displayId);
    var hidden  = document.getElementById(hiddenId);
    if (!display || !hidden) return;

    display.addEventListener('input', function() {
        var raw = this.value.replace(/[^0-9,]/g, '');
        var partes = raw.split(',');
        if (partes.length > 2) raw = partes[0] + ',' + partes.slice(1).join('');
        partes = raw.split(',');
        if (partes[1] !== undefined) partes[1] = partes[1].slice(0, 2);
        var entF  = (partes[0] || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var nuevo = partes[1] !== undefined ? entF + ',' + partes[1] : entF;
        var diff  = nuevo.length - this.value.length;
        var pos   = (this.selectionStart || 0) + diff;
        this.value = nuevo;
        try { this.setSelectionRange(pos, pos); } catch(_) {}
        hidden.value = textoANumero(nuevo) || 0;
        if (onChange) onChange();
    });

    display.addEventListener('blur', function() {
        var n = textoANumero(this.value);
        this.value  = n > 0 ? formatearSaldo(n) : '';
        hidden.value = n;
        if (onChange) onChange();
    });
}
attachCajeroSaldo('nc_saldo_inicial_display', 'nc_saldo_inicial', validarFormularioCuenta);
attachCajeroSaldo('ec_saldo_inicial_display', 'ec_saldo_inicial');
</script>
@endsection
