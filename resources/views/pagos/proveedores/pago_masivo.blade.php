@extends('layouts.app')
@section('titulo', 'Pago Masivo a Proveedores')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>PAGO MASIVO A PROVEEDORES</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pagos.proveedores.index') }}">Pagos Proveedores</a></li>
                    <li class="breadcrumb-item active">Pago Masivo</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                @if($contratos->isEmpty())
                data-steps='[
                    {"intro":"💸 El <b>Pago Masivo</b> te permite pagar a varios proveedores y contratos a la vez, en un solo proceso.<br><br>📭 Por ahora <b>no hay contratos con saldo pendiente</b>, así que no hay nada que pagar. Cuando existan saldos por pagar, aparecerán aquí."}
                ]'
                @else
                data-steps='[
                    {"intro":"💸 El <b>Pago Masivo</b> te permite pagar varios contratos de golpe. Se hace en <b>2 pasos</b>: primero eliges qué pagar, y luego a qué cuenta llega cada pago. Te guío por el Paso 1."},
                    {"element":"#cuenta_origen_id","intro":"🏦 <b>Cuenta de origen</b>: de qué cuenta de la empresa saldrá el dinero. Al elegirla se muestra su <b>saldo disponible</b> y te avisa si no alcanza.","position":"bottom"},
                    {"element":"#fecha_pago","intro":"📅 <b>Fecha de pago</b> que tendrán todos los pagos de este lote.","position":"bottom"},
                    {"element":"#metodo_pago","intro":"💳 <b>Método de pago</b> (transferencia o QR) para todo el lote.","position":"bottom"},
                    {"element":"#zona-proveedores","intro":"📦 Aquí están los contratos <b>agrupados por proveedor</b>. Marca la casilla del proveedor para seleccionar todos sus contratos, o marca contratos sueltos.","position":"top"},
                    {"element":"#col-pct","intro":"🔢 Para cada contrato marcado, escribe el <b>% del saldo</b> que vas a pagar. El sistema calcula el <b>monto</b> automáticamente en la columna de al lado.","position":"bottom"},
                    {"element":"#zona-totales","intro":"🧮 Abajo ves cuántos contratos seleccionaste y el <b>total a pagar</b>.","position":"top"},
                    {"element":"#btn_paso2","intro":"➡️ Cuando todo esté listo, el botón <b>Siguiente</b> te lleva al Paso 2 para asignar las cuentas destino y confirmar.","position":"bottom"}
                ]'
                @endif>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
<div class="row">
<div class="col-12">

{{-- ============================================================ DATOS GLOBALES (fijo entre Paso 1 y Paso 2) ============================================================ --}}
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="card-title mb-0 text-muted"><i class="bi bi-gear me-1"></i>Datos del pago</h6>
      <div>
        <button type="button" class="btn btn-primary" id="btn_paso2" onclick="irAPaso2()" disabled>
          Siguiente: Asignar cuentas destino <i class="bi bi-arrow-right ms-1"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" id="btn_paso2_volver" onclick="volverPaso1()" style="display:none">
          <i class="bi bi-arrow-left me-1"></i> Volver al Paso 1
        </button>
      </div>
    </div>
    {{-- Datos globales del pago --}}
    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label fw-semibold"><i class="bi bi-building"></i> Cuenta de origen (empresa) <span class="text-danger">*</span></label>
        <select class="form-select" id="cuenta_origen_id" name="cuenta_origen_id_tmp" required onchange="onCuentaOrigenChange()">
          <option value="">— Seleccione cuenta —</option>
          @foreach($empresas as $emp)
            @foreach($emp->cuentas as $cta)
            <option value="{{ $cta->id }}" data-moneda="{{ $cta->moneda }}" data-saldo="{{ $cta->saldo_actual }}">
              {{ $emp->nombre }} — {{ $cta->alias ?? $cta->numero_cuenta }} [{{ $cta->moneda }}] — Saldo: {{ number_format($cta->saldo_actual, 2, ',', '.') }}
            </option>
            @endforeach
          @endforeach
        </select>
        <div id="saldo_cuenta_info" class="mt-1" style="display:none">
          <small>Saldo disponible: <strong id="lbl_saldo_cuenta" class="text-success"></strong></small>
          <div id="aviso_saldo_insuficiente" class="alert alert-danger py-1 mt-1 small" style="display:none">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Saldo insuficiente. El total a pagar (<strong id="lbl_total_insuf"></strong>) supera el saldo disponible.
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold"><i class="bi bi-calendar3"></i> Fecha de pago <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="fecha_pago" value="{{ date('Y-m-d') }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold"><i class="bi bi-credit-card"></i> Método de pago <span class="text-danger">*</span></label>
        <select class="form-select" id="metodo_pago" required>
          <option value="">— Seleccione —</option>
          <option value="transferencia">Transferencia</option>
          <option value="qr">QR</option>
        </select>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ PASO 1 ============================================================ --}}
