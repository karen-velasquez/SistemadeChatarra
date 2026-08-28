@extends('layouts.app')
@section('titulo', 'Bancos y Cuentas Bancarias')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>BANCOS Y CUENTAS BANCARIAS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Bancos</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                    data-steps='[
                        {"intro":"🏦 Este módulo centraliza los <b>bancos</b> y las <b>cuentas bancarias</b> de proveedores, conductores, empleados y clientes. Estas cuentas se usan al registrar pagos. Te muestro cómo funciona."},
                        {"element":"#colapseBancos","intro":"🏛️ Aquí ves los <b>bancos registrados</b>. Haz clic en el encabezado para desplegar u ocultar la lista. Cada banco muestra su país, código y cuántas cuentas tiene.","position":"bottom"},
                        {"element":"#tabla_cuentas","intro":"💳 La lista de todas las <b>cuentas bancarias</b>: su banco, titular, tipo, número, moneda y alias. Puedes buscar y filtrar.","position":"top"},
                        {"element":"#tabla_cuentas thead th:nth-child(3)","intro":"🏷️ La columna <b>Tipo</b> indica de quién es la cuenta: Empleado, Cliente, Proveedor o Propietario/Conductor.","position":"bottom"},
                        {"element":"#btnNuevoBanco","intro":"➕ Con <b>Nuevo Banco</b> registras una entidad bancaria. Tiene su propia guía ❓.","position":"left"},
                        {"element":"#btnNuevaCuenta","intro":"➕ Con <b>Nueva Cuenta</b> registras una cuenta bancaria y su titular. También tiene su guía ❓.","position":"left"}
                    ]'>
                <i class="bi bi-question-circle"></i>
            </button>
            @can('bancos.create')
            <button id="btnNuevoBanco" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalBanco" onclick="resetModalBanco()">
                <i class="bi bi-plus-lg"></i> Nuevo Banco
            </button>
            @endcan
            @can('bancos_cuentas.create')
            <button id="btnNuevaCuenta" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCuenta" onclick="resetModalCuenta()">
                <i class="bi bi-plus-lg"></i> Nueva Cuenta
            </button>
            @endcan
        </div>
    </div>
</div>

