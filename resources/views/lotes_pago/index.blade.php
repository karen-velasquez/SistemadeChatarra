@extends('layouts.app')
@section('titulo', 'Lotes de Pago Masivo')
@section('content')

<div class="pagetitle">
    <div>
        <h1>LOTES DE PAGO MASIVO</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Lotes de Pago</li>
            </ol>
        </nav>
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
      <table class="table table-hover table-bordered table-sm align-middle">
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
              <span class="badge {{ $lote->tipo === 'proveedor' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                {{ $lote->tipo === 'proveedor' ? 'Proveedor' : 'Camión' }}
              </span>
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
            <td>
              @can('pagos_camiones.create')
              <button class="btn btn-outline-primary btn-sm"
                      onclick="abrirModalCodigo('{{ $lote->uuid }}', '{{ $lote->codigo_real ?? '' }}', '{{ $lote->tipo === 'proveedor' ? 'Proveedor' : 'Camión' }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                <i class="bi bi-pencil-square me-1"></i>
                {{ $lote->codigo_real ? 'Editar código' : 'Ingresar código' }}
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
        <div class="modal-body">
          <p class="small text-muted mb-2" id="lbl_info_lote"></p>
          <label class="form-label fw-semibold">Código real del banco</label>
          <input type="text" class="form-control" name="codigo_real" id="input_codigo_real"
                 placeholder="Ej: TRF-2026052500123" required maxlength="100">
          <small class="text-muted">Este código reemplazará el provisional en todos los registros del lote.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function abrirModalCodigo(uuid, codigoActual, tipo, fecha) {
    document.getElementById('form_codigo').action = `/lotes-pago/${uuid}/codigo`;
    document.getElementById('input_codigo_real').value = codigoActual;
    document.getElementById('lbl_info_lote').textContent = `Lote ${tipo} — ${fecha}`;
    new bootstrap.Modal(document.getElementById('modalCodigo')).show();
    setTimeout(() => document.getElementById('input_codigo_real').focus(), 400);
}
</script>
@endsection
