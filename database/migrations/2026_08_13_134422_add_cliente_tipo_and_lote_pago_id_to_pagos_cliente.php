<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pagos_cliente', 'lote_pago_id')) {
            Schema::table('pagos_cliente', function (Blueprint $table) {
                $table->unsignedBigInteger('lote_pago_id')->nullable()->after('tramo_id');
            });
        }

        $tipo = DB::selectOne("SHOW COLUMNS FROM lotes_pago WHERE Field = 'tipo'")->Type;
        if (!str_contains($tipo, "'cliente'")) {
            DB::statement("ALTER TABLE lotes_pago MODIFY tipo ENUM('proveedor', 'camion', 'cliente') NOT NULL");
        }

        $fkExiste = DB::selectOne("
            SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'pagos_cliente' AND COLUMN_NAME = 'lote_pago_id' AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->c;
        if (!$fkExiste) {
            Schema::table('pagos_cliente', function (Blueprint $table) {
                $table->foreign('lote_pago_id')->references('id')->on('lotes_pago')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('pagos_cliente', function (Blueprint $table) {
            $table->dropForeign(['lote_pago_id']);
            $table->dropColumn('lote_pago_id');
        });

        DB::statement("ALTER TABLE lotes_pago MODIFY tipo ENUM('proveedor', 'camion') NOT NULL");
    }
};
