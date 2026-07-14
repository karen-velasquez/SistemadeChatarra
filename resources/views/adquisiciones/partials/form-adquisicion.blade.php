{{-- Campos del formulario de adquisición (usado en crear y editar; $a opcional) --}}
@php $a = $a ?? null; @endphp
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Descripción del bien <span class="text-danger">*</span></label>
        <input type="text" name="descripcion" class="form-control" required maxlength="255"
               placeholder="Ej: Camión Volvo FH 2020" value="{{ $a->descripcion ?? '' }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tipo de bien</label>
        <select name="tipo_bien_id" class="form-select" onchange="toggleCamionVinculado(this)">
            <option value="">-- Seleccione --</option>
            @foreach($tiposBien as $tb)
                <option value="{{ $tb->id }}" data-valor="{{ $tb->valor }}"
                        @selected(($a->tipo_bien_id ?? null) == $tb->id)>{{ $tb->descripcion }}</option>
            @endforeach
        </select>
        <div class="form-text">Catálogo gestionado en Parámetros (grupo bien_tipo).</div>
    </div>
    <div class="col-md-4 solo-camiones" style="display:none">
        <label class="form-label">Unidad propia vinculada</label>
        <select name="camion_id" class="form-select">
            <option value="">-- Ninguna --</option>
            @foreach($camiones as $c)
                <option value="{{ $c->id }}" @selected(($a->camion_id ?? null) == $c->id)>{{ $c->placa }} — {{ $c->modelo }}</option>
            @endforeach
        </select>
        <div class="form-text">Solo si el bien es un camión de la empresa.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Origen <span class="text-danger">*</span></label>
        <select name="origen" class="form-select" required onchange="toggleExterior(this)">
            <option value="NACIONAL" @selected(($a->origen ?? 'NACIONAL') === 'NACIONAL')>Nacional</option>
            <option value="EXTERIOR" @selected(($a->origen ?? '') === 'EXTERIOR')>Exterior (importación)</option>
        </select>
    </div>
    <div class="col-md-4 solo-exterior" style="display:none">
        <label class="form-label">País de origen</label>
        <select name="pais_origen_id" class="form-select">
            <option value="">-- Seleccione --</option>
            @foreach($paisesOrigen as $p)
                <option value="{{ $p->id }}" @selected(($a->pais_origen_id ?? null) == $p->id)>{{ $p->descripcion }}</option>
            @endforeach
        </select>
        <div class="form-text">Catálogo gestionado en Parámetros (grupo pais_exportacion).</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Vendedor / Beneficiario de los pagos</label>
        <input type="text" name="vendedor" class="form-control" maxlength="255"
               placeholder="A quién se le paga (vendedor, cuenta destino…)" value="{{ $a->vendedor ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Financiamiento <span class="text-danger">*</span></label>
        <select name="financiamiento" class="form-select" required onchange="toggleCredito(this)">
            <option value="CAPITAL" @selected(($a->financiamiento ?? '') === 'CAPITAL')>Capital de la empresa</option>
            <option value="CREDITO" @selected(($a->financiamiento ?? '') === 'CREDITO')>Crédito</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Fecha de adquisición <span class="text-danger">*</span></label>
        <input type="date" name="fecha_adquisicion" class="form-control" required
               value="{{ isset($a) ? $a->fecha_adquisicion->format('Y-m-d') : date('Y-m-d') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Moneda <span class="text-danger">*</span></label>
        <select name="moneda" class="form-select" required>
            @foreach(['BOB' => 'Bolivianos (BOB)', 'USD' => 'Dólares (USD)', 'BRL' => 'Reales (BRL)', 'EUR' => 'Euros (EUR)'] as $cod => $label)
                <option value="{{ $cod }}" @selected(($a->moneda ?? 'BOB') === $cod)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">Moneda pactada con el vendedor o banco.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Monto total <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" name="monto_total" class="form-control" required
               value="{{ $a->monto_total ?? '' }}">
    </div>
    <div class="col-md-3 solo-credito" style="display:none">
        <label class="form-label">Entidad financiera</label>
        <input type="text" name="entidad_financiera" class="form-control" maxlength="150"
               placeholder="Ej: Banco Unión" value="{{ $a->entidad_financiera ?? '' }}">
    </div>
    <div class="col-md-3 solo-credito" style="display:none">
        <label class="form-label">Tipo de interés</label>
        <select name="tipo_interes" class="form-select">
            <option value="">-- Sin interés --</option>
            <option value="FIJO" @selected(($a->tipo_interes ?? '') === 'FIJO')>Fijo</option>
            <option value="VARIABLE" @selected(($a->tipo_interes ?? '') === 'VARIABLE')>Variable</option>
        </select>
    </div>
    <div class="col-md-2 solo-credito" style="display:none">
        <label class="form-label">Tasa % anual</label>
        <input type="number" step="0.001" min="0" max="100" name="tasa_interes" class="form-control"
               value="{{ $a->tasa_interes ?? '' }}">
    </div>
    <div class="col-md-2 solo-credito" style="display:none">
        <label class="form-label">Inicio contrato</label>
        <input type="date" name="fecha_contrato_inicio" class="form-control"
               value="{{ isset($a) && $a->fecha_contrato_inicio ? $a->fecha_contrato_inicio->format('Y-m-d') : '' }}">
    </div>
    <div class="col-md-2 solo-credito" style="display:none">
        <label class="form-label">Fin contrato</label>
        <input type="date" name="fecha_contrato_fin" class="form-control"
               value="{{ isset($a) && $a->fecha_contrato_fin ? $a->fecha_contrato_fin->format('Y-m-d') : '' }}">
    </div>
    <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" class="form-control" rows="2">{{ $a->observaciones ?? '' }}</textarea>
    </div>
</div>
