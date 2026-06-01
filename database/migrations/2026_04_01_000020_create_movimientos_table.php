<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lote_pago_id')->nullable();
            $table->unsignedBigInteger('cuenta_empresa_id');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->enum('categoria', [
                'pago_cliente', 'anticipo_cliente', 'pago_proveedor', 'pago_camion',
                'gasto_extra', 'pago_sueldo', 'prestamo_otorgado', 'prestamo_recibido',
                'devolucion_prestamo', 'otro',
            ]);
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('tipo_cambio', 10, 4)->default(1);
            $table->decimal('monto_bolivianos', 15, 2)->default(0);
            $table->date('fecha');
            $table->string('concepto', 255);
            $table->string('codigo_seguimiento', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('origen_type', 100)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('cuenta_empresa_id')->references('id')->on('cuentas_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
