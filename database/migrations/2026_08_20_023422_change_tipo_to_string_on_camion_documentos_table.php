<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE camion_documentos MODIFY tipo VARCHAR(100) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE camion_documentos MODIFY tipo ENUM('RUAT', 'CONTRATO', 'IMPUESTO', 'SEGURO', 'SOAT', 'OTRO') NOT NULL");
    }
};
