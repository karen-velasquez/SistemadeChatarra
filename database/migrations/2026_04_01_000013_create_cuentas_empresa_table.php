<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_empresa', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nombre_cuenta', 150);
            $table->unsignedBigInteger('banco_id')->nullable();
            $table->string('numero_cuenta', 100)->nullable();
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_actual', 15, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('banco_id')->references('id')->on('bancos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_empresa');
    }
};
