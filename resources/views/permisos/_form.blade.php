<p>
  Debe rellenar todos los campos marcados con <strong class="text-danger">(*)</strong>.
  Al momento de registrar o editar el permiso.</p>
<form id="formPermiso" action="{{route('permisos.store')}}" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="_idempotency_token" value="{{ $idempotencyToken ?? '' }}">
  
<div class="row mb-1">  
    <label for="role_id" class="col-md-4 col-form-label text-right">Nombre de permiso<span class="text-danger">(*)</span></label>
      <div class="col-md-6">
        <input id="name" type="text" class="form-control {{ $errors->has('name') ? ' error' : '' }}"  name="name" value="{{ old('name', $permiso->name)}}"/>
        @if ($errors->has('name'))
            <span class="text-danger">
                {{ $errors->first('name') }}
            </span>
        @endif
      </div>
</div>
<div class="row mb-1">  
    <label for="descripcion" class="col-md-4 col-form-label text-right">Descripcion <span class="text-danger">(*)</span></label>
      <div class="col-md-6">
        <input id="descripcion" type="text" class="form-control {{ $errors->has('descripcion') ? ' error' : '' }}"  name="descripcion" value="{{ old('descripcion', $permiso->descripcion)}}"/>
        @if ($errors->has('descripcion'))
            <span class="text-danger">
                {{ $errors->first('descripcion') }}
            </span>
        @endif
      </div>
</div>
<div class="row mb-1">
    <label for="grupo" class="col-md-4 col-form-label text-right ">Grupo <span class="text-danger">(*)</span></label>
    <div class="col-md-6 mb-0 pb-0">
        <select name="grupo"  class="form-control form-control {{ $errors->has('grupo') ? ' error' : '' }}" id="grupo" onchange="changeRol(this)">
            <option value="">--SELECCIONE--</option>
            @foreach($grupos as $grupo)
                <option value="{{$grupo->descripcion}}" {{ old('grupo',$permiso->grupo ? $permiso->grupo :'')== $grupo->descripcion ? 'selected' : '' }}>{{$grupo->name}} <em>{{$grupo->descripcion}}</em></option>
            @endforeach
        </select>
        @error('role_id')
            <span class="text-danger">
                {{$message}}
            </span>
        @enderror
    </div>
</div>
  <div class="row mt-2">
    <div class="text-center">
      <button type="submit" class="btn btn-primary" id="btnPermiso"> {{ $texto }} </button>
      <a href="{{route('permisos.index')}}" class="btn btn-danger">Cancelar</a>
    </div>
  </div>
</form>
<script>
document.getElementById('formPermiso').addEventListener('submit', function(e) {
    var btn = document.getElementById('btnPermiso');
    if (btn.disabled) { e.preventDefault(); return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
});
</script>