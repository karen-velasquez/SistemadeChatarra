<div class="alert alert-light border py-2 mb-3">
    <small>
        <strong>Campos obligatorios:</strong> Todos los campos marcados con <strong class="text-danger">(*)</strong> son obligatorios.
        @if($user->id)
            Al editar un usuario puede modificar el rol asignado y el correo electrónico.
            <strong>Nota:</strong> El empleado asociado no puede ser modificado.
        @else
            La contraseña sugerida se basa en el CI del empleado seleccionado (puede cambiarla si lo desea).
        @endif
    </small>
</div>
<form id="formUser" action="{{route('users.store')}}" method="POST" enctype="multipart/form-data">
<div class="row mb-1">
    <label for="empleado_id" class="col-md-4 col-form-label text-right">Empleado <span class="text-danger">(*)</span></label>
    <div class="col-md-6 mb-0 pb-0">
        @if($user->id)
            {{-- Modo edición: campo de solo lectura --}}
            <input type="text" class="form-control"
                   value="@if($user->empleado){{$user->empleado->apellido_paterno}} {{$user->empleado->apellido_materno}}, {{$user->empleado->nombre}} ({{$user->empleado->cargo->valor ?? 'Sin cargo'}})@else Sin empleado asignado @endif"
                   readonly
                   style="background-color: #e9ecef; cursor: not-allowed;">
            <input type="hidden" name="empleado_id" value="{{$user->empleado_id}}">
            <small class="form-text text-muted">
                <i class="bi bi-lock"></i> El empleado asociado no puede ser modificado después de crear el usuario.
            </small>
        @else
            {{-- Modo creación: select normal --}}
            <select name="empleado_id" class="form-control form-control {{ $errors->has('empleado_id') ? ' error' : '' }}" id="empleado_id" onchange="cargarDatosEmpleado(this.value); validarFormularioUsuario();" required>
                <option value="">--SELECCIONE EMPLEADO--</option>
                @foreach($empleados as $empleado)
                    <option value="{{$empleado->id}}"
                        data-nombre="{{$empleado->nombre}} {{$empleado->apellido_paterno}} {{$empleado->apellido_materno}}"
                        data-email="{{$empleado->email}}"
                        data-ci="{{$empleado->ci}}"
                        {{ old('empleado_id', $user->empleado_id) == $empleado->id ? 'selected' : '' }}>
                        {{$empleado->apellido_paterno}} {{$empleado->apellido_materno}}, {{$empleado->nombre}} ({{$empleado->cargo->valor ?? 'Sin cargo'}})
                    </option>
                @endforeach
            </select>
            @error('empleado_id')
                <span class="text-danger">
                    {{$message}}
                </span>
            @enderror
            <small class="form-text text-muted">
                <i class="bi bi-info-circle"></i> Seleccione el empleado que usará este usuario del sistema. Los datos se autocompletarán.
            </small>
        @endif
    </div>
</div>

<div class="row mb-1">
    <label for="role_id" class="col-md-4 col-form-label text-right ">Rol <span class="text-danger">(*)</span></label>
    <div class="col-md-6 mb-0 pb-0">
        <select name="role_id" class="form-control form-control {{ $errors->has('role_id') ? ' error' : '' }}" id="role_id" onchange="changeRol(this); validarFormularioUsuario();">
            <option value="">--SELECCIONE--</option>
            @foreach($roles as $rol)
                <option value="{{$rol->id}}" {{ old('role_id',count($user->rol)>0 ? $user->rol[0]->id :'')== $rol->id ? 'selected' : '' }}>{{$rol->name}} <em>({{$rol->descripcion}})</em></option>
            @endforeach
        </select>
        @error('role_id')
            <span class="text-danger">
                {{$message}}
            </span>
        @enderror
    </div>
</div>

<div class="row mb-1">
    <label for="name" class="col-md-4 col-form-label text-right">Nombre Completo: <span class="text-danger">(*)</span></label>
    <div class="col-md-6">
        <input id="name" type="text" class="form-control {{ $errors->has('name') ? ' error' : '' }}" name="name" value="{{ old('name',$user->name) }}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
        @if ($errors->has('name'))
            <span class="text-danger">
                {{ $errors->first('name') }}
            </span>
        @endif
        <small class="form-text text-muted">
            <i class="bi bi-lock"></i> Este campo se completa automáticamente al seleccionar un empleado.
        </small>
    </div>
</div>

<div class="row mb-1">
    <label for="role_id" class="col-md-4 col-form-label text-right ">Correo Electrónico o Usuario <span class="text-danger">(*)</span></label>
    <div class="col-md-6">
        <input id="email" type="text" class="form-control{{ $errors->has('email') ? ' error' : '' }}" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}" oninput="validarFormularioUsuario()">
        @if ($errors->has('email'))
            <span class="text-danger">
                {{ $errors->first('email') }}
            </span>
        @endif
    </div>
</div>

<div class="row mb-0">
    <label for="password" class="col-md-4 col-form-label text-right">
        {{ $texto_pass }}
        @if($tipo==1)
            <span class="text-danger">(*)</span>
        @endif
    </label>
    <div class="col-md-6 position-relative">
        <input id="password" type="password" class="form-control @error('password') error @enderror" name="password" autocomplete="new-password" oninput="validarPassword(); validarFormularioUsuario();">
        <i id="togglePasswordIcon" class="bi bi-eye position-absolute" style="right: 30px; top: 10px; cursor: pointer;" onclick="togglePassword('password', 'togglePasswordIcon')"></i>

        @error('password')
            <span class="text-danger">
                {{ $message }}
            </span>
        @enderror
        <small id="password-feedback" class="form-text"></small>
    </div>
</div>

@if($tipo==1)
    <div class="row mt-1">
        <label for="password_confirmation" class="col-md-4 col-form-label text-right ">Confirmar Contraseña <span class="text-danger">(*)</span></label>
        <div class="col-md-6 position-relative">
            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" oninput="validarPasswordConfirmation(); validarFormularioUsuario();">
            <i id="togglePasswordConfirmationIcon" class="bi bi-eye position-absolute" style="right: 30px; top: 10px; cursor: pointer;" onclick="togglePassword('password_confirmation', 'togglePasswordConfirmationIcon')"></i>

            @error('password_confirmation')
            <span class="text-danger">
                {{ $message }}
            </span>
        @enderror
            <small id="password-confirmation-feedback" class="form-text"></small>
        </div>
    </div>
@endif
<div class="row mt-2">
    <div class="text-center">
        <button type="submit" class="btn btn-primary" id="btnSubmitUser" @if($tipo==1) disabled @endif>{{ $texto }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
</form>