<div id="paso1_contenido">
<div class="card">
  <div class="card-body">
    <h5 class="card-title">Paso 1 — Seleccionar contratos y porcentaje a pagar</h5>
    <p class="text-muted small mb-3">
      <i class="bi bi-info-circle me-1"></i>
      Seleccione los contratos y defina qué porcentaje del saldo pendiente desea pagar a cada proveedor.
    </p>

    @if($contratos->isEmpty())
      <div class="alert alert-info py-2">
        <i class="bi bi-info-circle"></i> No hay contratos con saldo pendiente para proveedores.
      </div>
    @else

    {{-- Contratos agrupados por proveedor --}}
    @php
      $porProveedor = $contratos->groupBy(fn($c) => $c->proveedor_id);
    @endphp

    <div id="zona-proveedores">
    @foreach($porProveedor as $provId => $ctrs)
    @php $prov = $ctrs->first()->proveedor; @endphp
    <div class="card border mb-3">
      <div class="card-header py-2 bg-light d-flex align-items-center gap-2">
        <input type="checkbox" class="form-check-input chk_proveedor" id="chk_prov_{{ $provId }}"
               onchange="toggleProveedor({{ $provId }}, this.checked)">
        <label class="form-check-label fw-bold mb-0" for="chk_prov_{{ $provId }}">
          <i class="bi bi-box-seam me-1"></i>{{ $prov->nombre ?? 'Proveedor #'.$provId }}
        </label>
        <span class="ms-auto text-muted small">{{ $ctrs->count() }} contrato(s)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px"></th>
              <th>Contrato</th>
              <th class="text-end">Monto Total</th>
              <th class="text-end">Total Pagado</th>
              <th class="text-end">Saldo Pendiente</th>
              <th style="width:130px" @if($loop->first) id="col-pct" @endif>% a pagar</th>
              <th class="text-end" style="width:150px">Monto a pagar</th>
              <th style="width:40px"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($ctrs as $c)
            @php
              $saldo     = $c->saldo_pendiente_proveedor;
              $moneda    = $c->moneda ?? 'BOB';
              $totalPag  = $c->total_pagado_proveedor;
              $camiones  = $c->contratoCamiones;
            @endphp
            <tr data-proveedor-id="{{ $provId }}" data-contrato-id="{{ $c->id }}" data-saldo="{{ $saldo }}" data-moneda="{{ $moneda }}">
              <td class="text-center">
                <input type="checkbox" class="form-check-input chk_contrato" data-proveedor="{{ $provId }}"
                       id="chk_c_{{ $c->id }}" onchange="onCheckContrato({{ $c->id }}, this.checked)">
              </td>
              <td>
                <span class="fw-semibold small">{{ $c->numero_contrato ?? '#'.$c->id }}</span>
                <small class="text-muted d-block">{{ $c->fecha_inicio?->format('d/m/Y') }} — {{ $c->fecha_fin?->format('d/m/Y') ?? 'sin fin' }}</small>
              </td>
              <td class="text-end">
                <span class="small">{{ $moneda }} {{ number_format($c->monto_total, 2, ',', '.') }}</span>
              </td>
              <td class="text-end">
                <span class="small text-danger">{{ $moneda }} {{ number_format($totalPag, 2, ',', '.') }}</span>
              </td>
              <td class="text-end">
                <span class="fw-semibold text-success">{{ $moneda }} {{ number_format($saldo, 2, ',', '.') }}</span>
              </td>
              <td>
                <div class="input-group input-group-sm">
                  <input type="number" class="form-control pct_input" id="pct_{{ $c->id }}"
                         min="0.01" step="0.01" placeholder="0"
                         disabled
                         oninput="clampPct(this); calcularMonto({{ $c->id }})">
                  <span class="input-group-text">%</span>
                </div>
                <div class="text-danger small mt-1" id="err_pct_{{ $c->id }}" style="display:none">
                  <i class="bi bi-exclamation-circle me-1"></i>Máximo 100%
                </div>
              </td>
              <td class="text-end">
                <div class="input-group input-group-sm justify-content-end">
                  <span class="input-group-text text-muted" style="font-size:.75rem">{{ $moneda }}</span>
                  <input type="number" class="form-control monto_input" id="monto_{{ $c->id }}"
                         min="0.01" step="0.01" placeholder="0.00"
                         disabled
                         oninput="calcularPct({{ $c->id }})">
                </div>
              </td>
              <td class="text-center">
                @if($camiones->isNotEmpty())
                <button type="button"
                        class="btn {{ $c->envios_cerrados ? 'btn-outline-success' : 'btn-outline-info' }} btn-sm"
                        title="Ver camiones del contrato"
                        onclick="verCamiones({{ $c->id }})">
                  <i class="bi bi-truck"></i>
                  <span class="badge {{ $c->envios_cerrados ? 'bg-success' : 'bg-info text-dark' }} ms-1" style="font-size:.65rem">{{ $camiones->count() }}</span>
                </button>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endforeach
    </div>{{-- /zona-proveedores --}}

    <div class="d-flex justify-content-between align-items-center mt-3" id="zona-totales">
      <div class="text-muted small">
        Contratos seleccionados: <strong id="lbl_seleccionados">0</strong>
        &nbsp;|&nbsp; Total a pagar: <strong id="lbl_total">—</strong>
      </div>
    </div>
    @endif

  </div>
</div>
</div>{{-- /paso1 --}}

{{-- ============================================================ PASO 2 ============================================================ --}}
<div id="paso2_contenido" style="display:none">
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <h5 class="card-title">Paso 2 — ¿A qué cuenta llega el dinero de cada contrato?</h5>
      <button type="button"
              class="btn btn-outline-primary btn-sm btn-iniciar-tour"
              data-steps='[
                  {"intro":"📍 Estás en el <b>Paso 2</b> del pago masivo. Aquí defines a qué <b>cuenta bancaria</b> llega el dinero de cada contrato que seleccionaste antes. Te explico."},
                  {"element":"#paso2_lista","intro":"📦 Los contratos aparecen agrupados por proveedor. Para cada uno se listan sus <b>cuentas bancarias</b>: haz clic en la fila de la cuenta a la que quieres pagar. Aparecerá una etiqueta verde <b>Asignada</b>.","position":"top"},
                  {"element":"#btn_paso2_volver","intro":"↩️ Si te equivocaste o quieres cambiar montos, con <b>Volver al Paso 1</b> regresas sin perder lo seleccionado.","position":"bottom"},
                  {"element":"#btn_confirmar","intro":"✅ Cuando <b>todos</b> los contratos tengan una cuenta asignada, este botón se activa. Te mostrará un resumen final (que puedes exportar a Excel) antes de registrar los pagos.","position":"left"}
              ]'>
          <i class="bi bi-question-circle"></i>
      </button>
    </div>
    <p class="text-muted small mb-3">
      <i class="bi bi-info-circle me-1"></i>
      Seleccione una cuenta bancaria destino para cada contrato.
    </p>

    <div id="paso2_lista"></div>

    <div class="d-flex justify-content-end mt-4">
      <button type="button" class="btn btn-success" id="btn_confirmar" onclick="abrirConfirmacion()" disabled>
        <i class="bi bi-check-circle me-1"></i> Revisar y confirmar
      </button>
    </div>
  </div>
</div>
</div>{{-- /paso2 --}}

{{-- FORM oculto para envío --}}
<form id="form_pago_masivo" method="POST" action="{{ route('pagos.proveedores.pago_masivo.store') }}" style="display:none">
  @csrf
  <input type="hidden" name="cuenta_origen_id" id="h_cuenta_origen_id">
  <input type="hidden" name="fecha_pago"        id="h_fecha_pago">
  <input type="hidden" name="metodo_pago"        id="h_metodo_pago">
  <div id="h_contratos_dinamicos"></div>
</form>

{{-- Modal de confirmación --}}
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Confirmar pago masivo a proveedores</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-2">Verifique los datos antes de confirmar. Este proceso no se puede deshacer fácilmente.</p>
        <div class="d-flex gap-2 mb-3">
          <button class="btn btn-outline-secondary btn-sm" onclick="abrirEnVentana()">
            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir en nueva ventana
          </button>
          <button class="btn btn-outline-success btn-sm" onclick="descargarExcel()">
            <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="tabla_confirmacion" style="font-size:.8rem">
            <thead>
              <tr style="background:#1d7a3a;color:#fff;white-space:nowrap">
                <th>NRO DE ORDEN</th>
                <th>CODIGO DE CLIENTE</th>
                <th>NRO DE CUENTA</th>
                <th>NOMBRE DEL CLIENTE</th>
                <th>DOC DE IDENTIDAD</th>
                <th>IMPORTE</th>
                <th>FECHA DE PAGO</th>
                <th>FORMA DE PAGO</th>
                <th>MONEDA DESTINO</th>
                <th>ENTIDAD DESTINO</th>
                <th>SUCURSAL DESTINO</th>
                <th>GLOSA</th>
                <th>CODIGO UNICO</th>
                <th>EMAIL NOTIFICACION</th>
                <th>NRO_DOC_TERCERO</th>
                <th>NOMBRE TERCERO</th>
              </tr>
            </thead>
            <tbody id="tbody_confirmacion"></tbody>
          </table>
        </div>
        <div class="mt-2 text-end">
          <strong>Total: <span id="lbl_total_confirmacion" class="text-success"></span></strong>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="confirmarYEnviar()">
          <i class="bi bi-send me-1"></i>Sí, registrar pagos
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de camiones del contrato --}}
<div class="modal fade" id="modalCamiones" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#1a3a5c 0%,#1976d2 100%);">
        <div>
          <h5 class="modal-title mb-0"><i class="bi bi-truck-front me-2"></i>Estado de camiones</h5>
          <small class="opacity-75" id="modal_camiones_titulo"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" style="background:#f8f9fa;">
        <div id="modal_camiones_body"></div>
      </div>
      <div class="modal-footer border-0" style="background:#f8f9fa;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

