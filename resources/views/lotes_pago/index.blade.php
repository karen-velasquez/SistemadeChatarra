@extends('layouts.app')
@section('titulo', 'Lotes de Pago Masivo')
@section('content')

<div class="pagetitle">
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <h1>LOTES DE PAGO MASIVO</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Lotes de Pago</li>
                </ol>
            </nav>
        </div>
        <button type="button"
                class="btn btn-outline-primary btn-sm btn-iniciar-tour"
                @if($lotes->isEmpty())
                data-steps='[
                    {"intro":"📦 Un <b>Lote de Pago</b> agrupa todos los pagos que generaste en un <b>pago masivo</b> (a proveedores o camiones).<br><br>📭 Aún no hay lotes. Aparecerán aquí cuando hagas un pago masivo desde Proveedores o Transporte."}
                ]'
                @else
                data-steps='[
                    {"intro":"📦 Cada <b>lote</b> agrupa los pagos de un pago masivo. Aquí registras el <b>código real</b> que te da el banco cuando confirma la transferencia, y se aplica a todos los pagos del lote."},
                    {"element":"#lp-tabla","intro":"📋 La lista de lotes: fecha, tipo (proveedor o camión), cuenta de origen, método y los códigos.","position":"top"},
                    {"element":"#lp-tabla thead th:nth-child(5)","intro":"🔖 El <b>código provisional</b> lo genera el sistema al crear el lote. El <b>código real</b> es el que confirma el banco después.","position":"bottom"},
                    {"element":"#lp-tabla thead th:nth-child(7)","intro":"🚦 El <b>Estado</b>: <b>Pendiente</b> mientras no haya código real, <b>Confirmado</b> cuando lo registras.","position":"bottom"},
                    {"element":"#lp-tabla tbody tr:first-child td:last-child","intro":"✏️ Con este botón <b>ingresas o editas el código real</b> del banco para ese lote.","position":"left"}
                ]'
                @endif>
            <i class="bi bi-question-circle"></i>
        </button>
    </div>
</div>

<section class="section">
<div class="row">
<div class="col-12">
<div class="card">
  <div class="card-body">
    <h5 class="card-title">Historial de Lotes</h5>
    <p class="text-muted small mb-3">
      <i class="bi bi-info-circle me-1"></i>
      Cada lote agrupa los pagos generados en un pago masivo. Una vez que el banco confirme la transferencia,
      ingrese el <strong>código real</strong> y se actualizará en todos los registros del lote.
    </p>

    @if($lotes->isEmpty())
      <div class="alert alert-info py-2">
        <i class="bi bi-info-circle"></i> No hay lotes de pago registrados aún.
      </div>
    @else
    <div class="table-responsive">
      <table id="lp-tabla" class="table table-hover table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Cuenta origen</th>
            <th>Método</th>
            <th>Código provisional</th>
            <th>Código real (banco)</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($lotes as $lote)
          <tr>
            <td><small>{{ $lote->fecha_pago->format('d/m/Y') }}</small></td>
            <td>
              @php
                $badgeTipo = match($lote->tipo) {
                    'proveedor' => 'bg-info text-dark',
                    'cliente'   => 'bg-success',
                    default     => 'bg-warning text-dark',
                };
                $labelTipo = match($lote->tipo) {
                    'proveedor' => 'Proveedor',
                    'cliente'   => 'Cliente',
                    default     => 'Camión',
                };
              @endphp
              <span class="badge {{ $badgeTipo }}">{{ $labelTipo }}</span>
            </td>
            <td>
              <small>{{ $lote->cuentaOrigen?->empresa?->nombre ?? '—' }}</small>
              @if($lote->cuentaOrigen?->alias)
                <small class="text-muted d-block">({{ $lote->cuentaOrigen->alias }})</small>
              @endif
            </td>
            <td><small>{{ ucfirst($lote->metodo_pago) }}</small></td>
            <td><code class="small text-muted">{{ $lote->codigo_provisional }}</code></td>
            <td>
              @if($lote->codigo_real)
                <code class="small text-success fw-bold">{{ $lote->codigo_real }}</code>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              @if($lote->codigo_real)
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Confirmado</span>
              @else
                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendiente</span>
              @endif
            </td>
            <td class="text-nowrap">
              @can('pagos_camiones.create')
              <button class="btn btn-outline-primary btn-sm"
                      onclick="abrirModalCodigo('{{ $lote->uuid }}', '{{ $lote->codigo_real ?? $lote->codigo_provisional ?? '' }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                <i class="bi bi-pencil-square me-1"></i>
                {{ $lote->codigo_real ? 'Editar código' : 'Ingresar código' }}
              </button>
              @endcan
              @can('pagos_camiones.destroy')
              <button class="btn btn-outline-danger btn-sm"
                      onclick="abrirModalEliminarLote('{{ $lote->uuid }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                <i class="bi bi-trash me-1"></i>Eliminar
              </button>
              @endcan
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $lotes->links() }}</div>
    @endif
  </div>
</div>
</div>
</div>
</section>

