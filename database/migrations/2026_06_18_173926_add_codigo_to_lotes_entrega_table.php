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
            // Código correlativo por proveedor: LE-{SIGLA_PROV}-{NNN}
            $table->string('codigo', 30)->nullable()->after('uuid');
            $table->unique(['proveedor_id', 'codigo'], 'unique_codigo_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('lotes_entrega', function (Blueprint $table) {
            $table->dropUnique('unique_codigo_proveedor');
            $table->dropColumn('codigo');
        });
    }
};
