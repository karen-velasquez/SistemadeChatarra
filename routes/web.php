<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

    Route::get('/', function () {
        return view('auth.login');
    });

    // RUTA TEMPORAL DE DIAGNÓSTICO — eliminar después
    Route::get('debug-storage/{uuid}', function ($uuid) {
        $tramo = \App\Models\Tramo::where('uuid', $uuid)->first();
        if (!$tramo) return 'Tramo no encontrado';
        $doc = $tramo->documento_entrega;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        return response()->json([
            'documento_entrega' => $doc,
            'disk_root'         => $disk->path(''),
            'exists'            => $doc ? $disk->exists($doc) : false,
            'full_path'         => $doc ? $disk->path($doc) : null,
            'file_exists'       => $doc ? file_exists($disk->path($doc)) : false,
        ]);
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
    Route::get('proveedores/nuevo-token',[App\Http\Controllers\ProveedorController::class,'nuevoToken'])->name('proveedores.nuevo-token');
    Route::post('proveedor/store',[App\Http\Controllers\ProveedorController::class,'store'])->name('proveedores.store')->middleware('permission:proveedores.create');
    Route::get('proveedor/{uuid}',[App\Http\Controllers\ProveedorController::class,'show'])->name('proveedores.show')->middleware('permission:proveedores.show');
    Route::get('proveedor/{uuid}/edit',[App\Http\Controllers\ProveedorController::class,'edit'])->name('proveedores.edit')->middleware('permission:proveedores.edit');
    Route::put('proveedores/{proveedor}',[App\Http\Controllers\ProveedorController::class,'update'])->name('proveedores.update')->middleware('permission:proveedores.edit');
    Route::get('proveedor/{uuid}/destroy',[App\Http\Controllers\ProveedorController::class,'destroy'])->name('proveedores.destroy')->middleware('permission:proveedores.destroy');

    //clientes
    Route::get('clientes', [App\Http\Controllers\ClienteController::class, 'index'])->name('clientes.index')->middleware('permission:clientes.index');
    Route::get('clientes/create', [App\Http\Controllers\ClienteController::class, 'create'])->name('clientes.create')->middleware('permission:clientes.create');
    Route::get('clientes/nuevo-token',[App\Http\Controllers\ClienteController::class,'nuevoToken'])->name('clientes.nuevo-token');
    Route::post('clientes/store', [App\Http\Controllers\ClienteController::class, 'store'])->name('clientes.store')->middleware('permission:clientes.create');
    Route::get('clientes/{uuid}', [App\Http\Controllers\ClienteController::class, 'show'])->name('clientes.show')->middleware('permission:clientes.show');
    Route::get('clientes/{uuid}/edit', [App\Http\Controllers\ClienteController::class, 'edit'])->name('clientes.edit')->middleware('permission:clientes.edit');
    Route::put('clientes/{cliente}', [App\Http\Controllers\ClienteController::class, 'update'])->name('clientes.update')->middleware('permission:clientes.edit');
    Route::get('clientes/{uuid}/destroy', [App\Http\Controllers\ClienteController::class, 'destroy'])->name('clientes.destroy')->middleware('permission:clientes.destroy');

    //Camiones
    Route::get('camiones',[App\Http\Controllers\CamionController::class,'index'])->name('camiones.index')->middleware('permission:camiones.index');
    Route::get('camiones/nuevo-token',[App\Http\Controllers\CamionController::class,'nuevoToken'])->name('camiones.nuevo-token');
    Route::get('camion/buscar-placa',[App\Http\Controllers\CamionController::class,'buscarPorPlaca'])->name('camiones.buscar-placa')->middleware('permission:camiones.index');
    Route::post('camion/store',[App\Http\Controllers\CamionController::class,'store'])->name('camiones.store')->middleware('permission:camiones.create');
    Route::put('camion/{camion}',[App\Http\Controllers\CamionController::class,'update'])->name('camiones.update')->middleware('permission:camiones.edit');
    Route::get('camion/{uuid}/edit',[App\Http\Controllers\CamionController::class,'edit'])->name('camiones.edit')->middleware('permission:camiones.edit');
    Route::get('camion/{uuid}/destroy',[App\Http\Controllers\CamionController::class,'destroy'])->name('camiones.destroy')->middleware('permission:camiones.destroy');
    Route::get('camion/{uuid}/ruat',[App\Http\Controllers\CamionController::class,'verRuat'])->name('camiones.ruat')->middleware('permission:camiones.index');
    Route::delete('camion/foto/{foto}',[App\Http\Controllers\CamionController::class,'eliminarFoto'])->name('camiones.foto.destroy')->middleware('permission:camiones.edit');

    //Unidades Propias (camiones de la empresa: documentos, mantenimiento, talleres)
    Route::get('unidades',[App\Http\Controllers\UnidadPropiaController::class,'index'])->name('unidades.index')->middleware('permission:unidades.index');
    Route::post('unidades/marcar',[App\Http\Controllers\UnidadPropiaController::class,'marcar'])->name('unidades.marcar')->middleware('permission:unidades.create');
    Route::get('unidades/{uuid}/desmarcar',[App\Http\Controllers\UnidadPropiaController::class,'desmarcar'])->name('unidades.desmarcar')->middleware('permission:unidades.destroy');
    Route::get('unidades/{uuid}',[App\Http\Controllers\UnidadPropiaController::class,'show'])->name('unidades.show')->middleware('permission:unidades.index');
    Route::post('unidades/{uuid}/kilometraje',[App\Http\Controllers\UnidadPropiaController::class,'actualizarKm'])->name('unidades.km')->middleware('permission:unidades.edit');
    Route::post('unidades/{uuid}/documentos',[App\Http\Controllers\UnidadPropiaController::class,'storeDocumento'])->name('unidades.documentos.store')->middleware('permission:unidades.edit');
    Route::get('unidades/documento/{uuid}/ver',[App\Http\Controllers\UnidadPropiaController::class,'verDocumento'])->name('unidades.documentos.ver')->middleware('permission:unidades.index');
    Route::get('unidades/documento/{uuid}/destroy',[App\Http\Controllers\UnidadPropiaController::class,'destroyDocumento'])->name('unidades.documentos.destroy')->middleware('permission:unidades.destroy');
    Route::post('unidades/{uuid}/mantenimientos',[App\Http\Controllers\UnidadPropiaController::class,'storeMantenimiento'])->name('unidades.mantenimientos.store')->middleware('permission:unidades.edit');
    Route::get('unidades/mantenimiento/{uuid}/comprobante',[App\Http\Controllers\UnidadPropiaController::class,'verComprobante'])->name('unidades.mantenimientos.comprobante')->middleware('permission:unidades.index');
    Route::get('unidades/mantenimiento/{uuid}/destroy',[App\Http\Controllers\UnidadPropiaController::class,'destroyMantenimiento'])->name('unidades.mantenimientos.destroy')->middleware('permission:unidades.destroy');
    Route::post('unidades/{uuid}/plan',[App\Http\Controllers\UnidadPropiaController::class,'storePlan'])->name('unidades.plan.store')->middleware('permission:unidades.edit');
    Route::get('unidades/plan/{uuid}/realizar',[App\Http\Controllers\UnidadPropiaController::class,'realizarPlan'])->name('unidades.plan.realizar')->middleware('permission:unidades.edit');
    Route::get('unidades/plan/{uuid}/destroy',[App\Http\Controllers\UnidadPropiaController::class,'destroyPlan'])->name('unidades.plan.destroy')->middleware('permission:unidades.destroy');
    Route::post('talleres/store',[App\Http\Controllers\UnidadPropiaController::class,'storeTaller'])->name('talleres.store')->middleware('permission:unidades.edit');
    Route::post('talleres/store-ajax',[App\Http\Controllers\UnidadPropiaController::class,'storeTallerAjax'])->name('talleres.store.ajax')->middleware('permission:unidades.edit');
    Route::put('talleres/{uuid}',[App\Http\Controllers\UnidadPropiaController::class,'updateTaller'])->name('talleres.update')->middleware('permission:unidades.edit');
    Route::get('talleres/{uuid}/destroy',[App\Http\Controllers\UnidadPropiaController::class,'destroyTaller'])->name('talleres.destroy')->middleware('permission:unidades.destroy');

    //Operadores de Transporte (propietarios y conductores)
    Route::get('operador/buscar-ci',[App\Http\Controllers\OperadorTransporteController::class,'buscarPorCi'])->name('operadores.buscar-ci')->middleware('permission:operadores.index');
    Route::post('operador/store',[App\Http\Controllers\OperadorTransporteController::class,'store'])->name('operadores.store')->middleware('permission:operadores.create');
    Route::get('operador/{uuid}/edit',[App\Http\Controllers\OperadorTransporteController::class,'edit'])->name('operadores.edit')->middleware('permission:operadores.edit');
    Route::put('operador/{operador}',[App\Http\Controllers\OperadorTransporteController::class,'update'])->name('operadores.update')->middleware('permission:operadores.edit');
    Route::get('operador/{uuid}/destroy',[App\Http\Controllers\OperadorTransporteController::class,'destroy'])->name('operadores.destroy')->middleware('permission:operadores.destroy');
    Route::get('operador/{uuid}/carnet',[App\Http\Controllers\OperadorTransporteController::class,'verCarnet'])->name('operadores.carnet')->middleware('permission:operadores.index');
    Route::get('operador/{uuid}/licencia',[App\Http\Controllers\OperadorTransporteController::class,'verLicencia'])->name('operadores.licencia')->middleware('permission:operadores.index');

    //Contratos
    Route::get('contratos',[App\Http\Controllers\ContratoController::class,'index'])->name('contratos.index')->middleware('permission:contratos.index');
    Route::get('contratos/nuevo-token',[App\Http\Controllers\ContratoController::class,'nuevoToken'])->name('contratos.nuevo-token');
    Route::get('contratos/liquidacion',[App\Http\Controllers\ContratoController::class,'liquidacion'])->name('contratos.liquidacion')->middleware('permission:contratos.liquidacion');
    Route::post('contrato/store',[App\Http\Controllers\ContratoController::class,'store'])->name('contratos.store')->middleware('permission:contratos.create');
    Route::get('contrato/{uuid}/edit',[App\Http\Controllers\ContratoController::class,'edit'])->name('contratos.edit')->middleware('permission:contratos.edit');
    Route::put('contrato/{contrato}',[App\Http\Controllers\ContratoController::class,'update'])->name('contratos.update')->middleware('permission:contratos.edit');
    Route::get('contrato/{uuid}/destroy',[App\Http\Controllers\ContratoController::class,'destroy'])->name('contratos.destroy')->middleware('permission:contratos.destroy');
    Route::get('contrato/{uuid}/camiones',[App\Http\Controllers\ContratoController::class,'camiones'])->name('contratos.camiones')->middleware('permission:contratos.index');
    Route::get('contrato/{uuid}/pdf',[App\Http\Controllers\ContratoController::class,'verPdf'])->name('contratos.pdf')->middleware('permission:contratos.index');
    Route::get('api/contrato/{id}/toneladas',[App\Http\Controllers\ContratoController::class,'toneladas'])->name('contrato.toneladas')->middleware('permission:seguimiento.index');
    Route::get('contrato/{uuid}/cerrar-envios',[App\Http\Controllers\ContratoController::class,'cerrarEnvios'])->name('contratos.cerrar')->middleware('permission:contratos.cerrar');
    Route::get('contrato/{uuid}/descerrar-envios',[App\Http\Controllers\ContratoController::class,'descerrarEnvios'])->name('contratos.descerrar')->middleware('permission:contratos.cerrar');

    //Contrato Camiones
    Route::post('contrato-camion/store',[App\Http\Controllers\ContratoCamionController::class,'store'])->name('contrato-camion.store')->middleware('permission:contrato_camion.create');
    Route::get('contrato-camion/{uuid}/toggle-entrega',[App\Http\Controllers\ContratoCamionController::class,'toggleEntrega'])->name('contrato-camion.toggle-entrega')->middleware('permission:contrato_camion.edit');
    Route::get('contrato-camion/{uuid}/toggle-activo',[App\Http\Controllers\ContratoCamionController::class,'toggleActivo'])->name('contrato-camion.toggle-activo')->middleware('permission:contrato_camion.edit');
    Route::post('contrato-camion/{uuid}/flete',[App\Http\Controllers\ContratoCamionController::class,'actualizarFlete'])->name('contrato-camion.flete')->middleware('permission:contrato_camion.edit');

    //Tramos de transporte
    Route::post('tramo/store',[App\Http\Controllers\TramoController::class,'store'])->name('tramo.store')->middleware('permission:tramo.create');
    Route::get('tramo/{uuid}/edit',[App\Http\Controllers\TramoController::class,'edit'])->name('tramo.edit')->middleware('permission:tramo.edit');
    Route::put('tramo/{uuid}',[App\Http\Controllers\TramoController::class,'update'])->name('tramo.update')->middleware('permission:tramo.edit');
    Route::post('tramo/{uuid}/llegada',[App\Http\Controllers\TramoController::class,'registrarLlegada'])->name('tramo.llegada')->middleware('permission:tramo.edit');
    Route::post('tramo/{uuid}/deshacer-llegada',[App\Http\Controllers\TramoController::class,'deshacerLlegada'])->name('tramo.deshacer_llegada')->middleware('permission:tramo.edit');
    Route::get('tramo/{uuid}/toggle-activo',[App\Http\Controllers\TramoController::class,'toggleActivo'])->name('tramo.toggle-activo')->middleware('permission:tramo.edit');
    Route::get('tramo/{uuid}/nota-entrega',[App\Http\Controllers\TramoController::class,'notaEntrega'])->name('tramo.nota-entrega')->middleware('permission:contratos.index');
    Route::get('tramo/{uuid}/documento-entrega',[App\Http\Controllers\TramoController::class,'verDocumentoEntrega'])->name('tramo.documento-entrega')->middleware('permission:contratos.index');

    // Seguimiento de cargas
    Route::get('seguimiento-cargas',[App\Http\Controllers\SeguimientoCargasController::class,'index'])->name('seguimiento.index')->middleware('permission:seguimiento.index');

    // Empleados
    Route::get('empleados',[App\Http\Controllers\EmpleadoController::class,'index'])->name('empleados.index')->middleware('permission:empleados.index');
    Route::get('empleados/nuevo-token',[App\Http\Controllers\EmpleadoController::class,'nuevoToken'])->name('empleados.nuevo-token');
    Route::post('empleados',[App\Http\Controllers\EmpleadoController::class,'store'])->name('empleados.store')->middleware('permission:empleados.create');
    Route::put('empleados/{uuid}',[App\Http\Controllers\EmpleadoController::class,'update'])->name('empleados.update')->middleware('permission:empleados.edit');
    Route::get('empleados/{uuid}/toggle',[App\Http\Controllers\EmpleadoController::class,'toggleActivo'])->name('empleados.toggle')->middleware('permission:empleados.edit');
    Route::get('empleados/{uuid}/destroy',[App\Http\Controllers\EmpleadoController::class,'destroy'])->name('empleados.destroy')->middleware('permission:empleados.destroy');

    // Bancos y cuentas bancarias (rutas estáticas de cuentas ANTES que las dinámicas de banco)
    Route::get('bancos',[App\Http\Controllers\BancoController::class,'index'])->name('bancos.index')->middleware('permission:bancos.index');
    Route::post('bancos',[App\Http\Controllers\BancoController::class,'store'])->name('bancos.store')->middleware('permission:bancos.create');
    Route::post('bancos/cuentas',[App\Http\Controllers\BancoController::class,'storeCuenta'])->name('bancos.cuenta.store')->middleware('permission:bancos_cuentas.create');
    Route::put('bancos/cuentas/{uuid}',[App\Http\Controllers\BancoController::class,'updateCuenta'])->name('bancos.cuenta.update')->middleware('permission:bancos_cuentas.edit');
    Route::get('bancos/cuentas/{uuid}/destroy',[App\Http\Controllers\BancoController::class,'destroyCuenta'])->name('bancos.cuenta.destroy')->middleware('permission:bancos_cuentas.destroy');
    Route::get('bancos/nuevo-token-banco',[App\Http\Controllers\BancoController::class,'nuevoTokenBanco'])->name('bancos.nuevo-token-banco');
    Route::get('bancos/nuevo-token-cuenta',[App\Http\Controllers\BancoController::class,'nuevoTokenCuenta'])->name('bancos.nuevo-token-cuenta');
    Route::put('bancos/{uuid}',[App\Http\Controllers\BancoController::class,'update'])->name('bancos.update')->middleware('permission:bancos.edit');
    Route::get('bancos/{uuid}/destroy',[App\Http\Controllers\BancoController::class,'destroy'])->name('bancos.destroy')->middleware('permission:bancos.destroy');

    // Pagos de clientes
    Route::get('pagos/clientes',[App\Http\Controllers\PagoClienteController::class,'index'])->name('pagos.clientes.index')->middleware('permission:pagos_clientes.index');
    Route::post('pagos/clientes',[App\Http\Controllers\PagoClienteController::class,'store'])->name('pagos.clientes.store')->middleware('permission:pagos_clientes.create');
    Route::post('pagos/clientes/{id}/precio',[App\Http\Controllers\PagoClienteController::class,'setPrecio'])->name('pagos.clientes.precio')->middleware('permission:pagos_clientes.create');
    Route::get('pagos/clientes/{uuid}/destroy',[App\Http\Controllers\PagoClienteController::class,'destroy'])->name('pagos.clientes.destroy')->middleware('permission:pagos_clientes.destroy');
    Route::put('pagos/clientes/{uuid}',[App\Http\Controllers\PagoClienteController::class,'update'])->name('pagos.clientes.update')->middleware('permission:pagos_clientes.edit');
    Route::post('pagos/clientes/cobro-masivo',[App\Http\Controllers\PagoClienteController::class,'cobroMasivo'])->name('pagos.clientes.cobro_masivo')->middleware('permission:pagos_clientes.create');
    Route::get('api/pagos/clientes/{id}/detalle',[App\Http\Controllers\PagoClienteController::class,'detalle'])->name('pagos.clientes.detalle');
    Route::get('api/pagos/clientes/verificar-codigo',[App\Http\Controllers\PagoClienteController::class,'verificarCodigo'])->name('pagos.clientes.verificar_codigo')->middleware('permission:pagos_clientes.index');
    Route::get('pagos/clientes/{uuid}/voucher',[App\Http\Controllers\PagoClienteController::class,'verVoucher'])->name('pagos.clientes.voucher')->middleware('permission:pagos_clientes.index');
    Route::get('api/pagos/cuentas-cliente',[App\Http\Controllers\PagoClienteController::class,'cuentasCliente'])->name('pagos.cuentas-cliente');

    // Pagos a proveedores
    Route::get('pagos/proveedores',[App\Http\Controllers\PagoProveedorController::class,'index'])->name('pagos.proveedores.index')->middleware('permission:pagos_proveedores.index');
    Route::post('pagos/proveedores',[App\Http\Controllers\PagoProveedorController::class,'store'])->name('pagos.proveedores.store')->middleware('permission:pagos_proveedores.create');
    Route::get('pagos/proveedores/pago-masivo',[App\Http\Controllers\PagoProveedorController::class,'pagoMasivoView'])->name('pagos.proveedores.pago_masivo')->middleware('permission:pagos_proveedores.create');
    Route::post('pagos/proveedores/pago-masivo',[App\Http\Controllers\PagoProveedorController::class,'pagoMasivoStore'])->name('pagos.proveedores.pago_masivo.store')->middleware('permission:pagos_proveedores.create');
    Route::get('pagos/proveedores/{uuid}/destroy',[App\Http\Controllers\PagoProveedorController::class,'destroy'])->name('pagos.proveedores.destroy')->middleware('permission:pagos_proveedores.destroy');
    Route::put('pagos/proveedores/{uuid}',[App\Http\Controllers\PagoProveedorController::class,'update'])->name('pagos.proveedores.update')->middleware('permission:pagos_proveedores.edit');
    Route::get('api/pagos/proveedores/{id}/detalle',[App\Http\Controllers\PagoProveedorController::class,'detalle'])->name('pagos.proveedores.detalle');
    Route::get('pagos/proveedores/{uuid}/voucher',[App\Http\Controllers\PagoProveedorController::class,'verVoucher'])->name('pagos.proveedores.voucher')->middleware('permission:pagos_proveedores.index');
    Route::get('api/pagos/cuentas-proveedor',[App\Http\Controllers\PagoProveedorController::class,'cuentasProveedor'])->name('pagos.cuentas-proveedor');

    // Lotes de pago masivo
    Route::get('lotes-pago', [App\Http\Controllers\LotePagoController::class, 'index'])->name('lotes_pago.index')->middleware('permission:lotes_pago.index');
    Route::get('api/lotes-pago/verificar-codigo', [App\Http\Controllers\LotePagoController::class, 'verificarCodigo'])->name('lotes_pago.verificar_codigo')->middleware('permission:lotes_pago.index');
    Route::post('lotes-pago/{uuid}/codigo', [App\Http\Controllers\LotePagoController::class, 'actualizarCodigo'])->name('lotes_pago.codigo')->middleware('permission:lotes_pago.edit');
    Route::delete('lotes-pago/{uuid}', [App\Http\Controllers\LotePagoController::class, 'destroy'])->name('lotes_pago.destroy')->middleware('permission:lotes_pago.destroy');
    Route::get('api/lotes-pago/{uuid}/detalle', [App\Http\Controllers\LotePagoController::class, 'detalle'])->name('lotes_pago.detalle')->middleware('permission:lotes_pago.index');

    // Lotes de entrega semanal por proveedor
    Route::get('lotes-entrega', [App\Http\Controllers\LoteEntregaController::class, 'index'])->name('lotes_entrega.index')->middleware('permission:lotes_entrega.index');
    Route::post('lotes-entrega', [App\Http\Controllers\LoteEntregaController::class, 'store'])->name('lotes_entrega.store')->middleware('permission:lotes_entrega.index');
    Route::post('lotes-entrega/ajax', [App\Http\Controllers\LoteEntregaController::class, 'storeAjax'])->name('lotes_entrega.store.ajax')->middleware('permission:lotes_entrega.index');
    Route::get('lotes-entrega/proveedor/{proveedorId}', [App\Http\Controllers\LoteEntregaController::class, 'lotesProveedor'])->name('lotes_entrega.proveedor')->middleware('permission:lotes_entrega.index');
    Route::post('lotes-entrega/{uuid}/cerrar', [App\Http\Controllers\LoteEntregaController::class, 'cerrar'])->name('lotes_entrega.cerrar')->middleware('permission:lotes_entrega.cerrar');
    // Pagos extras por lote
    Route::post('lotes-entrega/{uuid}/pago-extra', [App\Http\Controllers\PagoExtraLoteController::class, 'store'])->name('lotes_entrega.pago_extra.store')->middleware('permission:lotes_entrega.pago');
    Route::delete('lotes-entrega/pago-extra/{uuid}', [App\Http\Controllers\PagoExtraLoteController::class, 'destroy'])->name('lotes_entrega.pago_extra.destroy')->middleware('permission:lotes_entrega.pago');

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
    Route::get('conductor/{uuid}/reiniciar',[App\Http\Controllers\CamionConductorController::class,'reiniciarAsignacion'])->name('conductores.reiniciar')->middleware('permission:conductores.edit');

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
    
    //Report
    Route::get('Reports',[App\Http\Controllers\NewReportController::class, 'index'])->name('newReports.index')->middleware('permission:reportes.index');
    Route::get('/reportes/export', [App\Http\Controllers\NewReportController::class, 'export'])->name('newReports.export')->middleware('can:reportes.export');
    //Reportes
    Route::get('/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index')->middleware('permission:reportes.index');
    Route::get('/reportes/exportar-excel', [App\Http\Controllers\ReporteController::class,'exportarExcel'])->name('reportes.exportar.excel')->middleware('permission:reportes.export');
    Route::get('reportes/capital-utilidad',[App\Http\Controllers\ReporteController::class, 'capitalUtilidad'])->name('reportes.capital_utilidad')->middleware('permission:reportes.capital_utilidad');
    Route::get('reportes/capital-utilidad/excel',[App\Http\Controllers\ReporteController::class, 'capitalUtilidadExcel'])->name('reportes.capital_utilidad.excel')->middleware('permission:reportes.capital_utilidad');
    // Empresas (tesorería)
    Route::get('empresas', [App\Http\Controllers\EmpresaController::class, 'index'])->name('empresas.index')->middleware('permission:empresas.index');
    Route::get('empresas/nuevo-token',[App\Http\Controllers\EmpresaController::class,'nuevoToken'])->name('empresas.nuevo-token');
    Route::get('empresas/nuevo-token-cuenta',[App\Http\Controllers\EmpresaController::class,'nuevoTokenCuenta'])->name('empresas.nuevo-token-cuenta');
    Route::post('empresas/store', [App\Http\Controllers\EmpresaController::class, 'store'])->name('empresas.store')->middleware('permission:empresas.create');
    Route::get('empresas/cuenta/{uuid}/edit', [App\Http\Controllers\EmpresaController::class, 'editCuenta'])->name('empresas.cuenta.edit')->middleware('permission:cuentas_bancarias.edit');
    Route::put('empresas/cuenta/{uuid}', [App\Http\Controllers\EmpresaController::class, 'updateCuenta'])->name('empresas.cuenta.update')->middleware('permission:cuentas_bancarias.edit');
    Route::get('empresas/cuenta/{uuid}/destroy', [App\Http\Controllers\EmpresaController::class, 'destroyCuenta'])->name('empresas.cuenta.destroy')->middleware('permission:cuentas_bancarias.destroy');
    Route::get('empresas/{uuid}/edit', [App\Http\Controllers\EmpresaController::class, 'edit'])->name('empresas.edit')->middleware('permission:empresas.edit');
    Route::put('empresas/{uuid}', [App\Http\Controllers\EmpresaController::class, 'update'])->name('empresas.update')->middleware('permission:empresas.edit');
    Route::get('empresas/{uuid}/destroy', [App\Http\Controllers\EmpresaController::class, 'destroy'])->name('empresas.destroy')->middleware('permission:empresas.destroy');
    Route::get('empresas/{uuid}/cuentas', [App\Http\Controllers\EmpresaController::class, 'cuentas'])->name('empresas.cuentas')->middleware('permission:cuentas_bancarias.index');
    Route::post('empresas/{uuid}/cuentas/store', [App\Http\Controllers\EmpresaController::class, 'storeCuenta'])->name('empresas.cuentas.store')->middleware('permission:cuentas_bancarias.create');

    // Movimientos (tesorería)
    Route::get('tesoreria', [App\Http\Controllers\MovimientoController::class, 'index'])->name('tesoreria.index')->middleware('permission:tesoreria.index');
    Route::get('tesoreria/cuenta/{uuid}', [App\Http\Controllers\MovimientoController::class, 'porCuenta'])->name('tesoreria.cuenta')->middleware('permission:tesoreria.index');
    Route::post('tesoreria/movimiento/store', [App\Http\Controllers\MovimientoController::class, 'store'])->name('tesoreria.movimiento.store')->middleware('permission:tesoreria.create');
    Route::get('tesoreria/movimiento/{uuid}/destroy', [App\Http\Controllers\MovimientoController::class, 'destroy'])->name('tesoreria.movimiento.destroy')->middleware('permission:tesoreria.destroy');

    // Créditos y Adquisiciones (bienes por crédito bancario o capital, plan de pagos)
    Route::get('adquisiciones',[App\Http\Controllers\AdquisicionController::class,'index'])->name('adquisiciones.index')->middleware('permission:adquisiciones.index');
    Route::post('adquisiciones/store',[App\Http\Controllers\AdquisicionController::class,'store'])->name('adquisiciones.store')->middleware('permission:adquisiciones.create');
    Route::get('adquisiciones/cuota/{uuid}/anular-pago',[App\Http\Controllers\AdquisicionController::class,'anularPago'])->name('adquisiciones.cuotas.anular')->middleware('permission:adquisiciones.destroy');
    Route::get('adquisiciones/cuota/{uuid}/comprobante',[App\Http\Controllers\AdquisicionController::class,'verComprobante'])->name('adquisiciones.cuotas.comprobante')->middleware('permission:adquisiciones.index');
    Route::get('adquisiciones/cuota/{uuid}/destroy',[App\Http\Controllers\AdquisicionController::class,'destroyCuota'])->name('adquisiciones.cuotas.destroy')->middleware('permission:adquisiciones.destroy');
    Route::post('adquisiciones/cuota/{uuid}/pagar',[App\Http\Controllers\AdquisicionController::class,'pagarCuota'])->name('adquisiciones.cuotas.pagar')->middleware('permission:adquisiciones.edit');
    Route::put('adquisiciones/cuota/{uuid}',[App\Http\Controllers\AdquisicionController::class,'updateCuota'])->name('adquisiciones.cuotas.update')->middleware('permission:adquisiciones.edit');
    Route::get('adquisiciones/factura/{uuid}/ver',[App\Http\Controllers\AdquisicionController::class,'verFactura'])->name('adquisiciones.facturas.ver')->middleware('permission:adquisiciones.index');
    Route::get('adquisiciones/factura/{uuid}/destroy',[App\Http\Controllers\AdquisicionController::class,'destroyFactura'])->name('adquisiciones.facturas.destroy')->middleware('permission:adquisiciones.destroy');
    Route::get('adquisiciones/{uuid}',[App\Http\Controllers\AdquisicionController::class,'show'])->name('adquisiciones.show')->middleware('permission:adquisiciones.index');
    Route::put('adquisiciones/{uuid}',[App\Http\Controllers\AdquisicionController::class,'update'])->name('adquisiciones.update')->middleware('permission:adquisiciones.edit');
    Route::get('adquisiciones/{uuid}/destroy',[App\Http\Controllers\AdquisicionController::class,'destroy'])->name('adquisiciones.destroy')->middleware('permission:adquisiciones.destroy');
    Route::post('adquisiciones/{uuid}/generar-plan',[App\Http\Controllers\AdquisicionController::class,'generarPlan'])->name('adquisiciones.generar_plan')->middleware('permission:adquisiciones.edit');
    Route::post('adquisiciones/{uuid}/cuotas',[App\Http\Controllers\AdquisicionController::class,'storeCuota'])->name('adquisiciones.cuotas.store')->middleware('permission:adquisiciones.edit');
    Route::post('adquisiciones/{uuid}/facturas',[App\Http\Controllers\AdquisicionController::class,'storeFactura'])->name('adquisiciones.facturas.store')->middleware('permission:adquisiciones.edit');

    // Préstamos internos
    Route::get('prestamos-internos', [App\Http\Controllers\PrestamoInternoController::class, 'index'])->name('prestamos_internos.index')->middleware('permission:prestamos_internos.index');
    Route::post('prestamos-internos/store', [App\Http\Controllers\PrestamoInternoController::class, 'store'])->name('prestamos_internos.store')->middleware('permission:prestamos_internos.create');
    Route::post('prestamos-internos/{uuid}/devolver', [App\Http\Controllers\PrestamoInternoController::class, 'devolver'])->name('prestamos_internos.devolver')->middleware('permission:prestamos_internos.create');

    // Parámetros
    Route::get('parametros', [App\Http\Controllers\ParametroController::class, 'index'])->name('parametros.index')->middleware('permission:parametros.index');
    Route::post('parametros/store', [App\Http\Controllers\ParametroController::class, 'store'])->name('parametros.store')->middleware('permission:parametros.create');
    Route::post('parametros/store-ajax', [App\Http\Controllers\ParametroController::class, 'storeAjax'])->name('parametros.store.ajax')->middleware('permission:parametros.create');
    Route::get('parametros/{uuid}/edit', [App\Http\Controllers\ParametroController::class, 'edit'])->name('parametros.edit')->middleware('permission:parametros.edit');
    Route::put('parametros/{uuid}', [App\Http\Controllers\ParametroController::class, 'update'])->name('parametros.update')->middleware('permission:parametros.edit');
    Route::get('parametros/{uuid}/destroy', [App\Http\Controllers\ParametroController::class, 'destroy'])->name('parametros.destroy')->middleware('permission:parametros.destroy');
   });
