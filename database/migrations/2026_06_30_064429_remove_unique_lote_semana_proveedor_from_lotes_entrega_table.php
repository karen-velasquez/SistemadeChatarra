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
        Schema::table('lotes_entrega', function (Blueprint $table) {
            $table->dropUnique('unique_lote_semana_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('lotes_entrega', function (Blueprint $table) {
            $table->unique(['proveedor_id', 'numero_semana', 'anio'], 'unique_lote_semana_proveedor');
        });
    }
};