{{-- Modal para ingresar/editar código real --}}
<div class="modal fade" id="modalCodigo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-hash me-2"></i>Código de transferencia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form_codigo" method="POST">
        @csrf
        <input type="hidden" id="input_lote_uuid" value="">
        <div class="modal-body">
          <p class="small text-muted mb-2" id="lbl_info_lote"></p>
          <label class="form-label fw-semibold">Código real del banco</label>
          <input type="text" class="form-control" name="codigo_real" id="input_codigo_real"
                 placeholder="Ej: TRF-2026052500123" required maxlength="100">
          <div class="invalid-feedback d-block" id="codigo_real_feedback" style="display:none !important;"></div>
          <small class="text-muted">Este código reemplazará el provisional en todos los registros del lote.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn_guardar_codigo">
            <i class="bi bi-save me-1"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal de confirmación para eliminar lote --}}
<div class="modal fade" id="modalEliminarLote" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar lote de pago</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" id="del_lbl_info_lote"></p>
        <div id="del_body">
          <div class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando pagos del lote...
          </div>
        </div>
        <div class="alert alert-warning small py-2 mb-0 mt-3">
          <i class="bi bi-info-circle me-1"></i>
          Se eliminarán todos los pagos listados y se revertirán sus movimientos en tesorería. Esta acción no se puede deshacer desde aquí.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form id="form_eliminar_lote" method="POST">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" id="btn_confirmar_eliminar_lote">
            <i class="bi bi-trash me-1"></i>Sí, eliminar lote
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function abrirModalCodigo(uuid, codigoActual, tipo, fecha) {
    document.getElementById('form_codigo').action = `${url_global}/lotes-pago/${uuid}/codigo`;
    document.getElementById('input_lote_uuid').value = uuid;
    document.getElementById('input_codigo_real').value = codigoActual;
    document.getElementById('input_codigo_real').classList.remove('is-invalid');
    document.getElementById('codigo_real_feedback').style.display = 'none';
    document.getElementById('lbl_info_lote').textContent = `Lote ${tipo} — ${fecha}`;
    new bootstrap.Modal(document.getElementById('modalCodigo')).show();
    setTimeout(() => document.getElementById('input_codigo_real').focus(), 400);
}

// Verificar código duplicado al hacer clic en Guardar (no en cada tecla,
// para no saturar de consultas), y solo entonces enviar el formulario.
document.getElementById('form_codigo').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn      = document.getElementById('btn_guardar_codigo');
    const input    = document.getElementById('input_codigo_real');
    const feedback = document.getElementById('codigo_real_feedback');
    const codigo   = input.value.trim();

    input.classList.remove('is-invalid');
    feedback.style.display = 'none';

    if (!codigo) return;

    btn.disabled = true;
    const loteUuid = document.getElementById('input_lote_uuid').value;
    const params = new URLSearchParams({ codigo, lote_uuid: loteUuid });

    fetch(`${url_global}/api/lotes-pago/verificar-codigo?${params}`)
        .then(r => r.json())
        .then(d => {
            if (!d.disponible) {
                input.classList.add('is-invalid');
                feedback.textContent = 'Este código ya está en uso por otro pago o lote. Ingresa uno distinto.';
                feedback.style.display = 'block';
                btn.disabled = false;
                return;
            }
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
            this.submit();
        });
});

// ===== Eliminar lote: modal con detalle de pagos antes de confirmar =====
const _fmtLote = (n, mon) => `${mon} ${new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n)}`;

function abrirModalEliminarLote(uuid, tipo, fecha) {
    document.getElementById('del_lbl_info_lote').textContent = `Lote ${tipo} — ${fecha}`;
    document.getElementById('form_eliminar_lote').action = `${url_global}/lotes-pago/${uuid}`;
    document.getElementById('del_body').innerHTML = `
        <div class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando pagos del lote...
        </div>`;
    document.getElementById('btn_confirmar_eliminar_lote').disabled = true;

    new bootstrap.Modal(document.getElementById('modalEliminarLote')).show();

    fetch(`${url_global}/api/lotes-pago/${uuid}/detalle`)
        .then(r => r.json())
        .then(d => {
            let html = `<p class="small text-muted mb-2">Se eliminarán <strong>${d.total_pagos}</strong> pago(s):</p>`;
            if (d.pagos.length > 0) {
                html += `<div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Referencia</th><th class="text-end">Monto</th><th>Fecha</th></tr></thead>
                        <tbody>`;
                d.pagos.forEach(p => {
                    html += `<tr><td>${p.referencia}</td><td class="text-end">${_fmtLote(p.monto, p.moneda)}</td><td>${p.fecha}</td></tr>`;
                });
                html += `</tbody></table></div>`;
            }
            document.getElementById('del_body').innerHTML = html;
            document.getElementById('btn_confirmar_eliminar_lote').disabled = false;
        })
        .catch(() => {
            document.getElementById('del_body').innerHTML = '<div class="alert alert-danger py-2 mb-0">Error al cargar el detalle del lote.</div>';
        });
}
</script>
@endsection
