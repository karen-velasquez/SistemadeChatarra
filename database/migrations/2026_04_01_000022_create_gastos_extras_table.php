<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_extras', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('cuenta_bancaria_id');
            $table->unsignedBigInteger('cuenta_empresa_id')->nullable();
            $table->string('categoria', 50);
            $table->string('concepto', 100);
            $table->date('fecha');
            $table->decimal('monto', 14, 4);
            $table->decimal('monto_bolivianos', 14, 4);
            $table->string('moneda', 10);
            $table->decimal('tipo_cambio', 10, 2)->nullable();
            $table->enum('estado', ['PENDIENTE', 'PAGADO'])->default('PENDIENTE');
            $table->string('metodo_pago', 50)->nullable();
            $table->string('comprobante_pago')->nullable();
            $table->string('nombre_titular', 150)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contrato_id')->references('id')->on('contratos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_extras');
    }
};
