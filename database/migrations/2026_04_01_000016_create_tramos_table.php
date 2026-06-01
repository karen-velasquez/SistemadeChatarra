<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lote_pago_id')->nullable();
            $table->unsignedBigInteger('contrato_camion_id');
            $table->unsignedBigInteger('tramo_padre_id')->nullable();
            $table->unsignedBigInteger('camion_id');
            $table->unsignedBigInteger('conductor_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('origen', 150);
            $table->string('destino', 150);
            $table->enum('tipo_tramo', ['Internacional', 'Nacional'])->default('Nacional');
            $table->decimal('peso_declarado', 10, 3)->nullable();
            $table->decimal('peso_salida', 10, 3)->nullable();
            $table->decimal('peso_llegada', 10, 3)->nullable();
            $table->decimal('precio_por_tonelada', 10, 4)->nullable();
            $table->string('moneda_venta', 10)->nullable();
            $table->decimal('descuento_porcentaje', 5, 2)->nullable();
            $table->text('observaciones_llegada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->enum('estado', ['En ruta', 'Transbordando', 'Transbordado', 'Entregado', 'Div. Carga', 'Desactivado'])->default('En ruta');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contrato_camion_id')->references('id')->on('contrato_camiones');
            $table->foreign('camion_id')->references('id')->on('camiones');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramos');
    }
};