<section class="section">
    <div class="row">

        {{-- Descripción general del módulo --}}
        <div class="col-12 mb-2">
            <div class="alert alert-light border py-2 mb-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Este módulo centraliza los <strong>bancos</strong> utilizados en el sistema y las <strong>cuentas bancarias</strong>
                    de proveedores, operadores de transporte, empleados y clientes.
                    Las cuentas registradas aquí se usan al momento de registrar pagos masivos y movimientos de tesorería.
                    Cada cuenta puede tener un titular diferente al registrado (familiar, representante, gerente), con su propio CI/NIT y correo de notificación.
                </small>
            </div>
        </div>

        {{-- ===== BANCOS (desplegable) ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center gap-2" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#colapseBancos">
                        <i class="bi bi-bank text-primary"></i>
                        <h5 class="card-title mb-0">Bancos Registrados</h5>
                        <span class="badge bg-secondary ms-1">{{ $bancos->count() }}</span>
                        <i class="bi bi-chevron-down ms-auto text-muted" id="iconColapseBancos"></i>
                    </div>
                    <div class="collapse mt-3" id="colapseBancos">
                        @if($bancos->isEmpty())
                            <div class="alert alert-info py-2">
                                <small><i class="bi bi-info-circle"></i> No hay bancos registrados.</small>
                            </div>
                        @else
                        <div class="row g-2">
                            @foreach($bancos as $banco)
                            <div class="col-md-4">
                            <div class="border rounded px-3 py-2 d-flex align-items-center gap-3 h-100" style="background:#f8f9fa">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold" style="font-size:.9rem">{{ $banco->nombre }}</div>
                                    <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:.75rem;color:#6c757d">
                                        <span class="badge bg-secondary">{{ $banco->pais->valor ?? '-' }}</span>
                                        @if($banco->codigo_banco)
                                            <span>Cód: <strong>{{ $banco->codigo_banco }}</strong></span>
                                        @endif
                                        @if($banco->codigo_swift)
                                            <span>SWIFT: <strong>{{ $banco->codigo_swift }}</strong></span>
                                        @endif
                                        <span><i class="bi bi-credit-card"></i> {{ $banco->cuentas_count }} cuenta(s)</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    @can('bancos.edit')
                                    <button class="btn btn-sm btn-warning"
                                        title="Editar banco"
                                        onclick="editarBanco('{{ $banco->uuid }}', '{{ addslashes($banco->nombre) }}', '{{ $banco->pais_id }}', '{{ $banco->codigo_swift }}', '{{ $banco->codigo_banco }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endcan
                                    @can('bancos.destroy')
                                    @if($banco->cuentas_count > 0)
                                        <span data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="No se puede eliminar porque hay {{ $banco->cuentas_count }} cuenta(s) registrada(s) con este banco">
                                            <button class="btn btn-sm" disabled
                                                style="background:#9ca3af;border-color:#9ca3af;color:#fff;opacity:.6;cursor:not-allowed">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </span>
                                    @else
                                        <a href="{{ route('bancos.destroy', $banco->uuid) }}"
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar banco"
                                            onclick="return confirm('¿Eliminar el banco {{ $banco->nombre }}?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== CUENTAS BANCARIAS (ancho completo) ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h5 class="card-title mb-0">Cuentas Bancarias</h5>
                    </div>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-credit-card me-1"></i>
                        Listado de todas las cuentas bancarias registradas en el sistema. Puedes filtrar por banco, titular, tipo o moneda usando el buscador.
                        Las cuentas con <strong>titular diferente</strong> corresponden a familiares, representantes o gerentes que figuran en la cuenta bancaria en lugar del titular principal.
                    </p>
                    @php
                        $cuentas = \App\Models\CuentaBancaria::with(['banco', 'titular'])
                            ->whereNull('deleted_at')
                            ->orderBy('tipo_titular')
                            ->orderBy('alias')
                            ->get();
                    @endphp
                    @if($cuentas->isEmpty())
                        <div class="alert alert-info py-2">
                            <small><i class="bi bi-info-circle"></i> No hay cuentas registradas.</small>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table id="tabla_cuentas" class="table table-hover table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Banco</th>
                                    <th>Titular</th>
                                    <th>Tipo</th>
                                    <th>N° Cuenta</th>
                                    <th>CI / NIT</th>
                                    <th>Sucursal</th>
                                    <th>Moneda</th>
                                    <th>Alias</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cuentas as $cuenta)
                                @php
                                    $tipoColor = ['empleado' => 'secondary', 'cliente' => 'success', 'proveedor' => 'primary', 'operador' => 'info text-dark'];
                                    $tipoIcon  = ['empleado' => 'bi-person-badge', 'cliente' => 'bi-people', 'proveedor' => 'bi-box-seam', 'operador' => 'bi-person-vcard'];
                                    $tipoLabel = ['empleado' => 'Empleado', 'cliente' => 'Cliente', 'proveedor' => 'Proveedor', 'operador' => 'Propietario/Conductor'];
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $cuenta->banco->nombre }}</strong>
                                        <small class="text-muted d-block">{{ $cuenta->banco->pais->valor ?? '-' }}</small>
                                    </td>
                                    <td>{{ $cuenta->nombre_titular_display }}</td>
                                    <td>
                                        <span class="badge bg-{{ $tipoColor[$cuenta->tipo_titular] ?? 'secondary' }}">
                                            <i class="bi {{ $tipoIcon[$cuenta->tipo_titular] ?? 'bi-person' }}"></i>
                                            {{ $tipoLabel[$cuenta->tipo_titular] ?? ucfirst($cuenta->tipo_titular) }}
                                        </span>
                                    </td>
                                    <td><code>{{ $cuenta->numero_cuenta }}</code></td>
                                    <td><small>{{ $cuenta->nro_documento ?? '—' }}</small></td>
                                    <td><small>{{ $cuenta->sucursal_departamento ?? '—' }}</small></td>
                                    <td><span class="badge bg-light text-dark border">{{ $cuenta->moneda }}</span></td>
                                    <td>{{ $cuenta->alias ?? '—' }}</td>
                                                    <td class="text-center">
                                        @can('bancos_cuentas.edit')
                                        <button class="btn btn-sm btn-outline-secondary"
                                            onclick="editarCuenta(
                                                '{{ $cuenta->uuid }}',
                                                {{ $cuenta->banco_id }},
                                                '{{ $cuenta->tipo_titular }}',
                                                {{ $cuenta->titular_id ?? 'null' }},
                                                '{{ $cuenta->numero_cuenta }}',
                                                '{{ $cuenta->moneda }}',
                                                '{{ addslashes($cuenta->alias ?? '') }}',
                                                '{{ addslashes($cuenta->getRawOriginal('nombre_titular') ?? '') }}',
                                                '{{ addslashes($cuenta->apellido_paterno_titular ?? '') }}',
                                                '{{ addslashes($cuenta->apellido_materno_titular ?? '') }}',
                                                '{{ addslashes($cuenta->tipo_relacion ?? '') }}',
                                                '{{ $cuenta->nro_documento ?? '' }}',
                                                '{{ addslashes($cuenta->sucursal_departamento ?? '') }}',
                                                '{{ addslashes($cuenta->email_notificacion ?? '') }}'
                                            )" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @endcan
                                        @can('bancos_cuentas.destroy')
                                        <a href="{{ route('bancos.cuenta.destroy', $cuenta->uuid) }}"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Eliminar esta cuenta bancaria?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ===== MODAL BANCO ===== --}}
