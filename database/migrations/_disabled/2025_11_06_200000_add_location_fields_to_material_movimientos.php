<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('origen_sede_id')->nullable()->after('usuario_id');
            $table->unsignedBigInteger('origen_departamento_id')->nullable()->after('origen_sede_id');
            $table->unsignedBigInteger('destino_sede_id')->nullable()->after('destino');
            $table->unsignedBigInteger('destino_departamento_id')->nullable()->after('destino_sede_id');

            // Índices para consultas (nombres cortos por límite de MySQL)
            $table->index(['origen_sede_id', 'origen_departamento_id'], 'mm_origen_idx');
            $table->index(['destino_sede_id', 'destino_departamento_id'], 'mm_destino_idx');
        });
    }

    public function down(): void
    {
        Schema::table('material_movimientos', function (Blueprint $table) {
            $table->dropIndex('mm_origen_idx');
            $table->dropIndex('mm_destino_idx');
            $table->dropColumn(['origen_sede_id', 'origen_departamento_id', 'destino_sede_id', 'destino_departamento_id']);
        });
    }
};
