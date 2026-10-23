<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_documentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('contrato_id');
            $table->string('archivo');
            $table->string('nombre_original')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contrato_id')->references('id')->on('contratos');
        });

        // Migra el documento único existente (columna documento_pdf) como el
        // primer documento de cada contrato en la tabla nueva. La columna
        // vieja no se borra, por compatibilidad con lo que ya la referencia.
        DB::table('contratos')
            ->whereNotNull('documento_pdf')
            ->where('documento_pdf', '!=', '')
            ->select('id', 'documento_pdf', 'created_by')
            ->orderBy('id')
            ->get()
            ->each(function ($contrato) {
                DB::table('contrato_documentos')->insert([
                    'uuid'         => Str::uuid()->toString(),
                    'contrato_id'  => $contrato->id,
                    'archivo'      => $contrato->documento_pdf,
                    'created_by'   => $contrato->created_by,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_documentos');
    }
};
