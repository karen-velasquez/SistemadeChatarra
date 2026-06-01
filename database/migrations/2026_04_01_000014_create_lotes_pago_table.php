<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_pago', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('tipo', ['proveedor', 'camion']);
            $table->string('codigo_provisional')->nullable();
            $table->string('codigo_real')->nullable();
            $table->date('fecha_pago');
            $table->string('metodo_pago', 30);
            $table->unsignedBigInteger('cuenta_origen_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_pago');
    }
};