<div class="modal fade" id="modalBanco" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bank"></i> <span id="tituloBanco">Nuevo Banco</span></h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalBanco"
                            data-steps='[
                                {"intro":"📝 Registra una entidad bancaria. Los campos con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#banco_nombre","intro":"🏦 <b>Nombre del Banco</b> (ej: Banco Bisa).","position":"bottom"},
                                {"element":"#banco_pais","intro":"🌎 <b>País</b> del banco. Si es Bolivia, aparece el campo de código ASFI.","position":"bottom"},
                                {"element":"#banco_swift","intro":"🔤 <b>Código SWIFT</b> (opcional): para transferencias internacionales.","position":"bottom"},
                                {"element":"#btnBanco","intro":"💾 Pulsa <b>Registrar</b> para guardar el banco.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formBanco" method="POST" action="{{ route('bancos.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodBanco" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenBanco" value="{{ $tokenBanco ?? '' }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre del Banco <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="nombre" id="banco_nombre" required maxlength="150"
                                placeholder="Ej: Banco Bisa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">País <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="pais_id" id="banco_pais" required onchange="cambiarPaisBanco(this.value)">
                                <option value="">-- Seleccione --</option>
                                @foreach($paises as $pais)
                                    <option value="{{ $pais->id }}" data-valor="{{ $pais->valor }}">{{ $pais->valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código SWIFT</label>
                            <input type="text" class="form-control" name="codigo_swift" id="banco_swift" maxlength="20"
                                placeholder="Ej: BISABOLPX" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-6" id="sec_codigo_banco" style="display:none;">
                            <label class="form-label">Código ASFI</label>
                            <input type="text" class="form-control" name="codigo_banco" id="banco_codigo" maxlength="10"
                                placeholder="Ej: 1009"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)">
                            <small class="text-muted">Solo números.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnBanco">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL CUENTA BANCARIA ===== --}}
<div class="modal fade" id="modalCuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-credit-card"></i> <span id="tituloCuenta">Nueva Cuenta Bancaria</span></h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-light btn-sm btn-iniciar-tour"
                            data-tour-modal="#modalCuenta"
                            data-steps='[
                                {"intro":"📝 Registra una cuenta bancaria. Los campos se van habilitando a medida que completas los anteriores. Los marcados con <span style=\"color:#dc3545\">(*)</span> son obligatorios."},
                                {"element":"#tipo_titular","intro":"👥 <b>Tipo de Titular</b>: elige primero de quién es la cuenta (Empleado, Cliente, Proveedor o Propietario/Conductor). Esto habilita el resto de campos.","position":"bottom"},
                                {"element":"#sec_titular","intro":"🔎 <b>Titular</b>: busca y selecciona a la persona o empresa registrada dueña de la cuenta.","position":"bottom"},
                                {"element":"#sel_banco_cuenta","intro":"🏦 <b>Banco</b> de la cuenta.","position":"bottom"},
                                {"element":"#inp_numero_cuenta","intro":"🔢 <b>Número de cuenta</b>.","position":"bottom"},
                                {"element":"#inp_nro_documento","intro":"🪪 <b>CI / NIT</b> del titular de la cuenta.","position":"bottom"},
                                {"element":"#sel_moneda","intro":"💱 <b>Moneda</b> de la cuenta (BOB o USD).","position":"top"},
                                {"element":"#sec_toggle_titular","intro":"🔀 Activa <b>Titular diferente</b> si la cuenta figura a nombre de otra persona (familiar, representante). Pedirá su nombre y la relación.","position":"top"},
                                {"element":"#btnCuenta","intro":"💾 Pulsa <b>Registrar Cuenta</b> para guardar.","position":"top"}
                            ]'>
                        <i class="bi bi-question-circle"></i> Ayuda
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <form id="formCuenta" method="POST" action="{{ route('bancos.cuenta.store') }}">
                @csrf
                <input type="hidden" name="_method" id="methodCuenta" value="POST">
                <input type="hidden" name="_idempotency_token" id="idempotencyTokenCuenta" value="{{ $tokenCuenta ?? '' }}">
                <div class="modal-body">
                    <div class="row g-3">

                     <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Titular <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_titular" id="tipo_titular" required
                                onchange="cambiarTitular(this.value)">
                                <option value="">-- Seleccione --</option>
                                <option value="empleado">Empleado de la empresa</option>
                                <option value="cliente">Cliente</option>
                                <option value="proveedor">Proveedor</option>
                                <option value="operador">Propietario / Conductor</option>
                            </select>
                        </div>

                         {{-- Búsqueda del titular registrado --}}
                        <div class="col-12" id="sec_titular">
                            <label class="form-label fw-semibold" id="lbl_titular">Titular</label>
                            <input type="hidden" name="titular_id" id="titular_id_hidden">
                            <div class="position-relative">
                                <input type="text" class="form-control" id="buscar_titular" disabled
                                    placeholder="Primero seleccione el tipo de titular" autocomplete="off"
                                    oninput="filtrarTitulares(this.value)">
                                <div id="lista_titulares" class="list-group mt-1" style="max-height:180px;overflow-y:auto;display:none;position:absolute;z-index:1050;width:100%;"></div>
                            </div>
                            <small class="text-success" id="titular_seleccionado"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Banco <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="banco_id" id="sel_banco_cuenta" required disabled onchange="cambiarBancoCuenta(this)">
                                <option value="">-- Seleccione --</option>
                                @foreach($bancos as $b)
                                    <option value="{{ $b->id }}" data-pais="{{ $b->pais->valor ?? '' }}">{{ $b->nombre }} ({{ $b->pais->valor ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6" id="sec_sucursal">
                            <label class="form-label fw-semibold">Departamento / Sucursal <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="sucursal_departamento" id="sel_sucursal" disabled>
                                <option value="">-- Seleccione --</option>
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->descripcion }}" data-sigla="{{ $suc->valor }}">
                                        {{ $suc->descripcion }} ({{ $suc->valor }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Número de cuenta y alias --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Número de Cuenta <span class="text-danger">(*)</span></label>
                            <input type="text" class="form-control" name="numero_cuenta" id="inp_numero_cuenta" required disabled maxlength="100"
                                placeholder="Ej: 1234-5678-9012">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alias / Descripción</label>
                            <input type="text" class="form-control" name="alias" id="inp_alias" disabled maxlength="150"
                                placeholder="Ej: Cuenta principal USD">
                        </div>

                        {{-- CI / NIT --}}
                        <div class="col-md-6" id="sec_nro_documento">
                            <label class="form-label fw-semibold">CI / NIT del titular de la cuenta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nro_documento" id="inp_nro_documento" disabled
                                maxlength="20" placeholder="Ej: 12345678" required
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,20)">
                            <small class="text-muted">Solo números, máximo 20 dígitos.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Moneda <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="moneda" id="sel_moneda" required disabled>
                                <option value="BOB">BOB — Boliviano</option>
                                <option value="USD">USD — Dólar</option>
                            </select>
                        </div>

                        {{-- Toggle titular diferente --}}
                        <div class="col-12" id="sec_toggle_titular" style="display:none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="toggle_titular_diferente" onchange="toggleTitularDiferente(this.checked)">
                                <label class="form-check-label" for="toggle_titular_diferente">
                                    Titular diferente. Selecciona si la cuenta pertenece a otra persona (familiar, representante, etc.)
                                </label>
                            </div>
                        </div>

                        {{-- Nombre, ap. paterno, ap. materno del titular de la cuenta --}}
                        {{-- Siempre visibles cuando hay titular; readonly si jalado del sistema, editable si es familiar --}}
                        <div class="col-12" id="sec_datos_titular" style="display:none;">
                            <div class="p-2 border rounded bg-light">
                                <p class="small text-muted mb-2" id="lbl_datos_titular_hint">
                                    <i class="bi bi-info-circle"></i> Datos jalados automáticamente del registro.
                                </p>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Nombre(s) <span class="text-danger">(*)</span></label>
                                        <input type="text" class="form-control" name="nombre_titular" id="inp_nombre_titular"
                                            maxlength="100" oninput="this.value=this.value.toUpperCase()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">(*)</span></label>
                                        <input type="text" class="form-control" name="apellido_paterno_titular" id="inp_ap_paterno_titular"
                                            maxlength="100" oninput="this.value=this.value.toUpperCase()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Apellido Materno <span class="text-danger">(*)</span></label>
                                        <input type="text" class="form-control" name="apellido_materno_titular" id="inp_ap_materno_titular"
                                            maxlength="100" oninput="this.value=this.value.toUpperCase()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email de notificación (opcional) --}}
                        <div class="col-md-6">
                            <label class="form-label">Email de Notificación <small class="text-muted">(opcional)</small></label>
                            <input type="email" class="form-control" name="email_notificacion" id="inp_email_notificacion" disabled
                                maxlength="150" placeholder="ejemplo@correo.com">
                        </div>

                        {{-- Relación: solo si el toggle (titular diferente) está activo --}}
                        <div class="col-md-6" id="sec_tipo_relacion" style="display:none;">
                            <label class="form-label fw-semibold">Relación con el titular principal <span class="text-danger">(*)</span></label>
                            <select class="form-select" name="tipo_relacion" id="sel_tipo_relacion">
                                <option value="">-- Seleccione --</option>
                            </select>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnCuenta"><i class="bi bi-save"></i> Registrar Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/tablas/basica.js') }}" type="text/javascript"></script>
<script>
$(document).ready(function() {
    // Activar tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });

    $('#tabla_cuentas').DataTable({
        language: {
            processing:  "Procesando...",
            lengthMenu:  'Mostrar <select><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="-1">Todos</option></select> registros',
            paginate:    { sFirst: "Primero", sLast: "Último", previous: "Anterior", next: "Siguiente" },
            info:        "Página _PAGE_ de _PAGES_ — _TOTAL_ cuentas",
            search:      "Buscar:",
            emptyTable:  "No hay cuentas registradas.",
            infoEmpty:   "",
        },
        orderCellsTop: true,
        ordering:      false,
        pageLength:    15,
        lengthMenu:    [10, 15, 25, 50, -1],
        columnDefs:    [{ orderable: false, targets: -1 }],
    });
});

document.getElementById('colapseBancos').addEventListener('show.bs.collapse', function() {
    document.getElementById('iconColapseBancos').className = 'bi bi-chevron-up ms-auto text-muted';
});
document.getElementById('colapseBancos').addEventListener('hide.bs.collapse', function() {
    document.getElementById('iconColapseBancos').className = 'bi bi-chevron-down ms-auto text-muted';
});
// Arrays de titulares con nombre, apellido_paterno, apellido_materno cuando aplica
@php
$proveedoresJs = $proveedores->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'ap' => '', 'am' => '', 'ci' => '']);
$operadoresJs  = $operadores->map(fn($o) => ['id' => $o->id, 'nombre' => $o->nombre, 'ap' => $o->apellido_paterno, 'am' => $o->apellido_materno, 'ci' => $o->ci ?? '']);
$empleadosJs   = $empleados->map(fn($e) => [
    'id'    => $e->id,
    'nombre'=> $e->nombre,
    'ap'    => $e->apellido_paterno,
    'am'    => $e->apellido_materno,
    'ci'    => $e->ci ?? '',
    'label' => $e->apellido_paterno . ' ' . $e->apellido_materno . ', ' . $e->nombre . ($e->cargo ? ' (' . $e->cargo->valor . ')' : ''),
]);
$clientesJs    = $clientes->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'ap' => '', 'am' => '', 'ci' => '']);
@endphp
const proveedores = @json($proveedoresJs);
const operadores  = @json($operadoresJs);
const empleados   = @json($empleadosJs);
const clientes    = @json($clientesJs);

