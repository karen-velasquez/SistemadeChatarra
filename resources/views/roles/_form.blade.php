<div class="row">
    <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Nuevo Rol</h5>
            <p>Debe rellenar todos los campos</p>
            <div class="row mb-1">
                <div class="col-lg-12 col-md-12 col-xs-12">
                    <label for="name" class="">Nombre rol<span class="text-danger text-bold">(*)</span></label>
                    <input type="text" class="form-control text-uppercase @error('name') error @enderror" name="name" id="name" value="{{ old('name',$role->name) }}" oninput="this.value = this.value.toUpperCase()">
                    @error('name')
                        <label class="error">{{ $message }}</label>
                    @enderror
                </div>
            </div>
                <div class="row mb-3">
                    <div class="col-lg-12 col-md-12 col-xs-12">
                    <label for="descripcion" class="">Descripción<span class="text-danger text-bold">(*)</span></label>
                        <input type="text" class="form-control @error('descripcion') error @enderror" name="descripcion" id=descripcion value="{{ old('descripcion',$role->descripcion) }}">
                    </div>
                </div>
                <input type="hidden" name="_idempotency_token" value="{{ $idempotencyToken ?? '' }}">
                <div class="text-center">
                 {{ Form::submit('Guardar',['class'=>'btn btn-primary', 'id'=>'btnRol']) }}
                 <a href="{{ route('roles.index') }}" class="btn btn-danger">Cancelar</a>
             </div>
             <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelector('form').addEventListener('submit', function(e) {
                        var btn = document.getElementById('btnRol');
                        if (btn.disabled) { e.preventDefault(); return; }
                        btn.disabled = true;
                        btn.value = 'Guardando...';
                    });
                });
             </script>
           </div>
        </div>
    </div>
            
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Lista de Permisos</h5>
            <p>Marque los permisos que quiere asignar al Rol y luego presione el boton <strong>GUARDAR</strong></p>
            <!-- Accordion without outline borders -->
            <div class="accordion accordion-flush" id="accordionFlushExample">
                @foreach($grupos as $cont_grupo=>$grupo)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne-{{ $cont_grupo }}" aria-expanded="false" aria-controls="flush-collapseOne-{{ $cont_grupo }}">
                            {{ $cont_grupo + 1 }} - GESTIÓN DE {{ $grupo->grupo }}
                        </button>
                    </h2>
                    <div id="flush-collapseOne-{{ $cont_grupo }}" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                        <ul class="list-unstyled">
                            <li>
                                <label>
                                    {{ Form::checkbox('marcar_todo_'.$grupo->grupo, null, false, ['class' => 'form-check-input marcar-todos', 'data-grupo' => $grupo->grupo]) }}
                                    &nbsp; MARCAR / DESMARCAR TODOS
                                </label>
                            </li>
                            <?php $cont=0; ?>
                            @php $rolePermissionIds = isset($role) && $role->id ? $role->permissions->pluck('id')->toArray() : []; @endphp
                            @foreach($permissions as $permission)
                            @if($permission->grupo==$grupo->grupo)
                            <?php $cont=$cont+1; ?>
                            <li>
                                <label>
                                    {{ Form::checkbox('permissions[]', $permission->id, in_array($permission->id, $rolePermissionIds), ['class' => 'form-check-input', 'id' => 'basic_checkbox_' . $cont, 'data-grupo' => $grupo->grupo]) }}
                                    &nbsp;&nbsp; {{$permission->descripcion ?: 'Sin descripción'}}
                                    <em>({{$permission->name}})</em>
                                </label>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
           
           </div>
        </div>
      </div>

</div>