</div>
</div>
</section>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
@php
$_sucMap = \App\Models\Parametro::where('tipo','sucursal_cuenta')->whereNull('deleted_at')->pluck('valor','descripcion');
$cuentasJs = $cuentasPorProveedor->map(fn($grupo) => $grupo->map(fn($c) => [
    'id'             => $c->id,
    'banco'          => $c->banco->nombre ?? '—',
    'codigo_banco'   => $c->banco->codigo_banco ?? '',
    'numero'         => $c->numero_cuenta,
    'moneda'         => $c->moneda,
    'alias'          => $c->alias,
    'titular_type'   => $c->titular_type,
    'titular_id'     => $c->titular_id,
    'nombre_titular' => $c->nombre_titular_excel ?? null,
    'tipo_relacion'  => $c->tipo_relacion ?? null,
    'nro_documento'  => $c->nro_documento ?? null,
    'sigla_sucursal'    => $_sucMap[$c->sucursal_departamento] ?? '',
    'email_notificacion'=> $c->email_notificacion ?? '',
])->values());

// Serializa un tramo individual al formato que consume el modal
$_serializarTramo = function($t) {
    return [
        'origen'           => $t->origen ?? '—',
        'destino'          => $t->destino ?? '—',
        'estado'           => $t->estado ?? '—',
        'fecha_salida'     => $t->fecha_salida?->format('d/m/Y') ?? '—',
        'fecha_llegada'    => $t->fecha_llegada?->format('d/m/Y') ?? '—',
        'peso_salida'      => (float) $t->peso_salida,
        'peso_llegada'     => (float) $t->peso_llegada,
        'cliente_nombre'   => $t->cliente?->nombre ?? null,
        'es_hijo'          => !is_null($t->tramo_padre_id),
        'estado_carga'     => (function() use ($t) {
            // Si no tiene hijos, el estado de la carga = su propio estado
            if ($t->tramosHijos->isEmpty()) return $t->estado;
            // Si tiene hijos, verificar si todos los tramos finales están entregados
            $todosEntregados = $t->tramosHijos->every(function($hijo) {
                if ($hijo->tramosHijos->isEmpty()) {
                    return in_array($hijo->estado, ['Entregado', 'Desactivado']);
                }
                return in_array($hijo->estado, ['Entregado', 'Desactivado']);
            });
            return $todosEntregados ? 'Entregado' : 'En proceso';
        })(),
        'flete_acordado'   => (float) ($t->contratoCamion->monto_acordado ?? 0),
        'flete_pagado'     => (float) ($t->contratoCamion->total_pagado ?? 0),
        'flete_moneda'     => $t->contratoCamion->moneda_flete ?? 'BOB',
        'moneda_venta'     => $t->moneda_venta ?? 'BOB',
        'deuda_cliente'    => (float) $t->monto_deuda_cliente,
        'cobrado_cliente'  => (float) $t->total_cobrado_cliente,
        'pct_cobrado'      => $t->monto_deuda_cliente > 0
                                ? round($t->total_cobrado_cliente / $t->monto_deuda_cliente * 100, 1)
                                : 0,
    ];
};

// Aplana el árbol de tramos: recorre cada tramo y sus hijos (que pueden vivir
// en otros ContratoCamion por transbordo/división) en orden jerárquico.
$_aplanarTramos = function($tramos) use (&$_aplanarTramos, $_serializarTramo) {
    $resultado = collect();
    foreach ($tramos as $t) {
        $resultado->push($_serializarTramo($t));
        $hijos = $t->tramosHijos->sortBy('id');
        if ($hijos->isNotEmpty()) {
            $resultado = $resultado->merge($_aplanarTramos($hijos));
        }
    }
    return $resultado;
};

$camionesJs = $contratos->keyBy('id')->map(fn($ct) => [
    'numero'  => $ct->numero_contrato ?? '#'.$ct->id,
    'moneda'  => $ct->moneda ?? 'BOB',
    // Solo CCs raíz: excluir CCs cuyos tramos son TODOS hijos (generados por Div. Carga/transbordo)
    'camiones' => $ct->contratoCamiones->filter(fn($cc) =>
        $cc->tramos->isEmpty() || $cc->tramos->contains(fn($t) => is_null($t->tramo_padre_id))
    )->map(fn($cc) => [
        'placa'          => $cc->camion->placa ?? '—',
        'marca'          => $cc->camion->marca->valor ?? '',
        'conductor'      => $cc->conductor->nombre_completo ?? '—',
        'monto_acordado' => (float) $cc->monto_acordado,
        'monto_neto'     => (float) $cc->monto_neto,
        'total_pagado'   => (float) $cc->total_pagado,
        'saldo'          => (float) $cc->saldo_pendiente,
        'moneda_flete'   => $cc->moneda_flete ?? 'BOB',
        'pct_pagado'     => $cc->monto_neto > 0 ? round($cc->total_pagado / $cc->monto_neto * 100, 1) : 0,
        // Partir de los tramos raíz del CC y aplanar todo el árbol descendiente,
        // incluyendo los hijos generados por transbordo o división de carga.
        'tramos'         => $_aplanarTramos($cc->tramos->whereNull('tramo_padre_id'))->values()->all(),
    ])->values()->all(),
]);
@endphp
<script>
// Datos de cuentas por proveedor (para Paso 2)
const _cuentasPorProveedor = {!! json_encode($cuentasJs) !!};
// Datos de camiones por contrato_id
const _camionesXContrato = {!! json_encode($camionesJs) !!};


function _fmtM(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(n) || 0);
}

function clampPct(input) {
    let v = parseFloat(input.value);
    if (isNaN(v)) return;
    if (v < 0) input.value = 0;
}

// Contratos seleccionados acumulados en Paso 1
let _seleccionados = {}; // { contratoId: { proveedorId, saldo, moneda, pct, monto, provNombre, contratoNum } }
let _saldoCuenta   = 0;
let _monedaCuenta  = '';

function onCuentaOrigenChange() {
    const sel = document.getElementById('cuenta_origen_id');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) {
        _saldoCuenta  = 0;
        _monedaCuenta = '';
        document.getElementById('saldo_cuenta_info').style.display = 'none';
    } else {
        _saldoCuenta  = parseFloat(opt.dataset.saldo) || 0;
        _monedaCuenta = opt.dataset.moneda || '';
        document.getElementById('lbl_saldo_cuenta').textContent = `${_monedaCuenta} ${_fmtM(_saldoCuenta)}`;
        document.getElementById('saldo_cuenta_info').style.display = '';
    }
    verificarSaldo();
    actualizarBtnPaso2();
}

function _totalSeleccionado() {
    return Object.values(_seleccionados).reduce((s, e) => s + (e.monto || 0), 0);
}

function verificarSaldo() {
    if (!_monedaCuenta) {
        document.getElementById('aviso_saldo_insuficiente').style.display = 'none';
        return true;
    }
    const total     = _totalSeleccionado();
    const insuf     = _saldoCuenta > 0 && total > _saldoCuenta;
    const avisoEl   = document.getElementById('aviso_saldo_insuficiente');
    const lbl       = document.getElementById('lbl_saldo_cuenta');

    if (insuf) {
        avisoEl.style.display = '';
        document.getElementById('lbl_total_insuf').textContent = `${_monedaCuenta} ${_fmtM(total)}`;
        lbl.className = 'text-danger';
    } else {
        avisoEl.style.display = 'none';
        lbl.className = 'text-success';
    }
    return !insuf;
}

function toggleProveedor(provId, checked) {
    document.querySelectorAll(`.chk_contrato[data-proveedor="${provId}"]`).forEach(chk => {
        chk.checked = checked;
        onCheckContrato(parseInt(chk.id.replace('chk_c_', '')), checked);
    });
}