let listaTitularActual = [];
let _titularTieneApellidos = false; // true para operador/empleado

// Relaciones desde la base de datos
const relacionesPorTipo = {
    'empleado':  @json($relacionesEmpleado->pluck('valor')),
    'proveedor': @json($relacionesProveedor->pluck('valor')),
    'cliente':   @json($relacionesCliente->pluck('valor')),
    'operador':  @json($relacionesOperador->pluck('valor')),
};

function cambiarTitular(tipo) {
    // Habilitar todos los campos cuando se selecciona un tipo de titular
    const campos = [
        'buscar_titular', 'sel_banco_cuenta', 'sel_sucursal',
        'inp_numero_cuenta', 'inp_alias', 'inp_nro_documento',
        'sel_moneda', 'inp_email_notificacion'
    ];

    if (tipo === '') {
        // Deshabilitar todos los campos
        campos.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) elem.disabled = true;
        });
        document.getElementById('buscar_titular').placeholder = 'Primero seleccione el tipo de titular';
        document.getElementById('sec_datos_titular').style.display = 'none';
        document.getElementById('sec_toggle_titular').style.display = 'none';
        document.getElementById('inp_nro_documento').value = '';
        limpiarTitular();
        limpiarDatosTitular();
        actualizarRelaciones('');
        return;
    }

    // Habilitar todos los campos
    campos.forEach(id => {
        const elem = document.getElementById(id);
        if (elem) elem.disabled = false;
    });
    document.getElementById('buscar_titular').placeholder = 'Buscar por nombre...';

    const listas = {
        'empleado':  { lista: empleados.map(e => ({...e, label: e.label})),   label: 'Empleado de la empresa', conApellidos: true },
        'cliente':   { lista: clientes,    label: 'Cliente',                  conApellidos: false },
        'proveedor': { lista: proveedores, label: 'Proveedor',                conApellidos: false },
        'operador':  { lista: operadores,  label: 'Propietario / Conductor',  conApellidos: true },
    };

    const cfg = listas[tipo] ?? { lista: [], label: 'Titular', conApellidos: false };
    _titularTieneApellidos = cfg.conApellidos;
    document.getElementById('lbl_titular').textContent = cfg.label;
    listaTitularActual = cfg.lista;
    document.getElementById('sec_toggle_titular').style.display = 'block';
    limpiarTitular();
    limpiarDatosTitular();
    actualizarRelaciones(tipo);
}

