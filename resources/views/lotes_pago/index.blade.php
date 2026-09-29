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
                    {"intro":"📦 Cada <b>lote</b> agrupa los pagos de un pago masivo. El banco da un <b>código real</b> distinto por cada transferencia, así que lo registras pago por pago desde las Opciones del lote."},
                    {"element":"#lp-tabla","intro":"📋 La lista de lotes: fecha, tipo (proveedor o camión), cuenta de origen, método y los códigos.","position":"top"},
                    {"element":"#lp-tabla thead th:nth-child(5)","intro":"🔖 El <b>código provisional</b> lo genera el sistema al crear el lote y agrupa sus pagos. La columna <b>código real</b> muestra cuántos pagos del lote ya tienen el código que dio el banco.","position":"bottom"},
                    {"element":"#lp-tabla thead th:nth-child(7)","intro":"🚦 El <b>Estado</b>: <b>Pendiente</b> si ningún pago tiene código, <b>Parcial</b> si solo algunos, <b>Confirmado</b> cuando todos los pagos del lote ya tienen su código real.","position":"bottom"},
                    {"element":"#lp-tabla tbody tr:first-child td:last-child","intro":"⚙️ El botón <b>Opciones</b> de cada lote: ver el Excel formato banco, ingresar/editar el código real, o eliminar el lote.","position":"left"}
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
            <th>Contratos / Referencias</th>
            <th class="text-end">Monto total</th>
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
              @if($lote->resumen_referencias->isEmpty())
                <span class="text-muted small">—</span>
              @else
                <small>
                  {{ $lote->resumen_referencias->take(2)->implode(' · ') }}
                  @if($lote->resumen_referencias->count() > 2)
                    <span class="text-muted">+{{ $lote->resumen_referencias->count() - 2 }} más</span>
                  @endif
                </small>
              @endif
            </td>
            <td class="text-end">
              <small class="fw-semibold">{{ $lote->resumen_moneda }} {{ number_format($lote->resumen_monto, 2, ',', '.') }}</small>
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
              @if($lote->con_codigo_real > 0)
                <span class="small text-success fw-bold">{{ $lote->con_codigo_real }}/{{ $lote->total_pagos }} pago(s) con código</span>
              @else
                <span class="text-muted small">—</span>
              @endif
            </td>
            <td>
              @if($lote->estado_codigo === 'confirmado')
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Confirmado</span>
              @elseif($lote->estado_codigo === 'parcial')
                <span class="badge bg-info text-dark"><i class="bi bi-hourglass-split me-1"></i>Parcial ({{ $lote->con_codigo_real }}/{{ $lote->total_pagos }})</span>
              @else
                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendiente</span>
              @endif
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-list-ul"></i> Opciones
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  @if($lote->tipo !== 'cliente')
                  <li>
                    <button class="dropdown-item" onclick="abrirModalExcelLote('{{ $lote->uuid }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                      <i class="bi bi-file-earmark-excel text-success me-2"></i> Ver Excel
                    </button>
                  </li>
                  @endif
                  @can('lotes_pago.edit')
                  <li>
                    <button class="dropdown-item" onclick="abrirModalCodigo('{{ $lote->uuid }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                      <i class="bi bi-pencil-square text-primary me-2"></i> Códigos reales por pago
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" onclick="abrirModalFecha('{{ $lote->uuid }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('Y-m-d') }}')">
                      <i class="bi bi-calendar-event text-primary me-2"></i> Editar fecha
                    </button>
                  </li>
                  @endcan
                  @can('lotes_pago.destroy')
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <button class="dropdown-item" onclick="abrirModalEliminarLote('{{ $lote->uuid }}', '{{ $labelTipo }}', '{{ $lote->fecha_pago->format('d/m/Y') }}')">
                      <i class="bi bi-trash text-danger me-2"></i> Eliminar
                    </button>
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
    <div class="mt-2">{{ $lotes->links() }}</div>
    @endif
  </div>
