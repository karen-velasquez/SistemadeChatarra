<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_extras MODIFY cuenta_bancaria_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_extras MODIFY cuenta_bancaria_id BIGINT UNSIGNED NOT NULL');
    }
};
