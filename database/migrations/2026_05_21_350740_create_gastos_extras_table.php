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
        Schema::create('gastos_extras', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('cuenta_bancaria_id');
            $table->string('categoria', 50);
            $table->string('concepto', 100);
            $table->date('fecha');
            $table->decimal('monto', 14, 4);
            $table->decimal('monto_bolivianos', 14, 4);
            $table->string('moneda', 10);
            $table->decimal('tipo_cambio', 10, 2)->nullable()->comment('De moneda extranjera a Bs');
            $table->enum('estado', ['PENDIENTE', 'PAGADO'])->default('PENDIENTE');
            $table->string('metodo_pago', 50)->nullable()->comment('TRANSFERENCIA, QR');
            $table->string('comprobante_pago')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contrato_id')->references('id')->on('contratos')->onDelete('cascade');
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuentas_bancarias');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos_extras');
    }
};