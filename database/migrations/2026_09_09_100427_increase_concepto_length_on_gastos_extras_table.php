<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_extras MODIFY concepto VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_extras MODIFY concepto VARCHAR(100) NOT NULL');
    }
};