function onCheckContrato(contratoId, checked) {
    const row = document.querySelector(`tr[data-contrato-id="${contratoId}"]`);
    const pctInput = document.getElementById(`pct_${contratoId}`);
    const montoInput = document.getElementById(`monto_${contratoId}`);
    if (checked) {
        pctInput.disabled   = false;
        montoInput.disabled = false;
        const saldo   = parseFloat(row.dataset.saldo);
        const moneda  = row.dataset.moneda;
        const provId  = parseInt(row.dataset.proveedorId);
        const provNom = document.querySelector(`#chk_prov_${provId}`)?.closest('.card-header')?.querySelector('label')?.textContent?.trim() ?? '—';
        const ctrNum  = row.querySelector('.fw-semibold.small')?.textContent?.trim() ?? '—';
        _seleccionados[contratoId] = {
            proveedorId: provId,
            saldo, moneda,
            pct: 0, monto: 0,
            provNombre: provNom,
            contratoNum: ctrNum,
            cuentaDestinoId: null,
            cuentaDestinoLabel: null,
        };
    } else {
        pctInput.disabled   = true;
        pctInput.value      = '';
        montoInput.disabled = true;
        montoInput.value    = '';
        delete _seleccionados[contratoId];
    }
    actualizarTotales();
    actualizarBtnPaso2();
}

function calcularMonto(contratoId) {
    const input      = document.getElementById(`pct_${contratoId}`);
    const montoInput = document.getElementById(`monto_${contratoId}`);
    const errEl      = document.getElementById(`err_pct_${contratoId}`);
    const pct        = parseFloat(input.value) || 0;
    const entry      = _seleccionados[contratoId];
    if (!entry) return;

    const invalido = pct < 0;
    input.classList.toggle('is-invalid', invalido);
    errEl.style.display = invalido ? '' : 'none';

    const pctReal = invalido ? 0 : pct;
    const monto   = Math.round(entry.saldo * pctReal / 100 * 100) / 100;
    entry.pct     = pctReal;
    entry.monto   = monto;

    montoInput.value = (pctReal > 0 && !invalido) ? monto.toFixed(2) : '';
    actualizarTotales();
    actualizarBtnPaso2();
}

function calcularPct(contratoId) {
    const montoInput = document.getElementById(`monto_${contratoId}`);
    const pctInput   = document.getElementById(`pct_${contratoId}`);
    const errEl      = document.getElementById(`err_pct_${contratoId}`);
    const entry      = _seleccionados[contratoId];
    if (!entry) return;

    const monto = parseFloat(montoInput.value) || 0;
    const pct   = entry.saldo > 0 ? Math.round(monto / entry.saldo * 10000) / 100 : 0;

    const invalido = monto < 0 || monto > entry.saldo;
    montoInput.classList.toggle('is-invalid', invalido);
    errEl.style.display = invalido ? '' : 'none';

    entry.monto = invalido ? 0 : monto;
    entry.pct   = invalido ? 0 : pct;

    pctInput.value = (monto > 0 && !invalido) ? pct.toFixed(2) : '';
    actualizarTotales();
    actualizarBtnPaso2();
}

function actualizarTotales() {
    const ids = Object.keys(_seleccionados);
    document.getElementById('lbl_seleccionados').textContent = ids.length;
    const totPorMoneda = {};
    ids.forEach(id => {
        const e = _seleccionados[id];
        if (!totPorMoneda[e.moneda]) totPorMoneda[e.moneda] = 0;
        totPorMoneda[e.moneda] += e.monto;
    });
    const txt = Object.entries(totPorMoneda).map(([m, v]) => `${m} ${_fmtM(v)}`).join(' + ');
    document.getElementById('lbl_total').textContent = txt || '—';
    verificarSaldo();
}

function actualizarBtnPaso2() {
    const ids         = Object.keys(_seleccionados);
    const todosConPct = ids.length > 0 && ids.every(id => _seleccionados[id].pct > 0 && _seleccionados[id].monto > 0);
    const sinErrores  = document.querySelectorAll('.pct_input.is-invalid').length === 0;
    const ctaOrigen   = document.getElementById('cuenta_origen_id').value;
    const fecha       = document.getElementById('fecha_pago').value;
    const metodo      = document.getElementById('metodo_pago').value;
    const saldoOk     = verificarSaldo();
    document.getElementById('btn_paso2').disabled = !(todosConPct && sinErrores && ctaOrigen && fecha && metodo && saldoOk);
}

document.getElementById('fecha_pago').addEventListener('change', actualizarBtnPaso2);
document.getElementById('metodo_pago').addEventListener('change', actualizarBtnPaso2);

