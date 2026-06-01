<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_internos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('cuenta_origen_id');
            $table->unsignedBigInteger('cuenta_destino_id');
            $table->decimal('monto_original', 15, 2);
            $table->decimal('monto_devuelto', 15, 2)->default(0);
            $table->string('moneda', 10)->default('BOB');
            $table->date('fecha_prestamo');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'pagado_parcial', 'pagado'])->default('pendiente');
            $table->text('concepto')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('cuenta_origen_id')->references('id')->on('cuentas_empresa');
            $table->foreign('cuenta_destino_id')->references('id')->on('cuentas_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_internos');
    }
};