function actualizarRelaciones(tipo) {
    const sel = document.getElementById('sel_tipo_relacion');
    sel.innerHTML = '<option value="">-- Seleccione --</option>';
    (relacionesPorTipo[tipo] || []).forEach(r => {
        const opt = document.createElement('option');
        opt.value = r; opt.textContent = r;
        sel.appendChild(opt);
    });

    document.getElementById('toggle_titular_diferente').checked = false;
    toggleTitularDiferente(false);
    document.getElementById('sec_toggle_titular').style.display =
        (relacionesPorTipo[tipo] || []).length ? 'block' : 'none';
}

function toggleTitularDiferente(activo) {
    const secRel  = document.getElementById('sec_tipo_relacion');
    const secDat  = document.getElementById('sec_datos_titular');
    const hint    = document.getElementById('lbl_datos_titular_hint');

    if (activo) {
        secRel.style.display  = 'block';
        secDat.style.display  = 'block';
        hint.innerHTML = '<i class="bi bi-pencil"></i> Ingrese los datos de la persona que figura en la cuenta.';
        // Limpiar y habilitar para edición manual
        setDatosTitularEditable(true);
        document.getElementById('inp_nombre_titular').value    = '';
        document.getElementById('inp_ap_paterno_titular').value = '';
        document.getElementById('inp_ap_materno_titular').value = '';
    } else {
        secRel.style.display  = 'none';
        document.getElementById('sel_tipo_relacion').value = '';
        // Si hay titular seleccionado, restaurar sus datos en readonly
        const id = document.getElementById('titular_id_hidden').value;
        if (id) {
            const item = listaTitularActual.find(i => String(i.id) === String(id));
            if (item) { rellenarDatosTitular(item); return; }
        }
        secDat.style.display = 'none';
        limpiarDatosTitular();
    }
}

