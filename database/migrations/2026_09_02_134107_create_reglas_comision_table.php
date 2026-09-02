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
        Schema::create('reglas_comision', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Cliente y/o empresa facturadora que activan la regla. Si un campo
            // queda nulo, esa condición no se exige (ej. solo cliente = aplica a
            // cualquier empresa; ambos nulos no se permite, se valida en el form).
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('empresa_facturadora_id')->nullable();
            $table->decimal('monto_por_tonelada', 12, 4);
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('id')->on('clientes')->nullOnDelete();
            $table->foreign('empresa_facturadora_id')->references('id')->on('empresas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglas_comision');
    }
};
