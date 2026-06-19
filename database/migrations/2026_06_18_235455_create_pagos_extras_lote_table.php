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
        Schema::create('pagos_extras_lote', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lote_entrega_id');
            $table->unsignedBigInteger('cuenta_origen_id');   // CuentaEmpresa (sale de aquí)
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('tipo_cambio', 10, 4)->default(1);
            $table->decimal('monto_bolivianos', 15, 2);
            $table->date('fecha');
            $table->enum('metodo_pago', ['transferencia', 'qr', 'efectivo', 'cheque']);
            $table->string('codigo_seguimiento', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('lote_entrega_id')->references('id')->on('lotes_entrega')->onDelete('cascade');
            $table->foreign('cuenta_origen_id')->references('id')->on('cuentas_empresa')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_extras_lote');
    }
};