function limpiarTitular() {
    document.getElementById('titular_id_hidden').value        = '';
    document.getElementById('buscar_titular').value           = '';
    document.getElementById('titular_seleccionado').textContent = '';
    document.getElementById('lista_titulares').style.display  = 'none';
}

function limpiarDatosTitular() {
    document.getElementById('inp_nombre_titular').value     = '';
    document.getElementById('inp_ap_paterno_titular').value = '';
    document.getElementById('inp_ap_materno_titular').value = '';
    document.getElementById('sec_datos_titular').style.display = 'none';
}

function setDatosTitularEditable(editable) {
    ['inp_nombre_titular', 'inp_ap_paterno_titular', 'inp_ap_materno_titular'].forEach(id => {
        const el = document.getElementById(id);
        if (editable) {
            el.removeAttribute('readonly');
            el.classList.remove('bg-light');
        } else {
            el.setAttribute('readonly', true);
            el.classList.add('bg-light');
        }
    });
}

function rellenarDatosTitular(item) {
    const secDat = document.getElementById('sec_datos_titular');
    const hint   = document.getElementById('lbl_datos_titular_hint');

    if (_titularTieneApellidos) {
        document.getElementById('inp_nombre_titular').value      = item.nombre ?? '';
        document.getElementById('inp_ap_paterno_titular').value  = item.ap ?? '';
        document.getElementById('inp_ap_materno_titular').value  = item.am ?? '';
        secDat.style.display = 'block';
        hint.innerHTML = '<i class="bi bi-check-circle text-success"></i> Datos del titular de la cuenta jalados automáticamente. Activa "titular diferente" para editarlos.';
        setDatosTitularEditable(false);
    } else {
        // Proveedor/cliente: solo nombre, sin apellidos separadoss
        document.getElementById('inp_nombre_titular').value      = item.nombre ?? '';
        document.getElementById('inp_ap_paterno_titular').value  = '';
        document.getElementById('inp_ap_materno_titular').value  = '';
        secDat.style.display = 'block';
        hint.innerHTML = '<i class="bi bi-check-circle text-success"></i> Nombre del titular jalado. Puedes completar los apellidos si aplica.';
        setDatosTitularEditable(true);
    }
}

function filtrarTitulares(q) {
    const lista = document.getElementById('lista_titulares');
    document.getElementById('titular_id_hidden').value        = '';
    document.getElementById('titular_seleccionado').textContent = '';

    if (!q.trim()) { lista.style.display = 'none'; return; }

    const termino    = q.toLowerCase();
    const resultados = listaTitularActual.filter(i =>
        (i.label || i.nombre).toLowerCase().includes(termino)
    );

    lista.innerHTML = '';
    if (resultados.length === 0) {
        lista.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
    } else {
        resultados.forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1 small';
            btn.textContent = item.label || item.nombre;
            btn.onclick = () => seleccionarTitular(item);
            lista.appendChild(btn);
        });
    }
    lista.style.display = 'block';
}

