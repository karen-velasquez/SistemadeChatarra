<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

    Route::get('/', function () {
        return view('auth.login');
    });
    
    Auth::routes();
    //Route::middleware(['auth'])->group(function(){
    Route::middleware(['auth', 'checkPasswordChange'])->group(function() {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
      
    //Roles
    Route::post('roles/store',[App\Http\Controllers\RoleController::class,'store'])->name('roles.store')->middleware('permission:roles.create');
    Route::get('roles',[App\Http\Controllers\RoleController::class,'index'])->name('roles.index')->middleware('permission:roles.index');
    Route::get('roles/create',[App\Http\Controllers\RoleController::class,'create'])->name('roles.create')->middleware('permission:roles.create');
    Route::put('roles/{role}',[App\Http\Controllers\RoleController::class,'update'])->name('roles.update')->middleware('permission:roles.edit');
    Route::get('roles/{uuid}',[App\Http\Controllers\RoleController::class,'show'])->name('roles.show')->middleware('permission:roles.index');
    Route::get('roles/{uuid}/destroy',[App\Http\Controllers\RoleController::class,'destroy'])->name('roles.destroy')->middleware('permission:roles.destroy');
    Route::get('roles/{uuid}/edit',[App\Http\Controllers\RoleController::class,'edit'])->name('roles.edit')->middleware('permission:roles.edit');

    //User
    Route::post('users/store',[App\Http\Controllers\UserController::class,'store'])->name('users.store')->middleware('permission:users.create');
    Route::get('users',[App\Http\Controllers\UserController::class,'index'])->name('users.index')->middleware('permission:users.index');
    Route::get('users/create',[App\Http\Controllers\UserController::class,'create'])->name('users.create')->middleware('permission:users.create');
    Route::put('users/{user}',[App\Http\Controllers\UserController::class,'update'])->name('users.update')->middleware('permission:users.edit');
    Route::put('profile/{user}',[App\Http\Controllers\UserController::class,'update_profile'])->name('users.update_profile');
    Route::get('mis_datos/{uuid}',[App\Http\Controllers\UserController::class,'show'])->name('users.show')->middleware('permission:users.show');
    Route::get('usuario/{uuid}/destroy',[App\Http\Controllers\UserController::class,'destroy'])->name('users.destroy')->middleware('permission:users.destroy');
    Route::get('users/{uuid}/edit',[App\Http\Controllers\UserController::class,'edit'])->name('users.edit')->middleware('permission:users.edit');
    Route::get('datos_empleado',[App\Http\Controllers\UserController::class,'datos_empleado'])->name('datos.empleado');
    Route::post('/change-password', [App\Http\Controllers\UserController::class, 'changePassword'])->name('change.password');

      //Permisos
    Route::get('permisos',[App\Http\Controllers\PermissionController::class,'index'])->name('permisos.index')->middleware('permission:permisos.index');
    Route::post('permisos/store',[App\Http\Controllers\PermissionController::class,'store'])->name('permisos.store')->middleware('permission:permisos.create');
    Route::get('permisos/create',[App\Http\Controllers\PermissionController::class,'create'])->name('permisos.create')->middleware('permission:permisos.create');
    Route::put('permisos/{permiso}',[App\Http\Controllers\PermissionController::class,'update'])->name('permisos.update')->middleware('permission:permisos.edit');
    Route::get('permisos/{permiso}',[App\Http\Controllers\PermissionController::class,'show'])->name('permisos.show')->middleware('permission:permisos.show');
    Route::get('permisos/{permiso}/eliminar',[App\Http\Controllers\PermissionController::class,'destroy'])->name('permisos.destroy')->middleware('permission:permisos.destroy');
    Route::get('permisos/{permiso}/edit',[App\Http\Controllers\PermissionController::class,'edit'])->name('permisos.edit')->middleware('permission:permisos.edit');

    //Proveedores
    Route::get('proveedores',[App\Http\Controllers\ProveedorController::class,'index'])->name('proveedores.index')->middleware('permission:proveedores.index');
    Route::get('proveedor/create',[App\Http\Controllers\ProveedorController::class,'create'])->name('proveedores.create')->middleware('permission:proveedores.create');
    Route::post('proveedor/store',[App\Http\Controllers\ProveedorController::class,'store'])->name('proveedores.store')->middleware('permission:proveedores.create');
    Route::get('proveedor/{uuid}',[App\Http\Controllers\ProveedorController::class,'show'])->name('proveedores.show')->middleware('permission:proveedores.show');
    Route::get('proveedor/{uuid}/edit',[App\Http\Controllers\ProveedorController::class,'edit'])->name('proveedores.edit')->middleware('permission:proveedores.edit');
    Route::put('proveedores/{proveedor}',[App\Http\Controllers\ProveedorController::class,'update'])->name('proveedores.update')->middleware('permission:proveedores.edit');
    Route::get('proveedor/{uuid}/destroy',[App\Http\Controllers\ProveedorController::class,'destroy'])->name('proveedores.destroy')->middleware('permission:proveedores.destroy');
    
    //clientes
    Route::get('clientes', [App\Http\Controllers\ClienteController::class, 'index'])->name('clientes.index')->middleware('permission:clientes.index');
    Route::get('clientes/create', [App\Http\Controllers\ClienteController::class, 'create'])->name('clientes.create')->middleware('permission:clientes.create');
    Route::post('clientes/store', [App\Http\Controllers\ClienteController::class, 'store'])->name('clientes.store')->middleware('permission:clientes.create');
    Route::get('clientes/{uuid}', [App\Http\Controllers\ClienteController::class, 'show'])->name('clientes.show')->middleware('permission:clientes.show');
    Route::get('clientes/{uuid}/edit', [App\Http\Controllers\ClienteController::class, 'edit'])->name('clientes.edit')->middleware('permission:clientes.edit');
    Route::put('clientes/{cliente}', [App\Http\Controllers\ClienteController::class, 'update'])->name('clientes.update')->middleware('permission:clientes.edit');
    Route::get('clientes/{uuid}/destroy', [App\Http\Controllers\ClienteController::class, 'destroy'])->name('clientes.destroy')->middleware('permission:clientes.destroy');

    //Camiones
    Route::get('camiones',[App\Http\Controllers\CamionController::class,'index'])->name('camiones.index')->middleware('permission:camiones.index');
    Route::post('camion/store',[App\Http\Controllers\CamionController::class,'store'])->name('camiones.store')->middleware('permission:camiones.create');
    Route::put('camion/{camion}',[App\Http\Controllers\CamionController::class,'update'])->name('camiones.update')->middleware('permission:camiones.edit');
    Route::get('camion/{uuid}/edit',[App\Http\Controllers\CamionController::class,'edit'])->name('camiones.edit')->middleware('permission:camiones.edit');
    Route::get('camion/{uuid}/destroy',[App\Http\Controllers\CamionController::class,'destroy'])->name('camiones.destroy')->middleware('permission:camiones.destroy');
    Route::get('camion/{uuid}/ruat',[App\Http\Controllers\CamionController::class,'verRuat'])->name('camiones.ruat')->middleware('permission:camiones.index');
    Route::delete('camion/foto/{foto}',[App\Http\Controllers\CamionController::class,'eliminarFoto'])->name('camiones.foto.destroy')->middleware('permission:camiones.edit');

    //Operadores de Transporte (propietarios y conductores)
    Route::post('operador/store',[App\Http\Controllers\OperadorTransporteController::class,'store'])->name('operadores.store')->middleware('permission:operadores.create');
    Route::get('operador/{uuid}/edit',[App\Http\Controllers\OperadorTransporteController::class,'edit'])->name('operadores.edit')->middleware('permission:operadores.edit');
    Route::put('operador/{operador}',[App\Http\Controllers\OperadorTransporteController::class,'update'])->name('operadores.update')->middleware('permission:operadores.edit');
    Route::get('operador/{uuid}/destroy',[App\Http\Controllers\OperadorTransporteController::class,'destroy'])->name('operadores.destroy')->middleware('permission:operadores.destroy');
    Route::get('operador/{uuid}/carnet',[App\Http\Controllers\OperadorTransporteController::class,'verCarnet'])->name('operadores.carnet')->middleware('permission:operadores.index');
    Route::get('operador/{uuid}/licencia',[App\Http\Controllers\OperadorTransporteController::class,'verLicencia'])->name('operadores.licencia')->middleware('permission:operadores.index');

    //Contratos
    Route::get('contratos',[App\Http\Controllers\ContratoController::class,'index'])->name('contratos.index')->middleware('permission:contratos.index');
    Route::post('contrato/store',[App\Http\Controllers\ContratoController::class,'store'])->name('contratos.store')->middleware('permission:contratos.create');
    Route::get('contrato/{uuid}/edit',[App\Http\Controllers\ContratoController::class,'edit'])->name('contratos.edit')->middleware('permission:contratos.edit');
    Route::put('contrato/{contrato}',[App\Http\Controllers\ContratoController::class,'update'])->name('contratos.update')->middleware('permission:contratos.edit');
    Route::get('contrato/{uuid}/destroy',[App\Http\Controllers\ContratoController::class,'destroy'])->name('contratos.destroy')->middleware('permission:contratos.destroy');
    Route::get('contrato/{uuid}/camiones',[App\Http\Controllers\ContratoController::class,'camiones'])->name('contratos.camiones')->middleware('permission:contratos.index');
    Route::get('contrato/{uuid}/pdf',[App\Http\Controllers\ContratoController::class,'verPdf'])->name('contratos.pdf')->middleware('permission:contratos.index');
    Route::get('contrato/{uuid}/cerrar-envios',[App\Http\Controllers\ContratoController::class,'cerrarEnvios'])->name('contratos.cerrar')->middleware('permission:contratos.cerrar');
    Route::get('contratos/liquidacion',[App\Http\Controllers\ContratoController::class,'liquidacion'])->name('contratos.liquidacion')->middleware('permission:contratos.liquidacion');

    //Contrato Camiones
    Route::post('contrato-camion/store',[App\Http\Controllers\ContratoCamionController::class,'store'])->name('contrato-camion.store')->middleware('permission:contratos.edit');
    Route::get('contrato-camion/{uuid}/toggle-entrega',[App\Http\Controllers\ContratoCamionController::class,'toggleEntrega'])->name('contrato-camion.toggle-entrega')->middleware('permission:contratos.edit');
    Route::get('contrato-camion/{uuid}/toggle-activo',[App\Http\Controllers\ContratoCamionController::class,'toggleActivo'])->name('contrato-camion.toggle-activo')->middleware('permission:contratos.edit');
    Route::post('contrato-camion/{uuid}/flete',[App\Http\Controllers\ContratoCamionController::class,'actualizarFlete'])->name('contrato-camion.flete')->middleware('permission:contratos.edit');

    //Tramos de transporte
    Route::post('tramo/store',[App\Http\Controllers\TramoController::class,'store'])->name('tramo.store')->middleware('permission:contratos.edit');
    Route::post('tramo/{uuid}/llegada',[App\Http\Controllers\TramoController::class,'registrarLlegada'])->name('tramo.llegada')->middleware('permission:contratos.edit');
    Route::get('tramo/{uuid}/toggle-activo',[App\Http\Controllers\TramoController::class,'toggleActivo'])->name('tramo.toggle-activo')->middleware('permission:contratos.edit');
    Route::get('tramo/{uuid}/nota-entrega',[App\Http\Controllers\TramoController::class,'notaEntrega'])->name('tramo.nota-entrega')->middleware('permission:contratos.index');

    // Seguimiento de cargas
    Route::get('seguimiento-cargas',[App\Http\Controllers\SeguimientoCargasController::class,'index'])->name('seguimiento.index')->middleware('permission:seguimiento.index');

    // Empleados
    Route::get('empleados',[App\Http\Controllers\EmpleadoController::class,'index'])->name('empleados.index')->middleware('permission:empleados.index');
    Route::post('empleados',[App\Http\Controllers\EmpleadoController::class,'store'])->name('empleados.store')->middleware('permission:empleados.create');
    Route::put('empleados/{uuid}',[App\Http\Controllers\EmpleadoController::class,'update'])->name('empleados.update')->middleware('permission:empleados.edit');
    Route::get('empleados/{uuid}/toggle',[App\Http\Controllers\EmpleadoController::class,'toggleActivo'])->name('empleados.toggle')->middleware('permission:empleados.edit');
    Route::get('empleados/{uuid}/destroy',[App\Http\Controllers\EmpleadoController::class,'destroy'])->name('empleados.destroy')->middleware('permission:empleados.destroy');

    // Bancos y cuentas bancarias
    Route::get('bancos',[App\Http\Controllers\BancoController::class,'index'])->name('bancos.index')->middleware('permission:bancos.index');
    Route::post('bancos',[App\Http\Controllers\BancoController::class,'store'])->name('bancos.store')->middleware('permission:bancos.create');
    Route::put('bancos/{uuid}',[App\Http\Controllers\BancoController::class,'update'])->name('bancos.update')->middleware('permission:bancos.edit');
    Route::get('bancos/{uuid}/destroy',[App\Http\Controllers\BancoController::class,'destroy'])->name('bancos.destroy')->middleware('permission:bancos.destroy');
    Route::post('bancos/cuentas',[App\Http\Controllers\BancoController::class,'storeCuenta'])->name('bancos.cuenta.store')->middleware('permission:bancos.create');
    Route::put('bancos/cuentas/{uuid}',[App\Http\Controllers\BancoController::class,'updateCuenta'])->name('bancos.cuenta.update')->middleware('permission:bancos.edit');
    Route::get('bancos/cuentas/{uuid}/destroy',[App\Http\Controllers\BancoController::class,'destroyCuenta'])->name('bancos.cuenta.destroy')->middleware('permission:bancos.destroy');

    // Pagos de clientes
    Route::get('pagos/clientes',[App\Http\Controllers\PagoClienteController::class,'index'])->name('pagos.clientes.index')->middleware('permission:pagos_clientes.index');
    Route::post('pagos/clientes',[App\Http\Controllers\PagoClienteController::class,'store'])->name('pagos.clientes.store')->middleware('permission:pagos_clientes.create');
    Route::post('pagos/clientes/{id}/precio',[App\Http\Controllers\PagoClienteController::class,'setPrecio'])->name('pagos.clientes.precio')->middleware('permission:pagos_clientes.create');
    Route::get('pagos/clientes/{uuid}/destroy',[App\Http\Controllers\PagoClienteController::class,'destroy'])->name('pagos.clientes.destroy')->middleware('permission:pagos_clientes.destroy');
    Route::put('pagos/clientes/{uuid}',[App\Http\Controllers\PagoClienteController::class,'update'])->name('pagos.clientes.update')->middleware('permission:pagos_clientes.edit');
    Route::post('pagos/clientes/cobro-masivo',[App\Http\Controllers\PagoClienteController::class,'cobroMasivo'])->name('pagos.clientes.cobro_masivo')->middleware('permission:pagos_clientes.create');
    Route::get('api/pagos/clientes/{id}/detalle',[App\Http\Controllers\PagoClienteController::class,'detalle'])->name('pagos.clientes.detalle');
    Route::get('api/pagos/cuentas-cliente',[App\Http\Controllers\PagoClienteController::class,'cuentasCliente'])->name('pagos.cuentas-cliente');

    // Pagos a proveedores
    Route::get('pagos/proveedores',[App\Http\Controllers\PagoProveedorController::class,'index'])->name('pagos.proveedores.index')->middleware('permission:pagos_proveedores.index');
    Route::post('pagos/proveedores',[App\Http\Controllers\PagoProveedorController::class,'store'])->name('pagos.proveedores.store')->middleware('permission:pagos_proveedores.create');
    Route::get('pagos/proveedores/pago-masivo',[App\Http\Controllers\PagoProveedorController::class,'pagoMasivoView'])->name('pagos.proveedores.pago_masivo')->middleware('permission:pagos_proveedores.create');
    Route::post('pagos/proveedores/pago-masivo',[App\Http\Controllers\PagoProveedorController::class,'pagoMasivoStore'])->name('pagos.proveedores.pago_masivo.store')->middleware('permission:pagos_proveedores.create');
    Route::get('pagos/proveedores/{uuid}/destroy',[App\Http\Controllers\PagoProveedorController::class,'destroy'])->name('pagos.proveedores.destroy')->middleware('permission:pagos_proveedores.destroy');
    Route::put('pagos/proveedores/{uuid}',[App\Http\Controllers\PagoProveedorController::class,'update'])->name('pagos.proveedores.update')->middleware('permission:pagos_proveedores.edit');
    Route::get('api/pagos/proveedores/{id}/detalle',[App\Http\Controllers\PagoProveedorController::class,'detalle'])->name('pagos.proveedores.detalle');
    Route::get('api/pagos/cuentas-proveedor',[App\Http\Controllers\PagoProveedorController::class,'cuentasProveedor'])->name('pagos.cuentas-proveedor');

    // Lotes de pago masivo
    Route::get('lotes-pago', [App\Http\Controllers\LotePagoController::class, 'index'])->name('lotes_pago.index')->middleware('permission:pagos_camiones.index');
    Route::post('lotes-pago/{uuid}/codigo', [App\Http\Controllers\LotePagoController::class, 'actualizarCodigo'])->name('lotes_pago.codigo')->middleware('permission:pagos_camiones.create');

    // Pagos a camiones
    Route::get('pagos/camiones',[App\Http\Controllers\PagoCamionController::class,'index'])->name('pagos.camiones.index')->middleware('permission:pagos_camiones.index');
    Route::post('pagos/camiones',[App\Http\Controllers\PagoCamionController::class,'store'])->name('pagos.camiones.store')->middleware('permission:pagos_camiones.create');
    Route::get('pagos/camiones/pago-masivo',[App\Http\Controllers\PagoCamionController::class,'pagoMasivoView'])->name('pagos.camiones.pago_masivo')->middleware('permission:pagos_camiones.create');
    Route::post('pagos/camiones/pago-masivo',[App\Http\Controllers\PagoCamionController::class,'pagoMasivoStore'])->name('pagos.camiones.pago_masivo.store')->middleware('permission:pagos_camiones.create');
    Route::put('pagos/camiones/{uuid}',[App\Http\Controllers\PagoCamionController::class,'update'])->name('pagos.camiones.update')->middleware('permission:pagos_camiones.edit');
    Route::get('pagos/camiones/{uuid}/destroy',[App\Http\Controllers\PagoCamionController::class,'destroy'])->name('pagos.camiones.destroy')->middleware('permission:pagos_camiones.destroy');
    Route::get('api/pagos/camiones/{id}/detalle',[App\Http\Controllers\PagoCamionController::class,'detalle'])->name('pagos.camiones.detalle');
    Route::get('api/pagos/cuentas-receptor',[App\Http\Controllers\PagoCamionController::class,'cuentasReceptor'])->name('pagos.cuentas-receptor');

    //Asignación de conductores a camiones
    Route::post('conductor/store',[App\Http\Controllers\CamionConductorController::class,'store'])->name('conductores.store')->middleware('permission:conductores.create');
    Route::get('conductor/{uuid}/finalizar',[App\Http\Controllers\CamionConductorController::class,'finalizarAsignacion'])->name('conductores.finalizar')->middleware('permission:conductores.edit');

    //Endpoints de consulta
    Route::get('api/camiones/detalle',[App\Http\Controllers\CamionConductorController::class,'camionesConDetalle'])->name('camiones.detalle');
    Route::get('api/camion/{uuid}/historial',[App\Http\Controllers\CamionConductorController::class,'historialConductores'])->name('camiones.historial');
    Route::get('api/camion/{uuid}/conductores-relacionados',[App\Http\Controllers\CamionConductorController::class,'conductoresRelacionados'])->name('camiones.conductores-relacionados');
    Route::get('api/camion/{uuid}/conductores-disponibles',[App\Http\Controllers\CamionConductorController::class,'conductoresDisponibles'])->name('camiones.conductores-disponibles');

     //Gastos Extras
    Route::get('gastos_extras', [App\Http\Controllers\GastoExtraController::class, 'index'])->name('gastos_extras.index')->middleware('permission:gastos_extras.index');
    Route::get('gastos_extras/create', [App\Http\Controllers\GastoExtraController::class, 'create'])->name('gastos_extras.create')->middleware('permission:gastos_extras.create');
    Route::post('gastos_extras/store', [App\Http\Controllers\GastoExtraController::class, 'store'])->name('gastos_extras.store')->middleware('permission:gastos_extras.create');
    Route::get('gastos_extras/{uuid}', [App\Http\Controllers\GastoExtraController::class, 'show'])->name('gastos_extras.show')->middleware('permission:gastos_extras.show');
    Route::get('gastos_extras/{uuid}/edit', [App\Http\Controllers\GastoExtraController::class, 'edit'])->name('gastos_extras.edit')->middleware('permission:gastos_extras.edit');
    Route::put('gastos_extras/{gastos_extras}', [App\Http\Controllers\GastoExtraController::class, 'update'])->name('gastos_extras.update')->middleware('permission:gastos_extras.edit');
    Route::get('gastos_extras/{uuid}/destroy', [App\Http\Controllers\GastoExtraController::class, 'destroy'])->name('gastos_extras.destroy')->middleware('permission:gastos_extras.destroy');
    
    //Cuentas Bancarias (gestionadas desde el módulo de Bancos);

    //Reportes
    Route::get('/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index')->middleware('permission:reportes.index');
    Route::get('/reportes/exportar-excel', [App\Http\Controllers\ReporteController::class,'exportarExcel'])->name('reportes.exportar.excel')->middleware('permission:reportes.export');

    // Empresas (tesorería)
    Route::get('empresas', [App\Http\Controllers\EmpresaController::class, 'index'])->name('empresas.index')->middleware('permission:empresas.index');
    Route::post('empresas/store', [App\Http\Controllers\EmpresaController::class, 'store'])->name('empresas.store')->middleware('permission:empresas.create');
    Route::get('empresas/{uuid}/edit', [App\Http\Controllers\EmpresaController::class, 'edit'])->name('empresas.edit')->middleware('permission:empresas.edit');
    Route::put('empresas/{uuid}', [App\Http\Controllers\EmpresaController::class, 'update'])->name('empresas.update')->middleware('permission:empresas.edit');
    Route::get('empresas/{uuid}/destroy', [App\Http\Controllers\EmpresaController::class, 'destroy'])->name('empresas.destroy')->middleware('permission:empresas.destroy');
    Route::get('empresas/{uuid}/cuentas', [App\Http\Controllers\EmpresaController::class, 'cuentas'])->name('empresas.cuentas')->middleware('permission:empresas.index');
    Route::post('empresas/{uuid}/cuentas/store', [App\Http\Controllers\EmpresaController::class, 'storeCuenta'])->name('empresas.cuentas.store')->middleware('permission:empresas.create');

    // Movimientos (tesorería)
    Route::get('tesoreria', [App\Http\Controllers\MovimientoController::class, 'index'])->name('tesoreria.index')->middleware('permission:empresas.index');
    Route::get('tesoreria/cuenta/{uuid}', [App\Http\Controllers\MovimientoController::class, 'porCuenta'])->name('tesoreria.cuenta')->middleware('permission:empresas.index');
    Route::post('tesoreria/movimiento/store', [App\Http\Controllers\MovimientoController::class, 'store'])->name('tesoreria.movimiento.store')->middleware('permission:empresas.create');
    Route::get('tesoreria/movimiento/{uuid}/destroy', [App\Http\Controllers\MovimientoController::class, 'destroy'])->name('tesoreria.movimiento.destroy')->middleware('permission:empresas.destroy');

    // Préstamos internos
    Route::get('prestamos-internos', [App\Http\Controllers\PrestamoInternoController::class, 'index'])->name('prestamos_internos.index')->middleware('permission:empresas.index');
    Route::post('prestamos-internos/store', [App\Http\Controllers\PrestamoInternoController::class, 'store'])->name('prestamos_internos.store')->middleware('permission:empresas.create');
    Route::post('prestamos-internos/{uuid}/devolver', [App\Http\Controllers\PrestamoInternoController::class, 'devolver'])->name('prestamos_internos.devolver')->middleware('permission:empresas.create');

    // Parámetros
    Route::get('parametros', [App\Http\Controllers\ParametroController::class, 'index'])->name('parametros.index')->middleware('permission:parametros.index');
    Route::post('parametros/store', [App\Http\Controllers\ParametroController::class, 'store'])->name('parametros.store')->middleware('permission:parametros.create');
    Route::get('parametros/{uuid}/edit', [App\Http\Controllers\ParametroController::class, 'edit'])->name('parametros.edit')->middleware('permission:parametros.edit');
    Route::put('parametros/{uuid}', [App\Http\Controllers\ParametroController::class, 'update'])->name('parametros.update')->middleware('permission:parametros.edit');
    Route::get('parametros/{uuid}/destroy', [App\Http\Controllers\ParametroController::class, 'destroy'])->name('parametros.destroy')->middleware('permission:parametros.destroy');
   });
