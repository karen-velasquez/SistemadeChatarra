<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_camiones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lote_pago_id')->nullable();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('camion_id');
            $table->unsignedBigInteger('conductor_id')->nullable();
            $table->decimal('toneladas', 10, 3);
            $table->decimal('monto_acordado', 12, 2)->nullable();
            $table->string('moneda_flete', 10)->default('BOB');
            $table->date('fecha_asignacion')->nullable();
            $table->enum('estado_entrega', ['Pendiente', 'Entregado', 'Desactivado'])->default('Pendiente');
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('contrato_id')->references('id')->on('contratos');
            $table->foreign('camion_id')->references('id')->on('camiones');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_camiones');
    }
};
