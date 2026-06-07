<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatosPruebaReducidoSeeder extends Seeder
{
    // Cambia estos números si quieres más o menos datos.
    private int $cantEmpleados = 5;
    private int $cantClientes = 8;
    private int $cantProveedores = 8;
    private int $cantOperadores = 8;
    private int $cantCamiones = 8;
    private int $cantContratos = 8;

    private array $nombres = ['Juan','Pedro','Luis','Carlos','Miguel','Mario','Jorge','Raul','Diego','Marco','Daniel','Gustavo','Jose','Roberto','Victor','Andres','Ana','Maria','Rosa','Lucia','Carmen','Paola','Daniela','Gabriela','Valeria'];
    private array $apellidos = ['Mamani','Quispe','Choque','Condori','Flores','Vargas','Rojas','Gutierrez','Lopez','Martinez','Fernandez','Torrez','Rivera','Callisaya','Apaza','Velasco'];
    private array $ciudades = ['La Paz','El Alto','Oruro','Cochabamba','Santa Cruz','Sucre','Potosi','Tarija','Desaguadero','Pisiga','Arica','Iquique','Corumba','Puerto Suarez'];
    private array $monedas = ['BOB','USD'];
    private array $metodosPago = ['efectivo','transferencia','qr','cheque'];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->ensureParametros();
        $this->ensureRoleSuperAdmin();

        $paises = $this->idsParametro('paises');
        $paisDoc = $this->idsParametro('pais_documento');
        $marcas = $this->idsParametro('camion_marca');
        $tiposCamion = $this->idsParametro('camion_tipo');
        $cargos = $this->idsParametro('cargo_empleados');
        $sucursales = $this->descripcionesParametro('sucursal_cuenta');

        $boliviaId = $this->idParametro('paises', 'BOLIVIA') ?? ($paises[0] ?? null);
        $paisDocBoliviaId = $this->idParametro('pais_documento', 'BOLIVIA') ?? ($paisDoc[0] ?? null);

        $bancos = $this->crearBancos($boliviaId);
        $empresas = $this->crearEmpresas();
        $cuentasEmpresa = $this->crearCuentasEmpresa($empresas, $bancos);
        $empleados = $this->crearEmpleadosUsuarios($cargos);
        $clientes = $this->crearClientes($paises);
        $proveedores = $this->crearProveedores($paises);
        $operadores = $this->crearOperadores($paisDocBoliviaId);
        $camiones = $this->crearCamiones($operadores, $paisDocBoliviaId, $marcas, $tiposCamion);

        $cuentasBancarias = [];
        $cuentasBancarias = array_merge($cuentasBancarias, $this->crearCuentasBancarias('cliente', $clientes, 'App\\Models\\Cliente', $bancos, $sucursales));
        $cuentasBancarias = array_merge($cuentasBancarias, $this->crearCuentasBancarias('proveedor', $proveedores, 'App\\Models\\Proveedor', $bancos, $sucursales));
        $cuentasBancarias = array_merge($cuentasBancarias, $this->crearCuentasBancarias('operador', $operadores, 'App\\Models\\OperadorTransporte', $bancos, $sucursales));
        $cuentasBancarias = array_merge($cuentasBancarias, $this->crearCuentasBancarias('empleado', $empleados, 'App\\Models\\Empleado', $bancos, $sucursales));

        $contratos = $this->crearContratos($clientes, $proveedores);
        [$contratoCamiones, $tramos] = $this->crearLogistica($contratos, $camiones, $operadores, $clientes);

        $lotesProveedor = $this->crearLotesPago('proveedor', $cuentasEmpresa, 4);
        $lotesCamion = $this->crearLotesPago('camion', $cuentasEmpresa, 4);

        $this->crearPagosProveedor($contratos, $cuentasEmpresa, $cuentasBancarias, $lotesProveedor);
        $this->crearPagosCamion($contratoCamiones, $operadores, $cuentasEmpresa, $cuentasBancarias, $lotesCamion);
        $this->crearPagosCliente($tramos, $cuentasEmpresa, $cuentasBancarias);
        $this->crearGastosExtras($contratos, $cuentasBancarias, $cuentasEmpresa);
        $this->crearMovimientosSueltos($cuentasEmpresa);
        $this->crearPrestamosInternos($cuentasEmpresa);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function uuid(): string
    {
        return (string) Str::uuid();
    }

    private function pick(array $array)
    {
        return $array[array_rand($array)];
    }

    private function money(float $min, float $max): float
    {
        return round(mt_rand((int)($min * 100), (int)($max * 100)) / 100, 2);
    }

    private function dateBetween(int $daysBack = 240, int $daysForward = 30): string
    {
        return Carbon::now()->subDays(rand(0, $daysBack))->addDays(rand(0, $daysForward))->format('Y-m-d');
    }

    private function ensureParametros(): void
    {
        $data = [
            ['paises', 'Bolivia', 'BOLIVIA'], ['paises', 'Brasil', 'BRASIL'], ['paises', 'Chile', 'CHILE'], ['paises', 'Argentina', 'ARGENTINA'], ['paises', 'Paraguay', 'PARAGUAY'],
            ['pais_documento', 'Bolivia', 'BOLIVIA'], ['pais_documento', 'Brasil', 'BRASIL'], ['pais_documento', 'Chile', 'CHILE'], ['pais_documento', 'Argentina', 'ARGENTINA'], ['pais_documento', 'Paraguay', 'PARAGUAY'],
            ['camion_marca', 'Mercedes-Benz', 'MERCEDES-BENZ'], ['camion_marca', 'Volvo', 'VOLVO'], ['camion_marca', 'Scania', 'SCANIA'], ['camion_marca', 'Freightliner', 'FREIGHTLINER'], ['camion_marca', 'Hino', 'HINO'], ['camion_marca', 'JAC', 'JAC'],
            ['camion_tipo', 'Camión Tracto', 'TRACTO'], ['camion_tipo', 'Camión Semirremolque', 'SEMIRREMOLQUE'], ['camion_tipo', 'Camión Plataforma', 'PLATAFORMA'], ['camion_tipo', 'Camión Volqueta', 'VOLQUETA'], ['camion_tipo', 'Camión Furgón', 'FURGON'],
            ['cargo_empleados', 'Dueño', 'DUEÑO'], ['cargo_empleados', 'Administrador', 'ADMINISTRADOR'], ['cargo_empleados', 'Contador', 'CONTADOR'], ['cargo_empleados', 'Operador Logístico', 'OPERADOR LOGÍSTICO'], ['cargo_empleados', 'Asistente', 'ASISTENTE'],
            ['sucursal_cuenta', 'La Paz', 'LPZ'], ['sucursal_cuenta', 'Cochabamba', 'CBB'], ['sucursal_cuenta', 'Santa Cruz', 'SCZ'], ['sucursal_cuenta', 'Oruro', 'ORU'], ['sucursal_cuenta', 'Tarija', 'TJA'],
        ];

        foreach ($data as [$tipo, $descripcion, $valor]) {
            DB::table('parametros')->updateOrInsert(
                ['tipo' => $tipo, 'valor' => $valor],
                ['uuid' => $this->uuid(), 'descripcion' => $descripcion, 'created_at' => $this->now(), 'updated_at' => $this->now()]
            );
        }
    }

    private function ensureRoleSuperAdmin(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('roles')) return;

        DB::table('roles')->updateOrInsert(
            ['name' => 'Superadministrador', 'guard_name' => 'web'],
            ['uuid' => $this->uuid(), 'descripcion' => 'Rol principal de pruebas', 'created_at' => $this->now(), 'updated_at' => $this->now()]
        );
    }

    private function idsParametro(string $tipo): array
    {
        return DB::table('parametros')->where('tipo', $tipo)->whereNull('deleted_at')->pluck('id')->all();
    }

    private function descripcionesParametro(string $tipo): array
    {
        return DB::table('parametros')->where('tipo', $tipo)->whereNull('deleted_at')->pluck('descripcion')->all() ?: ['La Paz'];
    }

    private function idParametro(string $tipo, string $valor): ?int
    {
        $id = DB::table('parametros')->where('tipo', $tipo)->where('valor', $valor)->whereNull('deleted_at')->value('id');
        return $id ? (int) $id : null;
    }

    private function crearBancos(?int $paisId): array
    {
        $nombres = ['Banco Union','Banco Nacional de Bolivia','Banco Mercantil Santa Cruz','Banco BISA','Banco FIE','Banco Economico','Banco Ganadero','Banco Fortaleza','BCP Bolivia','Banco Sol'];
        $ids = [];
        foreach ($nombres as $i => $nombre) {
            DB::table('bancos')->updateOrInsert(
                ['nombre' => $nombre],
                [
                    'uuid' => $this->uuid(), 'pais_id' => $paisId, 'codigo_swift' => 'BOL' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'codigo_banco' => str_pad($i + 1, 3, '0', STR_PAD_LEFT), 'activo' => 1,
                    'created_at' => $this->now(), 'updated_at' => $this->now(),
                ]
            );
            $ids[] = (int) DB::table('bancos')->where('nombre', $nombre)->value('id');
        }
        return $ids;
    }

    private function crearEmpresas(): array
    {
        $ids = [];
        for ($i = 1; $i <= 2; $i++) {
            $ids[] = DB::table('empresas')->insertGetId([
                'uuid' => $this->uuid(),
                'nombre' => "Empresa Chatarra {$i}",
                'nit' => 'EMP' . str_pad($i, 7, '0', STR_PAD_LEFT),
                'razon_social' => "Empresa Chatarra {$i} S.R.L.",
                'direccion' => $this->pick($this->ciudades) . ' zona central',
                'telefono' => '7' . rand(1000000, 9999999),
                'email' => "empresa{$i}@demo.com",
                'activo' => 1,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearCuentasEmpresa(array $empresas, array $bancos): array
    {
        $ids = [];
        foreach ($empresas as $empresaId) {
            foreach (['BOB', 'USD'] as $moneda) {
                $saldo = $this->money(50000, 350000);
                $ids[] = DB::table('cuentas_empresa')->insertGetId([
                    'uuid' => $this->uuid(), 'empresa_id' => $empresaId, 'nombre_cuenta' => "Cuenta {$moneda}",
                    'banco_id' => $this->pick($bancos), 'numero_cuenta' => (string) rand(100000000000, 999999999999),
                    'moneda' => $moneda, 'saldo_inicial' => $saldo, 'saldo_actual' => $saldo,
                    'activo' => 1, 'descripcion' => 'Cuenta creada por seeder',
                    'created_at' => $this->now(), 'updated_at' => $this->now(),
                ]);
            }
        }
        return $ids;
    }

    private function crearEmpleadosUsuarios(array $cargos): array
    {
        $empleados = [];
        $roleId = DB::table('roles')->where('name', 'Superadministrador')->value('id');

        for ($i = 1; $i <= $this->cantEmpleados; $i++) {
            $nombre = $this->pick($this->nombres);
            $ap = $this->pick($this->apellidos);
            $am = $this->pick($this->apellidos);

            $empleadoId = DB::table('empleados')->insertGetId([
                'uuid' => $this->uuid(), 'nombre' => $nombre, 'apellido_paterno' => $ap, 'apellido_materno' => $am,
                'ci' => (string) rand(1000000, 9999999), 'cargo_id' => $cargos ? $this->pick($cargos) : null,
                'telefono' => '7' . rand(1000000, 9999999), 'email' => "empleado{$i}@demo.com", 'activo' => 1,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $empleados[] = $empleadoId;

            $userId = DB::table('users')->insertGetId([
                'uuid' => $this->uuid(), 'name' => "{$nombre} {$ap}", 'email' => "usuario{$i}@demo.com",
                'empleado_id' => $empleadoId, 'email_verified_at' => $this->now(),
                'password' => Hash::make('12345678'), 'estado' => 1,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);

            if ($roleId && DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }
        }

        return $empleados;
    }

    private function crearClientes(array $paises): array
    {
        $ids = [];
        for ($i = 1; $i <= $this->cantClientes; $i++) {
            $ids[] = DB::table('clientes')->insertGetId([
                'uuid' => $this->uuid(),
                'nombre' => 'Cliente Metalurgico ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nit' => 'CLI' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'pais_id' => $paises ? $this->pick($paises) : null,
                'email' => "cliente{$i}@demo.com",
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        $this->crearContactos('App\\Models\\Cliente', $ids);
        return $ids;
    }

    private function crearProveedores(array $paises): array
    {
        $tipos = ['CHatarra ferrosa','Chatarra mixta','Aluminio','Cobre','Baterias','Bronce','Acero'];
        $ids = [];
        for ($i = 1; $i <= $this->cantProveedores; $i++) {
            $ids[] = DB::table('proveedors')->insertGetId([
                'uuid' => $this->uuid(),
                'nombre' => 'Proveedor Reciclaje ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nit' => 'PROV' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'pais_id' => $paises ? $this->pick($paises) : null,
                'email' => "proveedor{$i}@demo.com",
                'tipo_producto' => $this->pick($tipos),
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        $this->crearContactos('App\\Models\\Proveedor', $ids);
        return $ids;
    }

    private function crearContactos(string $type, array $ids): void
    {
        foreach ($ids as $id) {
            DB::table('contactos')->insert([
                ['uuid' => $this->uuid(), 'tipo' => 'telefono', 'valor' => '7' . rand(1000000, 9999999), 'contactable_id' => $id, 'contactable_type' => $type, 'created_at' => $this->now(), 'updated_at' => $this->now()],
                ['uuid' => $this->uuid(), 'tipo' => 'direccion', 'valor' => $this->pick($this->ciudades) . ' - Zona Industrial', 'contactable_id' => $id, 'contactable_type' => $type, 'created_at' => $this->now(), 'updated_at' => $this->now()],
            ]);
        }
    }

    private function crearOperadores(?int $paisDocId): array
    {
        $ids = [];
        for ($i = 1; $i <= $this->cantOperadores; $i++) {
            $tipo = $this->pick(['propietario','chofer','ambos']);
            $ids[] = DB::table('operadores_transporte')->insertGetId([
                'uuid' => $this->uuid(), 'nombre' => $this->pick($this->nombres),
                'apellido_paterno' => $this->pick($this->apellidos), 'apellido_materno' => $this->pick($this->apellidos),
                'ci' => (string) rand(1000000, 9999999), 'ci_pais_id' => $paisDocId,
                'telefono' => '7' . rand(1000000, 9999999), 'email' => "operador{$i}@demo.com",
                'direccion' => $this->pick($this->ciudades) . ' zona transporte',
                'tipo_operador' => $tipo,
                'licencia_numero' => in_array($tipo, ['chofer','ambos']) ? 'LIC-' . rand(10000, 99999) : null,
                'licencia_pais_id' => $paisDocId,
                'licencia_vencimiento' => Carbon::now()->addDays(rand(180, 1500))->format('Y-m-d'),
                'estado' => rand(1, 100) <= 92 ? 'Activo' : 'Inactivo',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearCamiones(array $operadores, ?int $paisId, array $marcas, array $tipos): array
    {
        $ids = [];
        $colores = ['Blanco','Rojo','Azul','Verde','Gris','Negro','Amarillo'];
        for ($i = 1; $i <= $this->cantCamiones; $i++) {
            $propietario = $this->pick($operadores);
            $camionId = DB::table('camiones')->insertGetId([
                'uuid' => $this->uuid(), 'placa' => rand(1000,9999) . '-' . strtoupper(Str::random(3)),
                'placa_pais_id' => $paisId, 'tipo_vehiculo_id' => $tipos ? $this->pick($tipos) : null,
                'marca_id' => $marcas ? $this->pick($marcas) : null, 'modelo' => 'Modelo ' . rand(2010, 2026),
                'anio' => rand(2010, 2026), 'capacidad_kg' => rand(18000, 35000), 'color' => $this->pick($colores),
                'estado' => rand(1, 100) <= 88 ? 'Activo' : $this->pick(['Inactivo','En mantenimiento']),
                'propietario_id' => $propietario,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $ids[] = $camionId;

            $conductor = $this->pick($operadores);
            DB::table('camion_conductores')->insert([
                'uuid' => $this->uuid(), 'camion_id' => $camionId, 'conductor_id' => $conductor,
                'fecha_inicio' => Carbon::now()->subDays(rand(20, 500))->format('Y-m-d'), 'fecha_fin' => null,
                'observaciones' => 'Asignación activa creada por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);

            DB::table('camion_fotos')->insert([
                'camion_id' => $camionId, 'ruta' => 'camiones/demo-camion-' . rand(1, 8) . '.jpg',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearCuentasBancarias(string $tipo, array $titulares, string $type, array $bancos, array $sucursales): array
    {
        $ids = [];
        foreach ($titulares as $i => $titularId) {
            if (rand(1, 100) > 70) continue;
            $nombre = $this->pick($this->nombres);
            $ap = $this->pick($this->apellidos);
            $am = $this->pick($this->apellidos);
            $ids[] = DB::table('cuentas_bancarias')->insertGetId([
                'uuid' => $this->uuid(), 'banco_id' => $this->pick($bancos), 'tipo_titular' => $tipo,
                'titular_id' => $titularId, 'titular_type' => $type,
                'numero_cuenta' => (string) rand(100000000000, 999999999999),
                'moneda' => $this->pick($this->monedas), 'alias' => "Cuenta {$tipo} " . ($i + 1),
                'nombre_titular' => $nombre, 'apellido_paterno_titular' => $ap, 'apellido_materno_titular' => $am,
                'nro_documento' => (string) rand(1000000, 9999999), 'email_notificacion' => strtolower("{$nombre}.{$ap}{$i}@demo.com"),
                'sucursal_departamento' => $this->pick($sucursales), 'tipo_relacion' => 'Titular', 'activo' => 1,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearContratos(array $clientes, array $proveedores): array
    {
        $ids = [];
        for ($i = 1; $i <= $this->cantContratos; $i++) {
            $fechaInicio = Carbon::now()->subDays(rand(1, 360));
            $toneladas = round(rand(15000, 60000) / 1000, 3);
            $precio = $this->money(600, 1800);
            $moneda = $this->pick($this->monedas);
            $ids[] = DB::table('contratos')->insertGetId([
                'uuid' => $this->uuid(), 'numero_contrato' => 'CTR-' . date('Y') . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'tipo_contrato' => $this->pick(['Nacional','Internacional']),
                'cliente_id' => $this->pick($clientes), 'proveedor_id' => $this->pick($proveedores),
                'fecha_inicio' => $fechaInicio->format('Y-m-d'), 'fecha_fin' => (clone $fechaInicio)->addDays(rand(30, 120))->format('Y-m-d'),
                'cantidad_camiones' => rand(1, 8), 'toneladas_contrato' => $toneladas,
                'monto_total' => round($toneladas * $precio, 2), 'moneda' => $moneda,
                'estado' => rand(1, 100) <= 75 ? 'Activo' : 'Concluido', 'envios_cerrados' => 0,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearLogistica(array $contratos, array $camiones, array $operadores, array $clientes): array
    {
        $contratoCamiones = [];
        $tramos = [];
        foreach ($contratos as $contratoId) {
            $n = rand(1, 2);
            for ($j = 1; $j <= $n; $j++) {
                $ton = round(rand(18000, 34000) / 1000, 3);
                $camionId = $this->pick($camiones);
                $conductorId = $this->pick($operadores);
                $estadoEntrega = rand(1, 100) <= 65 ? 'Entregado' : 'Pendiente';

                $ccId = DB::table('contrato_camiones')->insertGetId([
                    'uuid' => $this->uuid(), 'contrato_id' => $contratoId, 'camion_id' => $camionId, 'conductor_id' => $conductorId,
                    'toneladas' => $ton, 'monto_acordado' => $this->money(2000, 9000), 'moneda_flete' => $this->pick($this->monedas),
                    'fecha_asignacion' => $this->dateBetween(180, 0), 'estado_entrega' => $estadoEntrega,
                    'activo' => 1, 'observaciones' => 'Asignación generada por seeder',
                    'created_at' => $this->now(), 'updated_at' => $this->now(),
                ]);
                $contratoCamiones[] = $ccId;

                $salida = Carbon::parse($this->dateBetween(160, 0));
                $pesoSalida = $ton;
                $pesoLlegada = $estadoEntrega === 'Entregado' ? max(0, $pesoSalida - round(rand(0, 800) / 1000, 3)) : null;
                $tramoId = DB::table('tramos')->insertGetId([
                    'uuid' => $this->uuid(), 'contrato_camion_id' => $ccId, 'tramo_padre_id' => null,
                    'camion_id' => $camionId, 'conductor_id' => $conductorId, 'cliente_id' => $this->pick($clientes),
                    'origen' => $this->pick($this->ciudades), 'destino' => $this->pick($this->ciudades),
                    'tipo_tramo' => $this->pick(['Nacional','Internacional']),
                    'peso_declarado' => $pesoSalida, 'peso_salida' => $pesoSalida, 'peso_llegada' => $pesoLlegada,
                    'precio_por_tonelada' => $this->money(900, 2500), 'moneda_venta' => $this->pick($this->monedas),
                    'descuento_porcentaje' => rand(1,100) <= 15 ? rand(1, 5) : null,
                    'fecha_salida' => $salida->format('Y-m-d'),
                    'fecha_llegada' => $pesoLlegada ? (clone $salida)->addDays(rand(2, 10))->format('Y-m-d') : null,
                    'estado' => $estadoEntrega === 'Entregado' ? 'Entregado' : $this->pick(['En ruta','Transbordando']),
                    'activo' => 1, 'observaciones' => 'Tramo raíz generado por seeder',
                    'created_at' => $this->now(), 'updated_at' => $this->now(),
                ]);
                $tramos[] = $tramoId;
            }
        }
        return [$contratoCamiones, $tramos];
    }

    private function crearLotesPago(string $tipo, array $cuentasEmpresa, int $cantidad): array
    {
        $ids = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $ids[] = DB::table('lotes_pago')->insertGetId([
                'uuid' => $this->uuid(), 'tipo' => $tipo,
                'codigo_provisional' => strtoupper($tipo) . '-PROV-' . str_pad((string)$i, 5, '0', STR_PAD_LEFT),
                'codigo_real' => rand(1,100) <= 60 ? strtoupper($tipo) . '-REAL-' . str_pad((string)$i, 5, '0', STR_PAD_LEFT) : null,
                'fecha_pago' => $this->dateBetween(120, 0), 'metodo_pago' => $this->pick($this->metodosPago),
                'cuenta_origen_id' => $this->pick($cuentasEmpresa), 'observaciones' => 'Lote generado por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
        return $ids;
    }

    private function crearPagosProveedor(array $contratos, array $cuentasEmpresa, array $cuentasBancarias, array $lotes): void
    {
        foreach ($contratos as $contratoId) {
            if (rand(1,100) > 75) continue;
            $monto = $this->money(1000, 15000);
            $moneda = $this->pick($this->monedas);
            $tipoCambio = $moneda === 'BOB' ? 1 : 6.96;
            $pagoId = DB::table('pagos_proveedor')->insertGetId([
                'uuid' => $this->uuid(), 'lote_pago_id' => $this->pick($lotes), 'contrato_id' => $contratoId,
                'tipo_pago' => $this->pick(['adelanto','parcial','pago_final']), 'monto' => $monto,
                'moneda_pago' => $moneda, 'tipo_cambio' => $tipoCambio, 'fecha_pago' => $this->dateBetween(90, 0),
                'metodo_pago' => $this->pick($this->metodosPago), 'codigo_seguimiento' => 'PP-' . strtoupper(Str::random(8)),
                'cuenta_origen_id' => $this->pick($cuentasEmpresa), 'cuenta_destino_id' => $cuentasBancarias ? $this->pick($cuentasBancarias) : null,
                'observaciones' => 'Pago proveedor generado por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $this->movimiento($this->pick($cuentasEmpresa), 'egreso', 'pago_proveedor', $monto, $moneda, $tipoCambio, 'Pago a proveedor', 'App\\Models\\PagoProveedor', $pagoId);
        }
    }

    private function crearPagosCamion(array $contratoCamiones, array $operadores, array $cuentasEmpresa, array $cuentasBancarias, array $lotes): void
    {
        foreach ($contratoCamiones as $ccId) {
            if (rand(1,100) > 70) continue;
            $monto = $this->money(500, 8000);
            $moneda = $this->pick($this->monedas);
            $tipoCambio = $moneda === 'BOB' ? 1 : 6.96;
            $pagoId = DB::table('pagos_camion')->insertGetId([
                'uuid' => $this->uuid(), 'lote_pago_id' => $this->pick($lotes), 'contrato_camion_id' => $ccId,
                'tipo_pago' => $this->pick(['adelanto','flete','pago_final']), 'monto' => $monto,
                'moneda_pago' => $moneda, 'tipo_cambio' => $tipoCambio, 'fecha_pago' => $this->dateBetween(90, 0),
                'receptor_type' => 'App\\Models\\OperadorTransporte', 'receptor_id' => $this->pick($operadores),
                'cuenta_origen_id' => $this->pick($cuentasEmpresa), 'cuenta_destino_id' => $cuentasBancarias ? $this->pick($cuentasBancarias) : null,
                'metodo_pago' => $this->pick($this->metodosPago), 'codigo_seguimiento' => 'PC-' . strtoupper(Str::random(8)),
                'observaciones' => 'Pago camión generado por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $this->movimiento($this->pick($cuentasEmpresa), 'egreso', 'pago_camion', $monto, $moneda, $tipoCambio, 'Pago a camión', 'App\\Models\\PagoCamion', $pagoId);
        }
    }

    private function crearPagosCliente(array $tramos, array $cuentasEmpresa, array $cuentasBancarias): void
    {
        foreach ($tramos as $tramoId) {
            if (rand(1,100) > 65) continue;
            $monto = $this->money(1000, 22000);
            $moneda = $this->pick($this->monedas);
            $tipoCambio = $moneda === 'BOB' ? 1 : 6.96;
            $pagoId = DB::table('pagos_cliente')->insertGetId([
                'uuid' => $this->uuid(), 'tramo_id' => $tramoId,
                'tipo_pago' => $this->pick(['adelanto','parcial','pago_final']), 'monto' => $monto,
                'moneda_pago' => $moneda, 'tipo_cambio' => $tipoCambio, 'fecha_pago' => $this->dateBetween(90, 0),
                'metodo_pago' => $this->pick($this->metodosPago), 'codigo_seguimiento' => 'PCLI-' . strtoupper(Str::random(8)),
                'cuenta_origen_id' => $cuentasBancarias ? $this->pick($cuentasBancarias) : null, 'cuenta_destino_id' => $this->pick($cuentasEmpresa),
                'observaciones' => 'Pago cliente generado por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $this->movimiento($this->pick($cuentasEmpresa), 'ingreso', 'pago_cliente', $monto, $moneda, $tipoCambio, 'Pago de cliente', 'App\\Models\\PagoCliente', $pagoId);
        }
    }

    private function crearGastosExtras(array $contratos, array $cuentasBancarias, array $cuentasEmpresa): void
    {
        $categorias = ['ADUANERO','CARGUIO','DESCARGA','ALMACENAJE','VIATICOS','OTRO'];
        foreach ($contratos as $contratoId) {
            for ($i = 1; $i <= rand(1, 2); $i++) {
                $moneda = $this->pick($this->monedas);
                $tc = $moneda === 'BOB' ? null : 6.96;
                $monto = $this->money(100, 3500);
                $bob = $moneda === 'BOB' ? $monto : round($monto * $tc, 2);
                $estado = rand(1,100) <= 70 ? 'PAGADO' : 'PENDIENTE';
                $gastoId = DB::table('gastos_extras')->insertGetId([
                    'uuid' => $this->uuid(), 'contrato_id' => $contratoId, 'cuenta_bancaria_id' => $cuentasBancarias ? $this->pick($cuentasBancarias) : 1,
                    'cuenta_empresa_id' => $this->pick($cuentasEmpresa), 'categoria' => $this->pick($categorias),
                    'concepto' => 'Gasto operativo generado por seeder', 'fecha' => $this->dateBetween(120, 0),
                    'monto' => $monto, 'monto_bolivianos' => $bob, 'moneda' => $moneda, 'tipo_cambio' => $tc,
                    'estado' => $estado, 'metodo_pago' => $estado === 'PAGADO' ? strtoupper($this->pick(['EFECTIVO','TRANSFERENCIA','QR'])) : null,
                    'nombre_titular' => $this->pick($this->nombres) . ' ' . $this->pick($this->apellidos),
                    'created_at' => $this->now(), 'updated_at' => $this->now(),
                ]);
                if ($estado === 'PAGADO') {
                    $this->movimiento($this->pick($cuentasEmpresa), 'egreso', 'gasto_extra', $monto, $moneda, $tc ?? 1, 'Gasto extra', 'App\\Models\\GastoExtra', $gastoId);
                }
            }
        }
    }

    private function crearMovimientosSueltos(array $cuentasEmpresa): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $tipo = $this->pick(['ingreso','egreso']);
            $cat = $tipo === 'ingreso' ? $this->pick(['anticipo_cliente','pago_cliente','prestamo_recibido','otro']) : $this->pick(['pago_proveedor','pago_camion','gasto_extra','pago_sueldo','otro']);
            $moneda = $this->pick($this->monedas);
            $tc = $moneda === 'BOB' ? 1 : 6.96;
            $this->movimiento($this->pick($cuentasEmpresa), $tipo, $cat, $this->money(100, 12000), $moneda, $tc, 'Movimiento suelto generado por seeder');
        }
    }

    private function crearPrestamosInternos(array $cuentasEmpresa): void
    {
        if (count($cuentasEmpresa) < 2) return;
        for ($i = 1; $i <= 5; $i++) {
            $origen = $this->pick($cuentasEmpresa);
            do { $destino = $this->pick($cuentasEmpresa); } while ($destino === $origen);
            $monto = $this->money(1000, 30000);
            $devuelto = rand(1,100) <= 50 ? $this->money(0, $monto) : 0;
            $estado = $devuelto <= 0 ? 'pendiente' : ($devuelto >= $monto ? 'pagado' : 'pagado_parcial');
            DB::table('prestamos_internos')->insert([
                'uuid' => $this->uuid(), 'cuenta_origen_id' => $origen, 'cuenta_destino_id' => $destino,
                'monto_original' => $monto, 'monto_devuelto' => $devuelto, 'moneda' => 'BOB',
                'fecha_prestamo' => $this->dateBetween(180, 0), 'fecha_vencimiento' => Carbon::now()->addDays(rand(15, 180))->format('Y-m-d'),
                'estado' => $estado, 'concepto' => 'Préstamo interno generado por seeder',
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
        }
    }

    private function movimiento(int $cuentaEmpresaId, string $tipo, string $categoria, float $monto, string $moneda, float $tc, string $concepto, ?string $origenType = null, ?int $origenId = null): void
    {
        $bob = round($monto * $tc, 2);
        DB::table('movimientos')->insert([
            'uuid' => $this->uuid(), 'cuenta_empresa_id' => $cuentaEmpresaId, 'tipo' => $tipo,
            'categoria' => $categoria, 'monto' => $monto, 'moneda' => $moneda, 'tipo_cambio' => $tc,
            'monto_bolivianos' => $bob, 'fecha' => $this->dateBetween(120, 0),
            'concepto' => $concepto, 'codigo_seguimiento' => 'MOV-' . strtoupper(Str::random(8)),
            'observaciones' => 'Movimiento generado por seeder', 'origen_type' => $origenType, 'origen_id' => $origenId,
            'created_at' => $this->now(), 'updated_at' => $this->now(),
        ]);
    }
}