function seleccionarTitular(item) {
    document.getElementById('titular_id_hidden').value        = item.id;
    document.getElementById('buscar_titular').value           = item.label || item.nombre;
    document.getElementById('titular_seleccionado').textContent = '✓ Seleccionado';
    document.getElementById('lista_titulares').style.display  = 'none';

    // Autocompletar CI si es empleado u operador
    if (_titularTieneApellidos && item.ci) {
        document.getElementById('inp_nro_documento').value = item.ci;
    }

    // Si toggle "titular diferente" no está activo, rellenar datos automáticamente
    if (!document.getElementById('toggle_titular_diferente').checked) {
        rellenarDatosTitular(item);
    }
}

document.addEventListener('click', function(e) {
    const lista = document.getElementById('lista_titulares');
    if (lista && !lista.contains(e.target) && e.target.id !== 'buscar_titular') {
        lista.style.display = 'none';
    }
});

function cambiarPaisBanco(paisId) {
    const sec = document.getElementById('sec_codigo_banco');
    const selectPais = document.getElementById('banco_pais');
    const selectedOption = selectPais.options[selectPais.selectedIndex];
    const paisValor = selectedOption ? selectedOption.getAttribute('data-valor') : '';

    if (paisValor === 'BOLIVIA') {
        sec.style.display = 'block';
    } else {
        sec.style.display = 'none';
        document.getElementById('banco_codigo').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formBanco').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnBanco');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
    document.getElementById('formCuenta').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnCuenta');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    });
});

function pedirTokenFresco(rutaToken, campoId, onListo) {
    fetch(rutaToken)
        .then(r => { if (!r.ok) throw new Error('error'); return r.json(); })
        .then(d => {
            document.getElementById(campoId).value = d.token;
            if (onListo) onListo();
        })
        .catch(() => {
            // Si el fetch falla, no bloqueamos — el token del HTML inicial sigue en el campo
        });
}

function resetModalBanco() {
    document.getElementById('tituloBanco').textContent = 'Nuevo Banco';
    var btnB = document.getElementById('btnBanco');
    btnB.textContent = 'Registrar';
    btnB.disabled    = true;
    document.getElementById('methodBanco').value       = 'POST';
    document.getElementById('formBanco').action        = '{{ route("bancos.store") }}';
    document.getElementById('banco_nombre').value      = '';
    document.getElementById('banco_pais').value        = '';
    document.getElementById('banco_swift').value       = '';
    document.getElementById('banco_codigo').value      = '';
    document.getElementById('sec_codigo_banco').style.display = 'none';
    pedirTokenFresco('{{ route("bancos.nuevo-token-banco") }}', 'idempotencyTokenBanco', function() {
        document.getElementById('btnBanco').disabled = false;
        document.getElementById('btnBanco').textContent = 'Registrar';
    });
}

function resetModalCuenta() {
    document.getElementById('tituloCuenta').textContent  = 'Nueva Cuenta Bancaria';
    var btnC = document.getElementById('btnCuenta');
    btnC.innerHTML = '<i class="bi bi-save"></i> Registrar Cuenta';
    btnC.disabled  = true;
    document.getElementById('methodCuenta').value        = 'POST';
    document.getElementById('formCuenta').action         = '{{ route("bancos.cuenta.store") }}';
    pedirTokenFresco('{{ route("bancos.nuevo-token-cuenta") }}', 'idempotencyTokenCuenta', function() {
        var b = document.getElementById('btnCuenta');
        b.disabled = false;
        b.innerHTML = '<i class="bi bi-save"></i> Registrar Cuenta';
    });
    document.getElementById('tipo_titular').value        = '';

    // Deshabilitar todos los campos
    const campos = [
        'buscar_titular', 'sel_banco_cuenta', 'sel_sucursal',
        'inp_numero_cuenta', 'inp_alias', 'inp_nro_documento',
        'sel_moneda', 'inp_email_notificacion'
    ];
    campos.forEach(id => {
        const elem = document.getElementById(id);
        if (elem) elem.disabled = true;
    });

    document.getElementById('buscar_titular').placeholder = 'Primero seleccione el tipo de titular';
    document.getElementById('sec_toggle_titular').style.display   = 'none';
    document.getElementById('sec_datos_titular').style.display    = 'none';
    document.getElementById('sec_tipo_relacion').style.display    = 'none';
    document.getElementById('sel_sucursal').value                 = '';
    document.getElementById('toggle_titular_diferente').checked   = false;
    document.getElementById('inp_nro_documento').value            = '';
    document.getElementById('inp_email_notificacion').value       = '';
    limpiarTitular();
    limpiarDatosTitular();
    document.querySelectorAll('#formCuenta select:not(#tipo_titular):not(#sel_tipo_relacion):not(#sel_sucursal)').forEach(s => s.selectedIndex = 0);
    document.querySelectorAll('#formCuenta input[type="text"]').forEach(i => { if (i.id !== 'buscar_titular') i.value = ''; });
}

