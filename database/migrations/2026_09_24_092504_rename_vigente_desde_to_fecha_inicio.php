<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CHANGE COLUMN en vez de Schema::renameColumn: evita requerir doctrine/dbal
        // solo para este rename puntual.
        if (Schema::hasColumn('reglas_comision', 'vigente_desde')) {
            DB::statement('ALTER TABLE reglas_comision CHANGE COLUMN vigente_desde fecha_inicio DATE NULL DEFAULT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('reglas_comision', 'fecha_inicio')) {
            DB::statement('ALTER TABLE reglas_comision CHANGE COLUMN fecha_inicio vigente_desde DATE NULL DEFAULT NULL');
        }
    }
};