function irAPaso2() {
    const ids = Object.keys(_seleccionados);
    if (ids.length === 0) return;

    const lista = document.getElementById('paso2_lista');
    lista.innerHTML = '';

    // Agrupar por proveedor
    const porProv = {};
    ids.forEach(id => {
        const e = _seleccionados[id];
        if (!porProv[e.proveedorId]) porProv[e.proveedorId] = [];
        porProv[e.proveedorId].push({ id: parseInt(id), ...e });
    });

    Object.entries(porProv).forEach(([provId, contratos]) => {
        const provNom = contratos[0].provNombre;
        const cuentas = _cuentasPorProveedor[provId] || [];

        const card = document.createElement('div');
        card.className = 'card border mb-3';

        let headerHtml = `
          <div class="card-header py-2 bg-light d-flex align-items-center gap-2">
            <i class="bi bi-box-seam me-1"></i>
            <strong>${provNom}</strong>
            <span class="ms-auto text-muted small">${contratos.length} contrato(s)</span>
          </div>`;

        let bodyHtml = '<div class="card-body p-0">';
        contratos.forEach(ctr => {
            const badgeOk = `<span class="badge bg-success ms-2 d-none" id="badge_ok_${ctr.id}"><i class="bi bi-check-circle me-1"></i>Asignada</span>`;
            bodyHtml += `
              <div class="border-bottom p-2 ps-3" data-contrato-id="${ctr.id}">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="fw-semibold small">${ctr.contratoNum}</span>
                  <span class="badge bg-primary ms-1">${ctr.moneda} ${_fmtM(ctr.monto)}</span>
                  <span class="text-muted small">(${ctr.pct}% de saldo)</span>
                  ${badgeOk}
                </div>`;

            if (cuentas.length === 0) {
                bodyHtml += `<div class="alert alert-warning py-1 small mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Este proveedor no tiene cuentas bancarias registradas.</div>`;
            } else {
                bodyHtml += `<div class="table-responsive"><table class="table table-sm table-hover mb-1">
                  <thead class="table-light"><tr>
                    <th style="width:40px"></th>
                    <th>Banco</th>
                    <th>N° Cuenta</th>
                    <th>Moneda</th>
                    <th>Titular / Alias</th>
                  </tr></thead><tbody>`;
                cuentas.forEach(cta => {
                    const label         = `${cta.banco} ${cta.numero}${cta.alias ? ' ('+cta.alias+')' : ''} [${cta.moneda}]`;
                    const nomTitularEsc = (cta.nombre_titular ?? '').replace(/`/g, '').replace(/'/g, "\\'");
                    const nroDocEsc     = (cta.nro_documento ?? '').replace(/`/g, '').replace(/'/g, "\\'");
                    const codBancoEsc   = (cta.codigo_banco  ?? '').replace(/`/g, '').replace(/'/g, "\\'");
                    const siglaSucEsc   = (cta.sigla_sucursal    ?? '').replace(/`/g, '').replace(/'/g, "\\'");
                    const emailEsc      = (cta.email_notificacion ?? '').replace(/`/g, '').replace(/'/g, "\\'");
                    const esGanadero    = cta.banco === 'Banco Ganadero' ? 1 : 0;
                    bodyHtml += `
                      <tr style="cursor:pointer" onclick="seleccionarCuenta(${ctr.id}, ${cta.id}, \`${label.replace(/`/g,'')}\`, this, '${nomTitularEsc}', '${nroDocEsc}', '${codBancoEsc}', '${siglaSucEsc}', '${emailEsc}', ${esGanadero})">
                        <td class="text-center">
                          <input type="radio" class="form-check-input" name="radio_cta_${ctr.id}" value="${cta.id}" readonly>
                        </td>
                        <td><small>${cta.banco}</small></td>
                        <td><small>${cta.numero}</small></td>
                        <td><small>${cta.moneda}</small></td>
                        <td><small>${cta.nombre_titular ?? ''}${cta.tipo_relacion ? ' <span class="badge bg-secondary ms-1" style="font-size:.6rem">'+cta.tipo_relacion+'</span>' : ''}${cta.alias ? ' ('+cta.alias+')' : ''}</small></td>
                      </tr>`;
                });
                bodyHtml += `</tbody></table></div>`;
            }
            bodyHtml += `</div>`;
        });

        bodyHtml += '</div>';
        card.innerHTML = headerHtml + bodyHtml;
        lista.appendChild(card);
    });

    document.getElementById('paso1_contenido').style.display = 'none';
    document.getElementById('paso2_contenido').style.display = '';
    document.getElementById('btn_paso2').style.display        = 'none';
    document.getElementById('btn_paso2_volver').style.display  = '';
    document.getElementById('cuenta_origen_id').disabled       = true;
    actualizarBtnConfirmar();
}

function seleccionarCuenta(contratoId, ctaId, ctaLabel, tr, nombreTitular, nroDocumento, codigoBanco, siglaSucursal, emailNotificacion, esGanadero) {
    // Marcar radio
    const radio = tr.querySelector('input[type=radio]');
    if (radio) radio.checked = true;

    // Resaltar fila activa
    const tbody = tr.closest('tbody');
    tbody.querySelectorAll('tr').forEach(r => r.classList.remove('table-primary'));
    tr.classList.add('table-primary');

    // Guardar en seleccionados
    if (_seleccionados[contratoId]) {
        _seleccionados[contratoId].cuentaDestinoId     = ctaId;
        _seleccionados[contratoId].cuentaDestinoLabel  = ctaLabel;
        _seleccionados[contratoId].cuentaTitularNombre = nombreTitular || null;
        _seleccionados[contratoId].cuentaNroDocumento  = nroDocumento  || null;
        _seleccionados[contratoId].cuentaCodigoBanco   = codigoBanco   || null;
        _seleccionados[contratoId].cuentaSiglaSucursal    = siglaSucursal    || null;
        _seleccionados[contratoId].cuentaEmailNotificacion = emailNotificacion || null;
        _seleccionados[contratoId].esGanadero             = esGanadero ? true : false;
    }

    // Badge
    const badge = document.getElementById(`badge_ok_${contratoId}`);
    if (badge) badge.classList.remove('d-none');

    actualizarBtnConfirmar();
}

function actualizarBtnConfirmar() {
    const ids = Object.keys(_seleccionados);
    const todosConCuenta = ids.length > 0 && ids.every(id => _seleccionados[id].cuentaDestinoId);
    document.getElementById('btn_confirmar').disabled = !todosConCuenta;
}

function volverPaso1() {
    document.getElementById('paso2_contenido').style.display = 'none';
    document.getElementById('paso1_contenido').style.display = '';
    document.getElementById('btn_paso2').style.display        = '';
    document.getElementById('btn_paso2_volver').style.display  = 'none';
    document.getElementById('cuenta_origen_id').disabled       = false;
}

// ===================== CONFIRMACIÓN =====================

function _buildRows() {
    const ids     = Object.keys(_seleccionados);
    const fechaRaw = document.getElementById('fecha_pago').value;
    const [fy, fm, fd] = fechaRaw.split('-');
    const fecha    = fd ? `${fd}/${fm}/${fy}` : fechaRaw;
    const metodo  = document.getElementById('metodo_pago').value;
    const ctaOrigenSel = document.getElementById('cuenta_origen_id');
    const monedaOrigen = ctaOrigenSel.options[ctaOrigenSel.selectedIndex]?.dataset?.moneda ?? 'BOB';

    return ids.map((id, i) => {
        const e   = _seleccionados[id];
        const cta = e.cuentaDestinoLabel ?? '—';
        const ctaId = e.cuentaDestinoId;

        // Extraer banco y número de la etiqueta
        const [bancoParte, ...resto] = cta.split(' ');
        const numeroCuenta = cta.match(/\d{6,}/)?.[0] ?? '';
        const bancoNombre  = cta.split(' ')[0] ?? '—';

        const nombreExcel = e.cuentaTitularNombre || e.provNombre;
        const ganadero    = e.esGanadero;

        return {
            nro:         i + 1,
            nro_orden:   i + 1,
            cod_cliente: 0,
            nro_cuenta:  numeroCuenta,
            nombre:      nombreExcel,
            doc_id:      e.cuentaNroDocumento || '',
            importe:     e.monto.toFixed(2).replace('.', ','),
            fecha:       fecha,
            forma_pago:  ganadero ? 1 : 3,
            moneda:      ganadero ? 0 : (e.moneda === 'USD' ? 2 : 1),
            entidad:     ganadero ? 0 : (e.cuentaCodigoBanco || ''),
            sucursal:    ganadero ? 0 : (e.cuentaSiglaSucursal || ''),
            glosa:       `Pago proveedor ${e.contratoNum}`,
            codigo:      '',
            email:       e.cuentaEmailNotificacion || '',
            nro_doc_ter: '',
            nombre_ter:  '',
            // para el form
            contratoId:  id,
            ctaId:       ctaId,
            pct:         e.pct,
        };
    });
}

function abrirConfirmacion() {
    const rows  = _buildRows();
    const tbody = document.getElementById('tbody_confirmacion');
    tbody.innerHTML = '';
    let total = 0;
    let moneda = '';

    rows.forEach(r => {
        moneda = _seleccionados[r.contratoId]?.moneda ?? '';
        total += parseFloat(r.importe.replace(',', '.'));
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.nro_orden}</td>
          <td>${r.cod_cliente}</td>
          <td>${r.nro_cuenta}</td>
          <td>${r.nombre}</td>
          <td>${r.doc_id}</td>
          <td class="text-end fw-semibold">${r.importe}</td>
          <td>${r.fecha}</td>
          <td>${r.forma_pago}</td>
          <td>${r.moneda}</td>
          <td>${r.entidad}</td>
          <td>${r.sucursal}</td>
          <td>${r.glosa}</td>
          <td>${r.codigo}</td>
          <td>${r.email}</td>
          <td>${r.nro_doc_ter}</td>
          <td>${r.nombre_ter}</td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('lbl_total_confirmacion').textContent = `${moneda} ${_fmtM(total)}`;
    new bootstrap.Modal(document.getElementById('modalConfirmacion')).show();
}

function confirmarYEnviar() {
    const rows = _buildRows();

    document.getElementById('h_cuenta_origen_id').value   = document.getElementById('cuenta_origen_id').value;
    document.getElementById('h_fecha_pago').value          = document.getElementById('fecha_pago').value;
    document.getElementById('h_metodo_pago').value         = document.getElementById('metodo_pago').value;

    const contenedor = document.getElementById('h_contratos_dinamicos');
    contenedor.innerHTML = '';

    rows.forEach(r => {
        const addHidden = (name, val) => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = name;
            inp.value = val;
            contenedor.appendChild(inp);
        };
        addHidden(`contrato_ids[]`, r.contratoId);
        addHidden(`porcentaje[${r.contratoId}]`, r.pct);
        addHidden(`cuenta_destino[${r.contratoId}]`, r.ctaId);
    });

    document.getElementById('form_pago_masivo').submit();
}

function descargarExcel() {
    const rows = _buildRows();
    const cols = ['NRO DE ORDEN','CODIGO DE CLIENTE','NRO DE CUENTA','NOMBRE DEL CLIENTE','DOC DE IDENTIDAD','IMPORTE','FECHA DE PAGO','FORMA DE PAGO','MONEDA DESTINO','ENTIDAD DESTINO','SUCURSAL DESTINO','GLOSA','CODIGO UNICO','EMAIL NOTIFICACION','NRO_DOC_TERCERO','NOMBRE TERCERO'];
    const dataRows = rows.map(r => [r.nro_orden,r.cod_cliente,r.nro_cuenta,r.nombre,r.doc_id,r.importe,r.fecha,r.forma_pago,r.moneda,r.entidad,r.sucursal,r.glosa,r.codigo,r.email,r.nro_doc_ter,r.nombre_ter]);
    _exportarXlsx(cols, dataRows, 'Hoja 1', `pago_masivo_proveedores_{{ date('Ymd') }}.xlsx`);
}

// ---- Generador XLSX con cabecera estilizada (fondo verde oscuro + texto blanco + negrita) ----
function _exportarXlsx(headers, rows, sheetName, filename) {
    const esc = v => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    // Estilo 0 = normal, Estilo 1 = cabecera (verde oscuro, blanco, negrita)
    const styleXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1A6B2F"/></patternFill></fill>
  </fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>`;

    const colLetter = i => { let s='', n=i+1; while(n>0){s=String.fromCharCode(65+(n-1)%26)+s;n=Math.floor((n-1)/26);} return s; };

    // Calcular ancho de cada columna: máximo entre encabezado y datos, mínimo 12
    // Anchos fijos en unidades Excel (píxeles / 7)
    const colWidths = [20.6, 23.6, 23.9, 40.4, 25.6, 20.6, 22.6, 22.4, 24.1, 24.6, 25.7, 20.3, 19.0, 25.9, 25.9, 25.9];
    let colsXml = '<cols>';
    colWidths.forEach((w, ci) => { colsXml += `<col min="${ci+1}" max="${ci+1}" width="${w}" customWidth="1"/>`; });
    colsXml += '</cols>';

    let sheetData = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  ${colsXml}
  <sheetData>
    <row r="1">`;
    headers.forEach((h, ci) => {
        sheetData += `<c r="${colLetter(ci)}1" t="inlineStr" s="1"><is><t>${esc(h)}</t></is></c>`;
    });
    sheetData += `</row>`;
    rows.forEach((row, ri) => {
        sheetData += `<row r="${ri+2}">`;
        row.forEach((val, ci) => {
            sheetData += `<c r="${colLetter(ci)}${ri+2}" t="inlineStr"><is><t>${esc(val)}</t></is></c>`;
        });
        sheetData += `</row>`;
    });
    sheetData += `</sheetData></worksheet>`;

    const wb = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="${esc(sheetName)}" sheetId="1" r:id="rId1"/></sheets>
</workbook>`;

    const rels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`;

    const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>`;

    const rootRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>`;

    const zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    const xl = zip.folder('xl');
    xl.file('workbook.xml', wb);
    xl.file('styles.xml', styleXml);
    xl.folder('_rels').file('workbook.xml.rels', rels);
    xl.folder('worksheets').file('sheet1.xml', sheetData);

    zip.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
       .then(blob => {
           const url = URL.createObjectURL(blob);
           const a = document.createElement('a');
           a.href = url; a.download = filename; a.click();
           URL.revokeObjectURL(url);
       });
}

function abrirEnVentana() {
    const rows = _buildRows();
    const cols = ['NRO DE ORDEN','CODIGO DE CLIENTE','NRO DE CUENTA','NOMBRE DEL CLIENTE','DOC DE IDENTIDAD','IMPORTE','FECHA DE PAGO','FORMA DE PAGO','MONEDA DESTINO','ENTIDAD DESTINO','SUCURSAL DESTINO','GLOSA','CODIGO UNICO','EMAIL NOTIFICACION','NRO_DOC_TERCERO','NOMBRE TERCERO'];
    const th   = cols.map(c => `<th style="background:#1d7a3a;color:#fff;padding:6px 10px;white-space:nowrap">${c}</th>`).join('');
    let tr_html = rows.map((r,i) => {
        const bg = i%2===0 ? '#fff' : '#f5f5f5';
        const td = [r.nro_orden,r.cod_cliente,r.nro_cuenta,r.nombre,r.doc_id,r.importe,r.fecha,r.forma_pago,r.moneda,r.entidad,r.sucursal,r.glosa,r.codigo,r.email,r.nro_doc_ter,r.nombre_ter];
        return `<tr style="background:${bg}">${td.map(v=>`<td style="padding:5px 10px;border:1px solid #ddd">${v??''}</td>`).join('')}</tr>`;
    }).join('');
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pago Masivo Proveedores</title></head><body style="font-family:Arial,sans-serif;font-size:12px"><h3>Vista previa pago masivo a proveedores</h3><div style="overflow-x:auto"><table style="border-collapse:collapse;width:100%"><thead><tr>${th}</tr></thead><tbody>${tr_html}</tbody></table></div></body></html>`;
    const w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
}

// ---- Modal camiones del contrato ----
const _estadoCfg = {
    'En ruta':         { bg:'#1976d2', icon:'bi-truck',           label:'En ruta'         },
    'Transbordando':   { bg:'#f59e0b', icon:'bi-arrow-left-right', label:'Transbordando'   },
    'Transbordado':    { bg:'#0891b2', icon:'bi-check2-all',       label:'Transbordado'    },
    'Entregado':       { bg:'#16a34a', icon:'bi-check-circle',     label:'Entregado'       },
    'Div. Carga': { bg:'#16a34a', icon:'bi-check-circle',     label:'Div. Carga' },
};

function _fmt(n) { return n.toLocaleString('es-BO', {minimumFractionDigits:2, maximumFractionDigits:2}); }

function _buildCobradoClienteHtml(tramos) {
    const con = tramos.filter(function(t) { return t.deuda_cliente > 0; });
    if (!con.length) return '';
    var totalDeuda   = con.reduce(function(s,t){ return s+t.deuda_cliente; }, 0);
    var totalCobrado = con.reduce(function(s,t){ return s+t.cobrado_cliente; }, 0);
    var pct  = totalDeuda > 0 ? Math.min(100, Math.round(totalCobrado / totalDeuda * 100)) : 0;
    var col  = pct >= 100 ? '#16a34a' : pct >= 50 ? '#1976d2' : '#f59e0b';
    var mon  = con[0] ? con[0].moneda_venta : '';
    return '<div class="flex-fill py-2 px-3 d-flex flex-column justify-content-center" style="min-width:130px">'
        + '<div class="d-flex justify-content-between mb-1">'
        + '<span style="font-size:.68rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px">% cobrado cliente</span>'
        + '<span class="fw-bold" style="font-size:.78rem;color:' + col + '">' + pct + '%</span>'
        + '</div>'
        + '<div style="height:8px;background:#e9ecef;border-radius:99px;overflow:hidden">'
        + '<div style="height:100%;width:' + pct + '%;background:' + col + ';border-radius:99px;transition:width .5s ease"></div>'
        + '</div>'
        + '<div style="font-size:.65rem;color:#6c757d;margin-top:2px">' + mon + ' ' + _fmt(totalCobrado) + ' / ' + _fmt(totalDeuda) + '</div>'
        + '</div>';
}

function verCamiones(contratoId) {
    const data = _camionesXContrato[contratoId];
    if (!data) return;

    document.getElementById('modal_camiones_titulo').textContent = 'Contrato ' + data.numero;

    if (!data.camiones.length) {
        document.getElementById('modal_camiones_body').innerHTML = `
          <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <i class="bi bi-truck" style="font-size:3rem;opacity:.3"></i>
            <p class="mt-3 mb-0">No hay camiones asignados a este contrato.</p>
          </div>`;
        new bootstrap.Modal(document.getElementById('modalCamiones')).show();
        return;
    }

    let html = '<div class="p-3">';

    data.camiones.forEach(function(cc) {
        var pct      = cc.pct_pagado;
        var pctClamp = Math.min(pct, 100);
        var pctBg    = pct >= 100 ? '#16a34a' : pct >= 50 ? '#1976d2' : '#f59e0b';

        // Para tramos Div. Carga: color amarillo si algún hijo aún no entregado
        var hijosEstados = cc.tramos.filter(function(t){ return t.es_hijo; }).map(function(t){ return t.estado; });

        // Camión entregado: el tramo raíz (último de la lista) ya llegó a destino
        var ultimoTramo    = cc.tramos.length ? cc.tramos[cc.tramos.length - 1] : null;
        var camionEntregado = !!ultimoTramo && (
            ultimoTramo.estado === 'Entregado'
            || (ultimoTramo.estado === 'Div. Carga' && hijosEstados.filter(function(e){ return e !== 'Entregado' && e !== 'Desactivado'; }).length === 0)
        );
        var truckGradient = camionEntregado ? 'linear-gradient(135deg,#0f5132,#16a34a)' : 'linear-gradient(135deg,#1a3a5c,#1976d2)';

        // Tramos
        var tramosHtml = '';
        if (cc.tramos.length) {
            tramosHtml = '<div class="mt-3">';

            cc.tramos.forEach(function(t, ti) {
                var cfgBase = _estadoCfg[t.estado] || { bg:'#6c757d', icon:'bi-circle', label: t.estado };
                // Si es Div. Carga y hay hijos sin entregar → amarillo
                var cfg = cfgBase;
                if (t.estado === 'Div. Carga') {
                    var pendientes = hijosEstados.filter(function(e){ return e !== 'Entregado' && e !== 'Desactivado'; }).length;
                    cfg = pendientes > 0
                        ? { bg:'#f59e0b', icon:'bi-pie-chart', label:'Div. Carga' }
                        : { bg:'#16a34a', icon:'bi-check-circle', label:'Div. Carga' };
                }
                var isLast = ti === cc.tramos.length - 1;

                var lineaHtml = isLast ? '' :
                    '<div style="width:2px;flex:1;min-height:16px;background:linear-gradient(' + cfg.bg + ',#dee2e6);margin-top:2px"></div>';

                var hijoHtml    = t.es_hijo ? '<span class="badge bg-secondary me-1" style="font-size:.6rem">Redistribución</span>' : '';
                var clienteHtml = t.cliente_nombre ? '<span class="text-muted ms-1" style="font-size:.75rem"><i class="bi bi-person me-1"></i>' + t.cliente_nombre + '</span>' : '';
                var salidaHtml  = t.peso_salida  > 0 ? '<span><i class="bi bi-box me-1"></i>P. salida: <strong>' + _fmtM(t.peso_salida) + ' t</strong></span>' : '';
                var llegadaHtml = t.peso_llegada > 0 ? '<span><i class="bi bi-box-seam me-1"></i>P. llegada: <strong>' + _fmtM(t.peso_llegada) + ' t</strong></span>' : '';

                // Bloque flete del tramo
                var fleteHtml = '';
                if (t.es_hijo) {
                    if (t.flete_acordado > 0) {
                        var pF   = Math.min(100, t.flete_acordado > 0 ? Math.round(t.flete_pagado / t.flete_acordado * 100) : 0);
                        var pFb  = pF >= 100 ? '#16a34a' : pF >= 50 ? '#1976d2' : '#f59e0b';
                        fleteHtml = '<div class="mt-1 p-1 rounded" style="background:#fefce8;border:1px solid #fde68a">'
                            + '<div class="d-flex justify-content-between align-items-center mb-1">'
                            + '<span style="font-size:.65rem;color:#92400e;text-transform:uppercase;letter-spacing:.4px"><i class="bi bi-truck me-1"></i>Flete</span>'
                            + '<span class="fw-bold" style="font-size:.72rem;color:' + pFb + '">' + pF + '%</span>'
                            + '</div>'
                            + '<div style="height:5px;background:#fef9c3;border-radius:99px;overflow:hidden">'
                            + '<div style="height:100%;width:' + pF + '%;background:' + pFb + ';border-radius:99px"></div>'
                            + '</div>'
                            + '<div style="font-size:.65rem;color:#6c757d;margin-top:2px">' + t.flete_moneda + ' ' + _fmt(t.flete_pagado) + ' pagado de ' + _fmt(t.flete_acordado) + '</div>'
                            + '</div>';
                    } else {
                        fleteHtml = '<div class="mt-1 p-1 rounded" style="background:#fff7ed;border:1px solid #fed7aa">'
                            + '<span style="font-size:.65rem;color:#9a3412"><i class="bi bi-exclamation-circle me-1"></i>Flete a pagar no definido aún</span>'
                            + '</div>';
                    }
                }

                var cobroHtml = '';
                if (t.deuda_cliente > 0) {
                    var pC  = Math.min(100, t.pct_cobrado);
                    var pCb = pC >= 100 ? '#16a34a' : pC >= 50 ? '#1976d2' : '#f59e0b';
                    cobroHtml = '<div class="mt-1 p-1 rounded" style="background:#f0f9ff;border:1px solid #bae6fd">'
                        + '<div class="d-flex justify-content-between align-items-center mb-1">'
                        + '<span style="font-size:.65rem;color:#0369a1;text-transform:uppercase;letter-spacing:.4px"><i class="bi bi-cash-coin me-1"></i>Cobro al cliente</span>'
                        + '<span class="fw-bold" style="font-size:.72rem;color:' + pCb + '">' + t.pct_cobrado + '%</span>'
                        + '</div>'
                        + '<div style="height:5px;background:#e0f2fe;border-radius:99px;overflow:hidden">'
                        + '<div style="height:100%;width:' + pC + '%;background:' + pCb + ';border-radius:99px"></div>'
                        + '</div>'
                        + '<div style="font-size:.65rem;color:#6c757d;margin-top:2px">' + t.moneda_venta + ' ' + _fmt(t.cobrado_cliente) + ' cobrado de ' + _fmt(t.deuda_cliente) + '</div>'
                        + '</div>';
                }

                tramosHtml += '<div class="d-flex gap-3" style="position:relative">'
                    + '<div class="d-flex flex-column align-items-center" style="width:32px;flex-shrink:0">'
                    + '<div style="width:32px;height:32px;border-radius:50%;background:' + cfg.bg + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px ' + cfg.bg + '55">'
                    + '<i class="bi ' + cfg.icon + ' text-white" style="font-size:.85rem"></i>'
                    + '</div>' + lineaHtml + '</div>'
                    + '<div class="pb-3 flex-grow-1" style="' + (t.es_hijo ? 'padding-left:16px;' : '') + '">'
                    + '<div class="d-flex justify-content-between align-items-start flex-wrap gap-1">'
                    + '<div>' + hijoHtml
                    + '<span class="fw-semibold" style="font-size:.85rem">' + t.origen + '</span>'
                    + '<i class="bi bi-arrow-right mx-1 text-muted" style="font-size:.75rem"></i>'
                    + '<span class="fw-semibold" style="font-size:.85rem">' + t.destino + '</span>'
                    + clienteHtml + '</div>'
                    + '<div class="d-flex gap-1 align-items-center">'
                    + '<span class="badge rounded-pill" style="background:' + cfg.bg + ';font-size:.7rem;font-weight:600">'
                    + '<i class="bi ' + cfg.icon + ' me-1"></i>' + cfg.label + '</span>'
                    + ((!t.es_hijo && t.estado !== t.estado_carga)
                        ? '<span class="badge rounded-pill" style="background:' + (t.estado_carga === 'Entregado' ? '#16a34a' : '#64748b') + ';font-size:.7rem;font-weight:600">'
                          + '<i class="bi bi-box-seam me-1"></i>Carga: ' + t.estado_carga + '</span>'
                        : '')
                    + '</div>'
                    + '</div>'
                    + '<div class="d-flex gap-3 mt-1 flex-wrap" style="font-size:.75rem;color:#6c757d">'
                    + '<span><i class="bi bi-calendar-event me-1"></i>Salida: <strong>' + t.fecha_salida + '</strong></span>'
                    + '<span><i class="bi bi-calendar-check me-1"></i>Llegada: <strong>' + t.fecha_llegada + '</strong></span>'
                    + salidaHtml + llegadaHtml + '</div>'
                    + fleteHtml
                    + cobroHtml
                    + '</div></div>';
            });
            tramosHtml += '</div>';
        } else {
            tramosHtml = '<div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Sin tramos registrados.</div>';
        }

        html += '<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;margin-bottom:20px">'
            + '<div class="d-flex align-items-center gap-3 p-3" style="background:#fff;border-bottom:1px solid #e9ecef">'
            + '<div style="width:44px;height:44px;border-radius:10px;background:' + truckGradient + ';display:flex;align-items:center;justify-content:center;flex-shrink:0">'
            + '<i class="bi bi-truck-front text-white" style="font-size:1.2rem"></i></div>'
            + '<div class="flex-grow-1 min-width-0">'
            + '<div class="fw-bold" style="font-size:1rem;letter-spacing:.5px">' + cc.placa
            + '<span class="text-muted fw-normal ms-1" style="font-size:.82rem">' + cc.marca + '</span></div>'
            + '<div style="font-size:.78rem;color:#6c757d"><i class="bi bi-person-badge me-1"></i>' + cc.conductor + '</div>'
            + '</div></div>'
            + '<div class="d-flex" style="background:#f8f9fa;border-bottom:1px solid #e9ecef">'
            + '<div class="flex-fill text-center py-2 px-1" style="border-right:1px solid #e9ecef">'
            + '<div style="font-size:.68rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px">Monto flete</div>'
            + '<div class="fw-bold" style="font-size:.92rem">' + cc.moneda_flete + ' ' + _fmt(cc.monto_neto) + '</div></div>'
            + '<div class="flex-fill text-center py-2 px-1" style="border-right:1px solid #e9ecef">'
            + '<div style="font-size:.68rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px">Adelantado</div>'
            + '<div class="fw-bold" style="font-size:.92rem;color:#d97706">' + cc.moneda_flete + ' ' + _fmt(cc.total_pagado) + '</div></div>'
            + '<div class="flex-fill text-center py-2 px-1" style="border-right:1px solid #e9ecef">'
            + '<div style="font-size:.68rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px">Saldo</div>'
            + '<div class="fw-bold" style="font-size:.92rem;color:#dc3545">' + cc.moneda_flete + ' ' + _fmt(cc.saldo) + '</div></div>'
            + '<div class="flex-fill py-2 px-3 d-flex flex-column justify-content-center" style="min-width:130px;border-right:1px solid #e9ecef">'
            + '<div class="d-flex justify-content-between mb-1">'
            + '<span style="font-size:.68rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px">% flete pagado</span>'
            + '<span class="fw-bold" style="font-size:.78rem;color:' + pctBg + '">' + pct + '%</span></div>'
            + '<div style="height:8px;background:#e9ecef;border-radius:99px;overflow:hidden">'
            + '<div style="height:100%;width:' + pctClamp + '%;background:' + pctBg + ';border-radius:99px;transition:width .5s ease"></div>'
            + '</div></div>'
            + _buildCobradoClienteHtml(cc.tramos)
            + '</div>'
            + '<div class="px-3 pb-3 pt-2" style="background:#fff">'
            + '<div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9ca3af;margin-bottom:6px">'
            + '<i class="bi bi-signpost-split me-1"></i>Tramos</div>'
            + tramosHtml
            + '</div></div>';
    });

    // ── Resumen general de todos los camiones ──
    const monedaGeneral = data.camiones[0]?.moneda_flete ?? data.moneda ?? 'BOB';
    let totalFlete    = 0;
    let totalAdelanto = 0;
    let totalSaldo    = 0;
    data.camiones.forEach(cc => {
        totalFlete    += cc.monto_neto;
        totalAdelanto += cc.total_pagado;
        totalSaldo    += cc.saldo;
    });

    const resumenHtml = '<div style="margin:0 0 8px 0;border-radius:10px;overflow:hidden;border:1px solid #e9ecef;box-shadow:0 2px 6px rgba(0,0,0,.07)">'
        + '<div style="padding:8px 12px;background:linear-gradient(135deg,#1a3a5c,#1976d2)">'
        + '<span style="color:#fff;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.8px"><i class="bi bi-calculator me-1"></i>Resumen general — ' + data.camiones.length + ' camión(es)</span>'
        + '</div>'
        + '<div style="display:flex;background:#fff">'
        + '<div style="flex:1;text-align:center;padding:12px 8px;border-right:1px solid #e9ecef"><div style="font-size:.65rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Total Fletes</div><div style="font-weight:700;font-size:1rem;color:#1a3a5c">' + monedaGeneral + ' ' + _fmt(totalFlete) + '</div></div>'
        + '<div style="flex:1;text-align:center;padding:12px 8px;border-right:1px solid #e9ecef"><div style="font-size:.65rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Total Adelantado</div><div style="font-weight:700;font-size:1rem;color:#d97706">' + monedaGeneral + ' ' + _fmt(totalAdelanto) + '</div></div>'
        + '<div style="flex:1;text-align:center;padding:12px 8px"><div style="font-size:.65rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Saldo Pendiente</div><div style="font-weight:700;font-size:1rem;color:#dc3545">' + monedaGeneral + ' ' + _fmt(totalSaldo) + '</div></div>'
        + '</div></div>';
    html += resumenHtml;

    html += '</div>';
    document.getElementById('modal_camiones_body').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalCamiones')).show();
}
</script>
@endsection