</div>
</div>
</div>
</section>

{{-- Modal para ingresar/editar el código real por cada pago del lote --}}
<div class="modal fade" id="modalCodigo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-hash me-2"></i>Códigos reales del banco</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form_codigo" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <p class="small text-muted mb-2" id="lbl_info_lote"></p>
          <p class="small text-muted mb-3">
            <i class="bi bi-info-circle me-1"></i>
            El banco entrega un código distinto por cada transferencia. Ingresa el que corresponda a cada pago
            y adjunta su voucher si lo tienes; los que dejes vacíos quedan pendientes y puedes completarlos después.
          </p>
          <div id="codigo_body">
            <div class="text-center text-muted py-3">
              <span class="spinner-border spinner-border-sm me-1"></span>Cargando pagos del lote...
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn_guardar_codigo" disabled>
            <i class="bi bi-save me-1"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal de edición de fecha del lote --}}
<div class="modal fade" id="modalFecha" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>Editar fecha del lote</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form_fecha" method="POST">
        @csrf
        <div class="modal-body">
          <p class="small text-muted mb-3" id="lbl_info_lote_fecha"></p>
          <div class="alert alert-warning small py-2 mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Se actualizará la fecha del lote, de todos sus pagos individuales y de los movimientos de tesorería asociados.
          </div>
          <label class="form-label">Nueva fecha <span class="text-danger">(*)</span></label>
          <input type="date" name="fecha_pago" id="input_fecha_lote" class="form-control" required>
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

{{-- Modal de vista previa / descarga del Excel formato banco --}}
<div class="modal fade" id="modalExcelLote" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Excel del lote</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-2" id="excel_lbl_info_lote"></p>
        <div class="mb-3">
          <button class="btn btn-outline-success btn-sm" id="btn_descargar_excel_lote" onclick="descargarExcelLote()" disabled>
            <i class="bi bi-download me-1"></i>Descargar Excel
          </button>
        </div>
        <div id="excel_body">
          <div class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando datos del lote...
          </div>
        </div>
        <div class="mt-2 text-end" id="excel_lbl_total"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
function abrirModalFecha(uuid, tipo, fechaISO) {
    document.getElementById('form_fecha').action = `${url_global}/lotes-pago/${uuid}/fecha`;
    document.getElementById('lbl_info_lote_fecha').textContent = `Lote ${tipo}`;
    document.getElementById('input_fecha_lote').value = fechaISO;

    new bootstrap.Modal(document.getElementById('modalFecha')).show();
}

function abrirModalCodigo(uuid, tipo, fecha) {
    document.getElementById('form_codigo').action = `${url_global}/lotes-pago/${uuid}/codigo`;
    document.getElementById('lbl_info_lote').textContent = `Lote ${tipo} — ${fecha}`;
    document.getElementById('codigo_body').innerHTML = `
        <div class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando pagos del lote...
        </div>`;
    document.getElementById('btn_guardar_codigo').disabled = true;

    new bootstrap.Modal(document.getElementById('modalCodigo')).show();

    fetch(`${url_global}/api/lotes-pago/${uuid}/detalle`)
        .then(r => r.json())
        .then(d => {
            if (d.pagos.length === 0) {
                document.getElementById('codigo_body').innerHTML = '<div class="alert alert-info py-2 mb-0">Este lote no tiene pagos.</div>';
                return;
            }
            let html = `<div class="table-responsive"><table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>Referencia</th><th class="text-end">Monto</th><th>Fecha</th><th style="width:220px">Código real</th><th style="width:160px">Voucher</th></tr></thead>
                <tbody>`;
            let totalMonto = 0;
            const moneda = d.pagos[0]?.moneda ?? '';
            d.pagos.forEach((p, i) => {
                totalMonto += Number(p.monto);
                html += `<tr>
                    <td>${p.referencia}</td>
                    <td class="text-end">${_fmtLote(p.monto, p.moneda)}</td>
                    <td>${p.fecha}</td>
                    <td>
                        <input type="hidden" name="codigos[${i}][pago_uuid]" value="${p.uuid}">
                        <input type="text" class="form-control form-control-sm" name="codigos[${i}][codigo_real]"
                               value="${p.codigo ?? ''}" placeholder="Ej: TRF-2026052500123" maxlength="100">
                    </td>
                    <td>
                        ${p.tiene_voucher ? `<a href="${p.voucher_url}" target="_blank" class="btn btn-outline-success btn-sm mb-1 w-100"><i class="bi bi-file-earmark-check me-1"></i>Ver</a>` : ''}
                        <input type="file" class="form-control form-control-sm" name="codigos[${i}][voucher]" accept=".jpg,.jpeg,.png,.pdf">
                    </td>
                </tr>`;
            });
            html += `<tr class="table-light fw-bold">
                    <td class="text-end">TOTAL</td>
                    <td class="text-end">${_fmtLote(totalMonto, moneda)}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>`;
            html += `</tbody></table></div>`;
            document.getElementById('codigo_body').innerHTML = html;
            document.getElementById('btn_guardar_codigo').disabled = false;
        })
        .catch(() => {
            document.getElementById('codigo_body').innerHTML = '<div class="alert alert-danger py-2 mb-0">Error al cargar los pagos del lote.</div>';
        });
}

