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
        // MySQL ENUM no soporta change() directo en algunas versiones — usamos raw
        \DB::statement("ALTER TABLE pagos_extras_lote MODIFY metodo_pago ENUM('transferencia','qr') NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE pagos_extras_lote MODIFY metodo_pago ENUM('transferencia','qr','efectivo','cheque') NOT NULL");
    }
};
