@extends('layouts.app')
@section('titulo', 'Liquidación de Envíos')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>LIQUIDACIÓN DE ENVÍOS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('contratos.index') }}">Contratos</a></li>
                    <li class="breadcrumb-item active">Liquidación</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                @if($porProveedor->isEmpty())
                data-steps='[
                    {"intro":"🧮 Esta es la <b>Liquidación de Envíos</b>. Aquí podrás comparar, por proveedor, lo <b>pactado</b>, lo <b>declarado</b> y lo que realmente <b>llegó</b> al cliente.<br><br>📭 Por ahora <b>no hay contratos con envíos cerrados</b>, así que no hay datos que mostrar. Cuando cierres los envíos de un contrato, aparecerán aquí y la guía te mostrará cada parte."}
                ]'
                @else
                data-steps='[
                    {"intro":"🧮 Esta es la <b>Liquidación de Envíos</b>. Solo aparecen los contratos con <b>envíos cerrados</b>. Sirve para saber si el proveedor entregó lo acordado o hubo diferencias de peso."},
                    {"element":"#liq-explicacion","intro":"📖 Lee esto primero: se comparan <b>3 pesos</b> — lo <b>pactado</b>, lo <b>declarado</b> por el proveedor y lo que realmente <b>llegó</b> al cliente.","position":"bottom"},
                    {"element":"#liq-proveedor-cabecera","intro":"📦 Los contratos se agrupan por <b>proveedor</b>. A la derecha ves su <b>balance neto</b>: cuánto debe o se le debe en total.","position":"bottom"},
                    {"element":"#liq-resumen","intro":"📊 Este resumen suma todo del proveedor: total pactado, declarado, llegado y la diferencia neta.","position":"bottom"},
                    {"element":"#liq-tabla","intro":"📋 El detalle contrato por contrato. Cada fila compara los pesos y muestra la diferencia.","position":"top"},
                    {"element":"#liq-resultado","intro":"🏷️ La columna <b>Resultado</b>: <span style=\"color:#dc3545\"><b>Merma</b></span> = faltó respecto a lo pactado; <span style=\"color:#198754\"><b>Excedente</b></span> = llegó más de lo pactado.","position":"left"}
                ]'
                @endif>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
    <div class="row">
        <div class="col-lg-12">

            @if($porProveedor->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size:3rem;opacity:.3"></i>
                        <p class="mt-3">No hay contratos con envíos cerrados.</p>
                        <a href="{{ route('contratos.index') }}" class="btn btn-secondary btn-sm">Volver a Contratos</a>
                    </div>
                </div>
            @else

            <p class="text-muted small mb-3" id="liq-explicacion">
                <i class="bi bi-info-circle me-1"></i>
                Compara 3 valores por contrato: <strong>toneladas pactadas</strong> (lo acordado), <strong>declaradas por el proveedor</strong> (peso de salida de cada camión) y <strong>llegadas al cliente</strong> (peso de llegada confirmado).
                Una diferencia <span class="text-danger fw-semibold">negativa (merma)</span> significa que el proveedor nos debe,
                una <span class="text-success fw-semibold">positiva (excedente)</span> significa que nosotros le debemos al proveedor.
            </p>

            @foreach($porProveedor as $provId => $grupo)
            @php
                $prov     = $grupo['proveedor'];
                $ctrs     = $grupo['contratos'];
                $neta     = $grupo['diferencia_neta'];
                $pactado  = $grupo['total_pactado'];
                $entregado= $grupo['total_entregado'];
            @endphp

            <div class="card mb-4 border-0 shadow-sm">
                {{-- Cabecera del proveedor --}}
                <div class="card-header d-flex align-items-center gap-3 py-3"
                     @if($loop->first) id="liq-proveedor-cabecera" @endif
                     style="background:linear-gradient(135deg,#1a3a5c 0%,#1976d2 100%);">
                    <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-box-seam text-white" style="font-size:1.2rem"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-0 text-white fw-bold">{{ $prov->nombre ?? 'Proveedor #'.$provId }}</h5>
                        <small class="text-white opacity-75">{{ $ctrs->count() }} contrato(s) con envíos cerrados</small>
                    </div>
                    {{-- Balance neto del proveedor --}}
                    <div class="text-end">
                        <div class="text-white opacity-75" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Balance neto</div>
                        @if($neta < 0)
                            <span class="badge bg-danger fs-6">
                                <i class="bi bi-arrow-down-circle me-1"></i>{{ number_format(abs($neta), 3) }} t merma
                            </span>
                        @elseif($neta > 0)
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-arrow-up-circle me-1"></i>{{ number_format($neta, 3) }} t excedente
                            </span>
                        @else
                            <span class="badge bg-secondary fs-6">
                                <i class="bi bi-check-circle me-1"></i>Sin diferencia
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Resumen del proveedor --}}
                <div class="d-flex border-bottom" @if($loop->first) id="liq-resumen" @endif style="background:#f8f9fa">
                    <div class="flex-fill text-center py-2 px-3 border-end">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Total pactado</div>
                        <div class="fw-bold" style="font-size:1rem">{{ number_format($grupo['total_pactado'], 3) }} t</div>
                    </div>
                    <div class="flex-fill text-center py-2 px-3 border-end">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Total declarado</div>
                        <div class="fw-bold" style="font-size:1rem;color:#1976d2">{{ number_format($grupo['total_declarado'], 3) }} t</div>
                    </div>
                    <div class="flex-fill text-center py-2 px-3 border-end">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Total llegado</div>
                        <div class="fw-bold" style="font-size:1rem">{{ number_format($grupo['total_entregado'], 3) }} t</div>
                    </div>
                    <div class="flex-fill text-center py-2 px-3">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Diferencia neta</div>
                        <div class="fw-bold {{ $neta < 0 ? 'text-danger' : ($neta > 0 ? 'text-success' : 'text-muted') }}" style="font-size:1rem">
                            {{ $neta >= 0 ? '+' : '' }}{{ number_format($neta, 3) }} t
                        </div>
                    </div>
                </div>

                {{-- Detalle por contrato --}}
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0 align-middle" @if($loop->first) id="liq-tabla" @endif style="font-size:.85rem">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Contrato</th>
                                <th>Fecha cierre</th>
                                <th class="text-end">Ton. pactadas</th>
                                <th class="text-end">Ton. declaradas</th>
                                <th class="text-end">Ton. llegadas</th>
                                <th class="text-end">Dif. (pactado - llegado)</th>
                                <th class="text-end">Dif. (dec. - llegado)</th>
                                <th class="text-center" @if($loop->first) id="liq-resultado" @endif>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ctrs as $c)
                            @php
                                $diff       = $c->diferencia_liquidacion;
                                $diffPactado = $c->diferencia_pactado_llegado;
                                $declaradas = $c->toneladas_declaradas;
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold">{{ $c->numero_contrato }}</span>
                                    <small class="text-muted d-block">
                                        {{ $c->tipo_contrato }} —
                                        {{ $c->fecha_inicio?->format('d/m/Y') }} al {{ $c->fecha_fin?->format('d/m/Y') ?? 'sin fin' }}
                                    </small>
                                </td>
                                <td>
                                    <small>{{ $c->envios_cerrados_at?->format('d/m/Y H:i') ?? '—' }}</small>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($c->toneladas_contrato, 3) }} t</td>
                                <td class="text-end" style="color:#1976d2">{{ number_format($declaradas, 3) }} t</td>
                                <td class="text-end">{{ number_format($c->toneladas_entregadas, 3) }} t</td>
                                <td class="text-end fw-bold {{ $diffPactado < 0 ? 'text-danger' : ($diffPactado > 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $diffPactado >= 0 ? '+' : '' }}{{ number_format($diffPactado, 3) }} t
                                </td>
                                <td class="text-end fw-bold {{ $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 3) }} t
                                </td>
                                <td class="text-center">
                                    @if($diffPactado < 0)
                                        <span class="badge bg-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Merma
                                        </span>
                                        <small class="text-muted d-block" style="font-size:.65rem">Faltan {{ number_format(abs($diffPactado), 3) }} t de lo pactado</small>
                                    @elseif($diffPactado > 0)
                                        <span class="badge bg-success">
                                            <i class="bi bi-plus-circle me-1"></i>Excedente
                                        </span>
                                        <small class="text-muted d-block" style="font-size:.65rem">Llegaron {{ number_format($diffPactado, 3) }} t más de lo pactado</small>
                                    @else
                                        <span class="badge bg-secondary">Exacto</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        {{-- Subtotal del proveedor --}}
                        <tfoot>
                            <tr style="background:#f0f4ff;font-weight:600">
                                <td class="ps-3" colspan="2">Subtotal {{ $prov->nombre ?? '' }}</td>
                                <td class="text-end">{{ number_format($grupo['total_pactado'], 3) }} t</td>
                                <td class="text-end" style="color:#1976d2">{{ number_format($grupo['total_declarado'], 3) }} t</td>
                                <td class="text-end">{{ number_format($grupo['total_entregado'], 3) }} t</td>
                                <td class="text-end {{ $grupo['diferencia_pactado_llegado'] < 0 ? 'text-danger' : ($grupo['diferencia_pactado_llegado'] > 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $grupo['diferencia_pactado_llegado'] >= 0 ? '+' : '' }}{{ number_format($grupo['diferencia_pactado_llegado'], 3) }} t
                                </td>
                                <td class="text-end {{ $neta < 0 ? 'text-danger' : ($neta > 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $neta >= 0 ? '+' : '' }}{{ number_format($neta, 3) }} t
                                </td>
                                <td class="text-center">
                                    @if($grupo['diferencia_pactado_llegado'] < 0)
                                        <span class="badge bg-danger">Merma neta</span>
                                    @elseif($grupo['diferencia_pactado_llegado'] > 0)
                                        <span class="badge bg-success">Excedente neto</span>
                                    @else
                                        <span class="badge bg-secondary">Compensado</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach

            @endif
        </div>
    </div>
</section>
@endsection
