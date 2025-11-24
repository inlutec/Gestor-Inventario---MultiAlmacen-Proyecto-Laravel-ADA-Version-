<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para agregar 'entregado'
        DB::statement("ALTER TABLE material_movimientos MODIFY COLUMN estado ENUM('borrador', 'pendiente_firma', 'firmado', 'entregado') NOT NULL DEFAULT 'borrador'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM anterior
        DB::statement("ALTER TABLE material_movimientos MODIFY COLUMN estado ENUM('borrador', 'pendiente_firma', 'firmado') NOT NULL DEFAULT 'borrador'");
    }
};