document.getElementById('form_codigo').addEventListener('submit', function () {
    const btn = document.getElementById('btn_guardar_codigo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
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

// ===== Ver / descargar Excel formato banco de un lote (proveedor o camión) =====
let _excelLoteCols  = [];
let _excelLoteFilas = [];
let _excelLoteNombre = '';

function abrirModalExcelLote(uuid, tipo, fecha) {
    document.getElementById('excel_lbl_info_lote').textContent = `Lote ${tipo} — ${fecha}`;
    document.getElementById('excel_body').innerHTML = `
        <div class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm me-1"></span>Cargando datos del lote...
        </div>`;
    document.getElementById('excel_lbl_total').textContent = '';
    document.getElementById('btn_descargar_excel_lote').disabled = true;
    _excelLoteNombre = `lote_${tipo.toLowerCase()}_${fecha.replace(/\//g, '')}`;

    new bootstrap.Modal(document.getElementById('modalExcelLote')).show();

    fetch(`${url_global}/api/lotes-pago/${uuid}/excel`)
        .then(r => r.json())
        .then(d => {
            _excelLoteCols  = d.cols;
            _excelLoteFilas = d.filas;

            let html = `<div class="table-responsive"><table class="table table-bordered table-sm" style="font-size:.8rem">
                <thead><tr style="background:#1d7a3a;color:#fff;white-space:nowrap">`;
            d.cols.forEach(c => html += `<th>${c}</th>`);
            html += `</tr></thead><tbody>`;
            d.filas.forEach(fila => {
                html += '<tr>' + fila.map(v => `<td>${v ?? ''}</td>`).join('') + '</tr>';
            });
            html += `</tbody></table></div>`;

            document.getElementById('excel_body').innerHTML = html;
            document.getElementById('excel_lbl_total').innerHTML = `<strong>Total: <span class="text-success">${_fmtLote(d.total, d.moneda)}</span></strong>`;
            document.getElementById('btn_descargar_excel_lote').disabled = d.filas.length === 0;
        })
        .catch(() => {
            document.getElementById('excel_body').innerHTML = '<div class="alert alert-danger py-2 mb-0">Error al cargar el Excel del lote.</div>';
        });
}

function descargarExcelLote() {
    _exportarXlsxLote(_excelLoteCols, _excelLoteFilas, 'Hoja 1', `${_excelLoteNombre}.xlsx`);
}

// ---- Generador XLSX con cabecera estilizada (fondo verde oscuro + texto blanco + negrita) ----
function _exportarXlsxLote(headers, rows, sheetName, filename) {
    const esc = v => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

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
</script>
@endsection
