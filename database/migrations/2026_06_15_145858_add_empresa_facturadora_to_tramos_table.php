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
        Schema::table('tramos', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_facturadora_id')->nullable()->after('direccion_entrega');
            $table->foreign('empresa_facturadora_id')->references('id')->on('empresas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tramos', function (Blueprint $table) {
            $table->dropForeign(['empresa_facturadora_id']);
            $table->dropColumn('empresa_facturadora_id');
        });
    }
};
