<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lotes_entrega', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedSmallInteger('numero_semana');
            $table->unsignedSmallInteger('anio');
            $table->date('fecha_inicio'); // lunes de la semana ISO
            $table->date('fecha_fin');    // domingo de la semana ISO
            $table->enum('estado', ['Abierto', 'Cerrado'])->default('Abierto');
            $table->text('observaciones')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->unsignedBigInteger('cerrado_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedors')->onDelete('cascade');
            $table->foreign('cerrado_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['proveedor_id', 'numero_semana', 'anio'], 'unique_lote_semana_proveedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_entrega');
    }
};
