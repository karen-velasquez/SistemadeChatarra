@extends('layouts.app')
@section('titulo', 'Lotes de Entrega Semanal')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>LOTES DE ENTREGA SEMANAL</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Lotes de Entrega</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<section class="section">

    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('lotes_entrega.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1 small fw-semibold">Proveedor</label>
                    <select name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}" {{ $proveedorFiltro == $prov->id ? 'selected' : '' }}>
                                {{ $prov->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-semibold">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="Abierto" {{ $estadoFiltro === 'Abierto' ? 'selected' : '' }}>Abiertos</option>
                        <option value="Cerrado" {{ $estadoFiltro === 'Cerrado' ? 'selected' : '' }}>Cerrados</option>
                        <option value="Todos"   {{ $estadoFiltro === 'Todos'   ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('lotes_entrega.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($lotes->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-collection fs-1 d-block mb-2"></i>
                No hay lotes de entrega con los filtros seleccionados.
            </div>
        </div>
    @else
        @php
            $lotesPorProveedor = $lotes->getCollection()->groupBy(fn($l) => $l->proveedor_id);
        @endphp

        <div class="accordion" id="accordionProveedores">
        @foreach($lotesPorProveedor as $proveedorId => $lotesProveedor)
        @php
            $proveedor      = $lotesProveedor->first()->proveedor;
            $nombreProv     = $proveedor->nombre ?? 'Sin proveedor';
            $accId          = 'prov-' . $proveedorId;
            $abierto        = $loop->first; // primer proveedor expandido por defecto
            $totalLotes     = $lotesProveedor->count();
            $lotesAbiertos  = $lotesProveedor->where('estado', 'Abierto')->count();
        @endphp

        <div class="accordion-item mb-2 border rounded shadow-sm">
            {{-- Cabecera del proveedor --}}
            <h2 class="accordion-header" id="heading-{{ $accId }}">
                <button class="accordion-button {{ $abierto ? '' : 'collapsed' }} py-2"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-{{ $accId }}"
                        aria-expanded="{{ $abierto ? 'true' : 'false' }}"
                        aria-controls="collapse-{{ $accId }}">
                    <div class="d-flex align-items-center gap-3 w-100 me-3">
                        <i class="bi bi-person-badge text-primary fs-5"></i>
                        <span class="fw-bold fs-6">{{ $nombreProv }}</span>
                        <span class="badge bg-secondary ms-1">{{ $totalLotes }} {{ Str::plural('lote', $totalLotes) }}</span>
                        @if($lotesAbiertos > 0)
                            <span class="badge bg-success">{{ $lotesAbiertos }} abierto{{ $lotesAbiertos > 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                </button>
            </h2>

            {{-- Cuerpo del proveedor --}}
            <div id="collapse-{{ $accId }}"
                 class="accordion-collapse collapse {{ $abierto ? 'show' : '' }}"
                 aria-labelledby="heading-{{ $accId }}"
                 data-bs-parent="#accordionProveedores">
                <div class="accordion-body p-0">

                    {{-- Sub-accordion por lote --}}
                    <div class="accordion" id="accordionLotes-{{ $accId }}">
                    @foreach($lotesProveedor as $lote)
                    @php
                        $tramos             = $lote->tramos->where('activo', true);
                        $totalToneladas     = $tramos->sum(fn($t) => (float)$t->peso_llegada);
                        $contratos          = $tramos->map(fn($t) => $t->contratoCamion?->contrato)->filter()->unique('id');
                        $costoTotal         = $tramos->sum(fn($t) => (float)$t->peso_llegada * (float)($t->contratoCamion?->contrato?->costo_unitario ?? 0));
                        $costoPromedio      = $totalToneladas > 0 ? $costoTotal / $totalToneladas : 0;
                        $loteAccId          = 'lote-' . $lote->id;
                        $loteAbierto        = $loop->first && $abierto;
                    @endphp

                    <div class="accordion-item border-0 border-bottom">
                        {{-- Cabecera del lote --}}
                        <h2 class="accordion-header" id="heading-{{ $loteAccId }}">
                            <div class="d-flex align-items-center w-100">
                                <button class="accordion-button {{ $loteAbierto ? '' : 'collapsed' }} py-2 ps-4 bg-light flex-grow-1"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $loteAccId }}"
                                        aria-expanded="{{ $loteAbierto ? 'true' : 'false' }}">
                                    <div class="d-flex align-items-center gap-2 flex-wrap w-100 me-2">
                                        @if($lote->codigo)
                                            <span class="badge bg-primary font-monospace">{{ $lote->codigo }}</span>
                                        @endif
                                        <span class="fw-semibold small">Semana {{ $lote->numero_semana }}/{{ $lote->anio }}</span>
                                        <span class="text-muted small">{{ $lote->fecha_inicio->format('d/m') }} – {{ $lote->fecha_fin->format('d/m/Y') }}</span>
                                        @if($lote->estado === 'Abierto')
                                            <span class="badge bg-success">Abierto</span>
                                        @else
                                            <span class="badge bg-secondary">Cerrado</span>
                                        @endif
                                        <span class="ms-auto d-flex align-items-center gap-3 small text-muted">
                                            <span><i class="bi bi-truck text-success me-1"></i>{{ $tramos->count() }} entregas</span>
                                            <span><i class="bi bi-box text-primary me-1"></i>{{ number_format($totalToneladas, 2, ',', '.') }} t</span>
                                            <span><i class="bi bi-file-text me-1"></i>{{ $contratos->count() }} {{ Str::plural('contrato', $contratos->count()) }}</span>
                                            @if($lote->estado === 'Cerrado' && $lote->cerrado_at)
                                                <span class="text-secondary"><i class="bi bi-lock-fill me-1"></i>{{ $lote->cerrado_at->format('d/m/Y') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </button>
                                @if($lote->estado === 'Abierto')
                                    @can('contratos.edit')
                                    <form method="POST" action="{{ route('lotes_entrega.cerrar', $lote->uuid) }}"
                                          class="ms-2 me-2 flex-shrink-0"
                                          onsubmit="return confirm('¿Cerrar el lote {{ $lote->codigo }}? Esta acción no se puede deshacer.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-lock"></i> Cerrar
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </h2>

                        {{-- Cuerpo del lote --}}
                        <div id="collapse-{{ $loteAccId }}"
                             class="accordion-collapse collapse {{ $loteAbierto ? 'show' : '' }}">
                            <div class="accordion-body px-4 py-3">

                                {{-- Métricas del lote --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <div class="fw-bold fs-5 text-primary">{{ $tramos->count() }}</div>
                                            <small class="text-muted">Entregas</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <div class="fw-bold fs-5 text-success">{{ number_format($totalToneladas, 3, ',', '.') }} t</div>
                                            <small class="text-muted">Total toneladas</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <div class="fw-bold fs-5">{{ $contratos->count() }}</div>
                                            <small class="text-muted">{{ Str::plural('Contrato', $contratos->count()) }}</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <div class="fw-bold fs-5 text-warning">{{ number_format($costoPromedio, 2, ',', '.') }}</div>
                                            <small class="text-muted">Costo prom. BOB/t</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tabla de entregas --}}
                                @if($tramos->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Camión</th>
                                                <th>Origen → Destino</th>
                                                <th>Tipo</th>
                                                <th class="text-end">Ton. llegada</th>
                                                <th class="text-end">Costo/t</th>
                                                <th>Fecha llegada</th>
                                                <th>Contrato / % entregado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tramos->sortByDesc('fecha_llegada') as $t)
                                            @php
                                                $costoUt  = $t->contratoCamion?->contrato?->costo_unitario ?? 0;
                                                $contrato = $t->contratoCamion?->contrato;
                                                $tnTotal  = (float)($contrato?->toneladas_contrato ?? 0);
                                                $pctEnt   = $contrato ? $contrato->porcentaje_toneladas : 0;
                                                $pctTra   = $contrato && $tnTotal > 0
                                                    ? min(100 - $pctEnt, round(($contrato->toneladas_en_transito / $tnTotal) * 100, 1))
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $t->camion?->placa ?? '—' }}</td>
                                                <td>{{ $t->origen }} → {{ $t->destino }}</td>
                                                <td>
                                                    <span class="badge {{ $t->tipo_tramo === 'Nacional' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                                                        {{ $t->tipo_tramo }}
                                                    </span>
                                                </td>
                                                <td class="text-end">{{ number_format((float)$t->peso_llegada, 3, ',', '.') }}</td>
                                                <td class="text-end">
                                                    @if($costoUt)
                                                        {{ number_format((float)$costoUt, 2, ',', '.') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>{{ $t->fecha_llegada?->format('d/m/Y') ?? '—' }}</td>
                                                <td>
                                                    @if($contrato)
                                                        <div class="fw-semibold small mb-1">{{ $contrato->numero_contrato }}</div>
                                                        <div class="progress" style="height:10px;min-width:80px;">
                                                            @if($pctEnt > 0)
                                                            <div class="progress-bar bg-success" style="width:{{ $pctEnt }}%"
                                                                 title="Entregado: {{ $pctEnt }}%"></div>
                                                            @endif
                                                            @if($pctTra > 0)
                                                            <div class="progress-bar" style="width:{{ $pctTra }}%;background:#38bdf8;"
                                                                 title="En ruta: {{ $pctTra }}%"></div>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted">
                                                            @if($pctEnt > 0)
                                                                <span class="text-success fw-semibold">{{ $pctEnt }}%</span> ent.
                                                            @endif
                                                            @if($pctTra > 0)
                                                                <span style="color:#38bdf8;" class="fw-semibold ms-1">{{ $pctTra }}%</span> ruta
                                                            @endif
                                                        </small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Aún no hay entregas asignadas a este lote.</small>
                                @endif

                                {{-- ===== PAGOS EXTRAS ===== --}}
                                <hr class="my-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="bi bi-cash-coin text-warning me-1"></i> Pagos extras del lote
                                    </h6>
                                    @can('contratos.edit')
                                    @if($lote->estado === 'Abierto')
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            id="btnAbrirPagoExtra-{{ $lote->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalPagoExtra-{{ $lote->id }}">
                                        <i class="bi bi-plus-circle"></i> Registrar pago extra
                                    </button>
                                    @endif
                                    @endcan
                                </div>

                                @php $pagosExtras = $lote->pagosExtras->whereNull('deleted_at'); @endphp
                                @if($pagosExtras->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Cuenta origen</th>
                                                <th>Método</th>
                                                <th class="text-end">Monto</th>
                                                <th class="text-end">Monto Bs.</th>
                                                <th>Descripción</th>
                                                <th>Código</th>
                                                @can('contratos.edit')<th></th>@endcan
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pagosExtras->sortByDesc('fecha') as $pe)
                                            <tr>
                                                <td>{{ $pe->fecha->format('d/m/Y') }}</td>
                                                <td>
                                                    <div>{{ $pe->cuentaOrigen?->nombre_cuenta ?? '—' }}</div>
                                                    <small class="text-muted">{{ $pe->cuentaOrigen?->empresa?->nombre ?? '' }}</small>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">{{ ucfirst($pe->metodo_pago) }}</span></td>
                                                <td class="text-end fw-semibold">
                                                    {{ $pe->moneda }} {{ number_format($pe->monto, 2, ',', '.') }}
                                                    @if($pe->moneda !== 'BOB')
                                                        <br><small class="text-muted">TC: {{ number_format($pe->tipo_cambio, 4, ',', '.') }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold text-success">
                                                    Bs. {{ number_format($pe->monto_bolivianos, 2, ',', '.') }}
                                                </td>
                                                <td>{{ $pe->descripcion ?? '—' }}</td>
                                                <td><small class="text-muted font-monospace">{{ $pe->codigo_seguimiento ?? '—' }}</small></td>
                                                @can('contratos.edit')
                                                <td>
                                                    @if($lote->estado === 'Abierto')
                                                    <form method="POST" action="{{ route('lotes_entrega.pago_extra.destroy', $pe->uuid) }}"
                                                          onsubmit="return confirm('¿Eliminar este pago extra? El movimiento contable también será revertido.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </td>
                                                @endcan
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-semibold">Total Bs.:</td>
                                                <td class="text-end fw-bold text-success">
                                                    Bs. {{ number_format($pagosExtras->sum('monto_bolivianos'), 2, ',', '.') }}
                                                </td>
                                                <td colspan="{{ auth()->user()->can('contratos.edit') ? 3 : 2 }}"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> No hay pagos extras registrados en este lote.</small>
                                @endif

                            </div>
                        </div>
                    </div>

                    {{-- Modal pago extra (solo si el lote está abierto) --}}
                    @can('contratos.edit')
                    @if($lote->estado === 'Abierto')
                    <div class="modal fade" id="modalPagoExtra-{{ $lote->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title">
                                        <i class="bi bi-cash-coin me-1"></i>
                                        Pago extra — {{ $lote->codigo ?? 'Lote #'.$lote->id }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('lotes_entrega.pago_extra.store', $lote->uuid) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="alert alert-light border py-2 mb-3 small">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Proveedor: <strong>{{ $nombreProv }}</strong> —
                                            Semana {{ $lote->numero_semana }}/{{ $lote->anio }}
                                        </div>
                                        <div class="row g-3">
                                            {{-- Cuenta origen --}}
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Cuenta de origen <span class="text-danger">(*)</span></label>
                                                <select name="cuenta_origen_id" class="form-select" required
                                                        id="pe_cuenta_{{ $lote->id }}"
                                                        onchange="peActualizarCuenta({{ $lote->id }}, this)">
                                                    <option value="">— Seleccione cuenta —</option>
                                                    @foreach($empresas as $emp)
                                                        <optgroup label="{{ $emp->nombre }}">
                                                            @foreach($emp->cuentas as $cta)
                                                                <option value="{{ $cta->id }}"
                                                                        data-moneda="{{ $cta->moneda }}"
                                                                        data-saldo="{{ $cta->saldo_actual }}">
                                                                    {{ $cta->nombre_cuenta }}
                                                                    @if($cta->banco) — {{ $cta->banco->nombre }} @endif
                                                                    [{{ $cta->moneda }}]
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                                <div class="pe_saldo_info_{{ $lote->id }} mt-1" style="display:none;">
                                                    <small>Saldo disponible:
                                                        <strong class="pe_saldo_val_{{ $lote->id }}"></strong>
                                                    </small>
                                                </div>
                                            </div>
                                            {{-- Campos dependientes: se habilitan al elegir cuenta --}}
                                            <div id="pe_campos_{{ $lote->id }}" class="col-12 row g-3 m-0 p-0" style="opacity:.45;pointer-events:none;">
                                            {{-- Monto + moneda --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Monto <span class="text-danger">(*)</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text pe_moneda_label_{{ $lote->id }}">BOB</span>
                                                    <input type="text" inputmode="decimal"
                                                           class="form-control pe_monto_display_{{ $lote->id }}"
                                                           placeholder="0,00" autocomplete="off"
                                                           oninput="peFormatearMonto({{ $lote->id }}, this)"
                                                           onblur="peBlurMonto({{ $lote->id }}, this)"
                                                           disabled>
                                                    <input type="hidden" name="monto"
                                                           class="pe_monto_input_{{ $lote->id }}">
                                                </div>
                                                <div class="pe_saldo_warn_{{ $lote->id }} text-danger mt-1 small" style="display:none;">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                    El monto supera el saldo disponible de la cuenta.
                                                </div>
                                            </div>
                                            {{-- Tipo de cambio --}}
                                            <div class="col-md-6 pe_tc_row_{{ $lote->id }}" style="display:none;">
                                                <label class="form-label fw-semibold">Tipo de cambio a BOB <span class="text-danger">(*)</span></label>
                                                <input type="number" step="0.0001" min="0.0001"
                                                       class="form-control pe_tc_display_{{ $lote->id }}"
                                                       placeholder="ej: 6.96" disabled>
                                                <small class="text-muted">1 <span class="pe_moneda_label_{{ $lote->id }}">USD</span> = ? BOB</small>
                                            </div>
                                            <input type="hidden" name="moneda" class="pe_moneda_hidden_{{ $lote->id }}" value="BOB">
                                            <input type="hidden" name="tipo_cambio" class="pe_tc_hidden_{{ $lote->id }}" value="1">
                                            {{-- Fecha --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Fecha <span class="text-danger">(*)</span></label>
                                                <input type="date" name="fecha" class="form-control" required
                                                       value="{{ now()->format('Y-m-d') }}" disabled>
                                            </div>
                                            {{-- Método --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Método de pago <span class="text-danger">(*)</span></label>
                                                <select name="metodo_pago" class="form-select" required
                                                        id="pe_metodo_{{ $lote->id }}"
                                                        onchange="peActualizarMetodo({{ $lote->id }}, this)"
                                                        disabled>
                                                    <option value="">— Seleccione —</option>
                                                    <option value="transferencia">Transferencia</option>
                                                    <option value="qr">QR</option>
                                                </select>
                                            </div>
                                            {{-- Código seguimiento (solo transferencia) --}}
                                            <div class="col-12 pe_cod_row_{{ $lote->id }}" style="display:none;">
                                                <label class="form-label">N° de referencia / código de transferencia</label>
                                                <input type="text" name="codigo_seguimiento" class="form-control"
                                                       placeholder="Dejar vacío para generar automáticamente" maxlength="100" disabled>
                                                <small class="text-muted">Si no ingresa uno se generará automáticamente.</small>
                                            </div>
                                            {{-- Descripción --}}
                                            <div class="col-12">
                                                <label class="form-label">Descripción del pago</label>
                                                <textarea name="descripcion" class="form-control" rows="2" maxlength="500"
                                                          placeholder="Motivo del pago extra, concepto, etc." disabled></textarea>
                                                <small class="text-muted">Aparecerá como concepto en movimientos de tesorería.</small>
                                            </div>
                                            </div>{{-- fin pe_campos --}}
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-warning pe_submit_{{ $lote->id }}">
                                            <i class="bi bi-check-lg"></i> Registrar pago
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endcan

                    @endforeach
                    </div>{{-- fin accordion lotes --}}

                </div>
            </div>
        </div>
        @endforeach
        </div>{{-- fin accordion proveedores --}}

        <div class="d-flex justify-content-center mt-3">
            {{ $lotes->links() }}
        </div>
    @endif

</section>
@endsection

@push('scripts')
<script>
/**
 * Al cambiar método, muestra el código de seguimiento solo para transferencia.
 */
function peActualizarMetodo(loteId, select) {
    const esTransferencia = select.value === 'transferencia';
    document.querySelectorAll('.pe_cod_row_' + loteId)
        .forEach(el => el.style.display = esTransferencia ? '' : 'none');
    // Limpiar el campo si se cambia a QR
    if (!esTransferencia) {
        document.querySelectorAll('.pe_cod_row_' + loteId + ' input')
            .forEach(el => el.value = '');
    }
}

/**
 * Al cambiar cuenta: actualiza moneda, tipo_cambio y muestra saldo disponible.
 */
function peActualizarCuenta(loteId, select) {
    const opt    = select.options[select.selectedIndex];
    const moneda = opt?.dataset?.moneda || 'BOB';
    const saldo  = parseFloat(opt?.dataset?.saldo ?? 'NaN');
    const esBob  = moneda === 'BOB';

    // Moneda label
    document.querySelectorAll('.pe_moneda_label_' + loteId)
        .forEach(el => el.textContent = moneda);

    // Tipo de cambio
    document.querySelectorAll('.pe_tc_row_' + loteId)
        .forEach(el => el.style.display = esBob ? 'none' : '');
    document.querySelectorAll('.pe_moneda_hidden_' + loteId)
        .forEach(el => el.value = moneda);
    if (esBob) {
        document.querySelectorAll('.pe_tc_hidden_' + loteId)
            .forEach(el => el.value = '1');
        document.querySelectorAll('.pe_tc_display_' + loteId)
            .forEach(el => el.required = false);
    } else {
        document.querySelectorAll('.pe_tc_display_' + loteId)
            .forEach(el => { el.required = true; el.value = ''; });
    }

    // Saldo disponible
    const infoEl = document.querySelector('.pe_saldo_info_' + loteId);
    const valEl  = document.querySelector('.pe_saldo_val_' + loteId);
    if (select.value && !isNaN(saldo) && infoEl && valEl) {
        const saldoFmt = saldo.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        valEl.textContent = moneda + ' ' + saldoFmt;
        valEl.className   = 'pe_saldo_val_' + loteId + (saldo <= 0 ? ' text-danger' : ' text-success');
        infoEl.style.display = '';
    } else if (infoEl) {
        infoEl.style.display = 'none';
    }

    // Habilitar / deshabilitar campos dependientes
    const camposDiv = document.getElementById('pe_campos_' + loteId);
    const activo    = !!select.value;
    if (camposDiv) {
        camposDiv.style.opacity      = activo ? '1'    : '.45';
        camposDiv.style.pointerEvents = activo ? 'auto' : 'none';
        camposDiv.querySelectorAll('input, select, textarea').forEach(el => {
            // No tocar los hidden ni el tc_display cuando está oculto
            if (el.type === 'hidden') return;
            el.disabled = !activo;
        });
    }

    // Re-verificar saldo con monto actual
    const montoHidden = document.querySelector('.pe_monto_input_' + loteId);
    peActualizarSaldoRestante(loteId, parseFloat(montoHidden?.value) || 0);
}

/** Parsea texto con formato "1.234,56" → número 1234.56 */
function peParseDisplay(str) {
    if (!str) return NaN;
    // quitar puntos de miles, cambiar coma decimal por punto
    return parseFloat(str.replace(/\./g, '').replace(',', '.'));
}

/** Formatea número → "1.234,56" */
function peFormatNum(num) {
    if (isNaN(num)) return '';
    return num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** Mientras escribe: permite solo dígitos, puntos y coma; actualiza hidden y saldo */
function peFormatearMonto(loteId, displayInput) {
    let raw = displayInput.value;

    // Permitir solo dígitos, punto y coma
    raw = raw.replace(/[^\d.,]/g, '');

    // Solo una coma permitida
    const parts = raw.split(',');
    if (parts.length > 2) raw = parts[0] + ',' + parts.slice(1).join('');

    // Máximo 2 decimales después de la coma
    if (parts.length === 2 && parts[1].length > 2) {
        raw = parts[0] + ',' + parts[1].substring(0, 2);
    }

    displayInput.value = raw;

    // Actualizar hidden con valor numérico limpio
    const num = peParseDisplay(raw);
    const hidden = document.querySelector('.pe_monto_input_' + loteId);
    if (hidden) hidden.value = isNaN(num) ? '' : num.toFixed(2);

    peActualizarSaldoRestante(loteId, num);
}

/** Al salir del campo: autocompletar decimales y formatear miles */
function peBlurMonto(loteId, displayInput) {
    const num = peParseDisplay(displayInput.value);
    if (!isNaN(num) && num > 0) {
        displayInput.value = peFormatNum(num);
        const hidden = document.querySelector('.pe_monto_input_' + loteId);
        if (hidden) hidden.value = num.toFixed(2);
        peActualizarSaldoRestante(loteId, num);
    }
}

/** Actualiza el saldo mostrado restando el monto ingresado, y muestra advertencia */
function peActualizarSaldoRestante(loteId, monto) {
    const select = document.getElementById('pe_cuenta_' + loteId);
    const saldo  = parseFloat(select?.options[select.selectedIndex]?.dataset?.saldo ?? 'NaN');
    const warnEl = document.querySelector('.pe_saldo_warn_' + loteId);
    const valEl  = document.querySelector('.pe_saldo_val_' + loteId);
    const infoEl = document.querySelector('.pe_saldo_info_' + loteId);

    if (isNaN(saldo) || !select?.value) return;

    const montoVal   = isNaN(monto) ? 0 : monto;
    const restante   = saldo - montoVal;
    const moneda     = select.options[select.selectedIndex]?.dataset?.moneda || '';

    if (valEl && infoEl) {
        valEl.textContent  = moneda + ' ' + peFormatNum(restante);
        valEl.className    = 'pe_saldo_val_' + loteId + (restante < 0 ? ' text-danger fw-bold' : ' text-success');
        infoEl.style.display = '';
    }

    if (warnEl) warnEl.style.display = (montoVal > 0 && restante < 0) ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    // Sincronizar tipo_cambio display → hidden
    document.querySelectorAll('[class*="pe_tc_display_"]').forEach(input => {
        const cls    = Array.from(input.classList).find(c => c.startsWith('pe_tc_display_'));
        const loteId = cls?.replace('pe_tc_display_', '');
        if (!loteId) return;
        input.addEventListener('input', () => {
            document.querySelectorAll('.pe_tc_hidden_' + loteId)
                .forEach(h => h.value = input.value || '1');
        });
    });

    document.querySelectorAll('[id^="modalPagoExtra-"]').forEach(modal => {
        const loteId   = modal.id.replace('modalPagoExtra-', '');
        const btnAbrir = document.getElementById('btnAbrirPagoExtra-' + loteId);

        // Deshabilitar botón "Registrar pago extra" mientras el modal está abierto
        modal.addEventListener('show.bs.modal',   () => { if (btnAbrir) btnAbrir.disabled = true;  });
        modal.addEventListener('hidden.bs.modal', () => { if (btnAbrir) btnAbrir.disabled = false; });

        // Bloquear doble envío del formulario interno
        const form      = modal.querySelector('form');
        const submitBtn = modal.querySelector('.pe_submit_' + loteId);
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                if (form.dataset.enviando === '1') {
                    e.preventDefault();
                    return;
                }
                form.dataset.enviando = '1';
                submitBtn.disabled    = true;
                submitBtn.innerHTML   = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Registrando...';
            });
        }
    });

    // Al cerrar el modal: limpiar display monto, saldo y advertencia
    document.querySelectorAll('[id^="modalPagoExtra-"]').forEach(modal => {
        const loteId = modal.id.replace('modalPagoExtra-', '');
        modal.addEventListener('hidden.bs.modal', () => {
            // Limpiar monto
            const display = modal.querySelector('.pe_monto_display_' + loteId);
            const hidden  = modal.querySelector('.pe_monto_input_' + loteId);
            if (display) display.value = '';
            if (hidden)  hidden.value  = '';
            // Ocultar advertencia
            const warn = modal.querySelector('.pe_saldo_warn_' + loteId);
            if (warn) warn.style.display = 'none';
            // Restaurar saldo original
            const select = modal.querySelector('#pe_cuenta_' + loteId);
            if (select) peActualizarSaldoRestante(loteId, 0);
            // Volver a deshabilitar campos dependientes
            const camposDiv = document.getElementById('pe_campos_' + loteId);
            if (camposDiv) {
                camposDiv.style.opacity       = '.45';
                camposDiv.style.pointerEvents = 'none';
                camposDiv.querySelectorAll('input, select, textarea').forEach(el => {
                    if (el.type === 'hidden') return;
                    el.disabled = true;
                });
            }
            // Rehabilitar botón submit por si se cerró sin enviar
            const submitBtn2 = modal.querySelector('.pe_submit_' + loteId);
            if (submitBtn2) {
                submitBtn2.disabled  = false;
                submitBtn2.innerHTML = '<i class="bi bi-check-lg"></i> Registrar pago';
            }
            const form2 = modal.querySelector('form');
            if (form2) delete form2.dataset.enviando;
        });
    });
});
</script>
@endpush