function cambiarBancoCuenta(sel) {
    const opt = sel.options[sel.selectedIndex];
    const pais = opt ? opt.dataset.pais : '';
    const selSucursal = document.getElementById('sel_sucursal');
    if (pais.toUpperCase() === 'BOLIVIA') {
        selSucursal.disabled = false;
        selSucursal.required = true;
    } else {
        selSucursal.disabled = true;
        selSucursal.required = false;
        selSucursal.value = '';
    }
}

function editarCuenta(uuid, bancoId, tipoTitular, titularId, numeroCuenta, moneda, alias, nombreTitular, apPaterno, apMaterno, tipoRelacion, nroDocumento, sucursal, emailNotificacion) {
    document.getElementById('tituloCuenta').textContent = 'Editar Cuenta Bancaria';
    document.getElementById('btnCuenta').innerHTML      = '<i class="bi bi-save"></i> Actualizar Cuenta';
    document.getElementById('methodCuenta').value       = 'PUT';
    document.getElementById('formCuenta').action        = url_global + '/bancos/cuentas/' + uuid;

    // Banco y moneda
    const selBanco = document.getElementById('sel_banco_cuenta');
    selBanco.value = bancoId;
    cambiarBancoCuenta(selBanco);
    document.querySelector('#formCuenta select[name="moneda"]').value       = moneda;
    document.querySelector('#formCuenta input[name="numero_cuenta"]').value = numeroCuenta;
    document.querySelector('#formCuenta input[name="alias"]').value         = alias;

    // Tipo titular → pobla lista y relaciones
    const selTipo = document.getElementById('tipo_titular');
    selTipo.value = tipoTitular;
    cambiarTitular(tipoTitular);

    // Pre-seleccionar titular en el buscador
    if (titularId) {
        const item = listaTitularActual.find(i => String(i.id) === String(titularId));
        document.getElementById('titular_id_hidden').value       = titularId;
        document.getElementById('buscar_titular').value          = item ? (item.label || item.nombre) : '';
        document.getElementById('titular_seleccionado').textContent = '✓ Seleccionado';
    }

    // CI / NIT
    document.getElementById('inp_nro_documento').value = nroDocumento || '';

    // Sucursal
    if (sucursal) document.getElementById('sel_sucursal').value = sucursal;

    // Email notificación
    document.getElementById('inp_email_notificacion').value = emailNotificacion || '';

    // Datos del titular en la cuenta
    if (tipoRelacion) {
        // Es titular diferente (familiar/representante)
        document.getElementById('toggle_titular_diferente').checked = true;
        toggleTitularDiferente(true);
        document.getElementById('inp_nombre_titular').value      = nombreTitular || '';
        document.getElementById('inp_ap_paterno_titular').value  = apPaterno || '';
        document.getElementById('inp_ap_materno_titular').value  = apMaterno || '';
        document.getElementById('sel_tipo_relacion').value       = tipoRelacion;
    } else if (nombreTitular) {
        // Datos del titular del sistema (readonly)
        document.getElementById('sec_datos_titular').style.display = 'block';
        document.getElementById('inp_nombre_titular').value      = nombreTitular;
        document.getElementById('inp_ap_paterno_titular').value  = apPaterno || '';
        document.getElementById('inp_ap_materno_titular').value  = apMaterno || '';
        setDatosTitularEditable(!_titularTieneApellidos);
        document.getElementById('lbl_datos_titular_hint').innerHTML =
            '<i class="bi bi-check-circle text-success"></i> Datos jalados automáticamente. Activa "titular diferente" para editarlos.';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCuenta')).show();
}

function editarBanco(uuid, nombre, pais, swift, codigo) {
    document.getElementById('tituloBanco').textContent = 'Editar Banco';
    document.getElementById('btnBanco').textContent    = 'Actualizar';
    document.getElementById('methodBanco').value       = 'PUT';
    document.getElementById('formBanco').action        = url_global + '/bancos/' + uuid;
    document.getElementById('banco_nombre').value      = nombre;
    document.getElementById('banco_pais').value        = pais;
    document.getElementById('banco_swift').value       = swift ?? '';
    document.getElementById('banco_codigo').value      = codigo ?? '';
    cambiarPaisBanco(pais);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBanco')).show();
}
</script>
@endsection
