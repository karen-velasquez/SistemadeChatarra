<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_camion', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lote_pago_id')->nullable();
            $table->unsignedBigInteger('contrato_camion_id');
            $table->enum('tipo_pago', ['adelanto', 'flete', 'pago_final']);
            $table->decimal('monto', 12, 2);
            $table->string('moneda_pago', 10)->default('BOB');
            $table->decimal('tipo_cambio', 10, 4)->default(1);
            $table->date('fecha_pago');
            $table->string('receptor_type', 100)->nullable();
            $table->unsignedBigInteger('receptor_id')->nullable();
            $table->unsignedBigInteger('cuenta_origen_id')->nullable();
            $table->unsignedBigInteger('cuenta_destino_id')->nullable();
            $table->unsignedBigInteger('cuenta_empresa_id')->nullable();
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'qr', 'cheque']);
            $table->string('codigo_seguimiento', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contrato_camion_id')->references('id')->on('contrato_camiones');
            $table->foreign('cuenta_origen_id')->references('id')->on('cuentas_bancarias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_camion');
    }
};
