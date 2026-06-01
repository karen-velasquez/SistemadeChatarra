var empleadoId = $('#empleado_id').val();

// Validar formulario completo de usuario
function validarFormularioUsuario() {
    const btnSubmit = document.getElementById('btnSubmitUser');

    // Si no existe el botón (modo edición), salir
    if (!btnSubmit) return;

    // Obtener valores de campos obligatorios
    const empleadoId = document.getElementById('empleado_id') ? document.getElementById('empleado_id').value : null;
    const roleId = document.getElementById('role_id') ? document.getElementById('role_id').value : null;
    const email = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
    const password = document.getElementById('password') ? document.getElementById('password').value : '';
    const passwordConfirmation = document.getElementById('password_confirmation') ? document.getElementById('password_confirmation').value : '';

    // Validar que todos los campos obligatorios estén llenos
    let formularioValido = true;

    // Empleado (solo en creación)
    if (empleadoId !== null && empleadoId === '') {
        formularioValido = false;
    }

    // Rol
    if (roleId === null || roleId === '') {
        formularioValido = false;
    }

    // Email
    if (email === '') {
        formularioValido = false;
    }

    // Contraseña (solo en creación - si existe el campo)
    if (password !== null) {
        if (password.length < 8) {
            formularioValido = false;
        }

        // Confirmación de contraseña
        if (passwordConfirmation !== password) {
            formularioValido = false;
        }
    }

    // Habilitar o deshabilitar el botón
    btnSubmit.disabled = !formularioValido;
}

// Validar contraseña en tiempo real
function validarPassword() {
    const password = document.getElementById('password');
    const feedback = document.getElementById('password-feedback');
    const length = password.value.length;

    if (length === 0) {
        feedback.textContent = '';
        feedback.className = 'form-text';
        password.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (length < 8) {
        feedback.textContent = `Mínimo 8 caracteres. Faltan ${8 - length} caractere(s).`;
        feedback.className = 'form-text text-danger';
        password.classList.remove('is-valid');
        password.classList.add('is-invalid');
    } else {
        feedback.textContent = `✓ Contraseña válida (${length} caracteres)`;
        feedback.className = 'form-text text-success';
        password.classList.remove('is-invalid');
        password.classList.add('is-valid');
    }

    // Validar confirmación si ya tiene contenido
    if (document.getElementById('password_confirmation')) {
        validarPasswordConfirmation();
    }
}

// Validar confirmación de contraseña en tiempo real
function validarPasswordConfirmation() {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    const feedback = document.getElementById('password-confirmation-feedback');

    if (!confirmation) return;

    if (confirmation.value === '') {
        feedback.textContent = '';
        feedback.className = 'form-text';
        confirmation.classList.remove('is-valid', 'is-invalid');
        return;
    }

    if (password.value === confirmation.value) {
        feedback.textContent = '✓ Las contraseñas coinciden';
        feedback.className = 'form-text text-success';
        confirmation.classList.remove('is-invalid');
        confirmation.classList.add('is-valid');
    } else {
        feedback.textContent = '✗ Las contraseñas no coinciden';
        feedback.className = 'form-text text-danger';
        confirmation.classList.remove('is-valid');
        confirmation.classList.add('is-invalid');
    }
}

// Autocompletar datos del empleado seleccionado
function cargarDatosEmpleado(empleadoId) {
    if (!empleadoId) {
        // Limpiar campos si no hay empleado seleccionado
        $('#name').val('');
        $('#email').val('');
        $('#password').val('');
        return;
    }

    const selectEmpleado = document.getElementById('empleado_id');
    const selectedOption = selectEmpleado.options[selectEmpleado.selectedIndex];

    // Obtener datos del empleado desde los atributos data
    const nombreCompleto = selectedOption.getAttribute('data-nombre');
    const emailEmpleado = selectedOption.getAttribute('data-email');
    const ciEmpleado = selectedOption.getAttribute('data-ci');

    // Autocompletar nombre
    if (nombreCompleto) {
        $('#name').val(nombreCompleto.toUpperCase());
    }

    // Autocompletar email (si existe)
    if (emailEmpleado && emailEmpleado !== 'null') {
        $('#email').val(emailEmpleado);
    } else {
        $('#email').val('');
    }

    // Sugerir contraseña basada en CI (solo al crear, no al editar)
    const formAction = $('#formUser').attr('action');
    if (ciEmpleado && ciEmpleado !== 'null' && formAction && formAction.includes('store')) {
        $('#password').val(ciEmpleado);
        $('#password_confirmation').val(ciEmpleado);
    }
}

$("#fileInput").change(function () {
    readURL(this);
});

function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#imagen').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

