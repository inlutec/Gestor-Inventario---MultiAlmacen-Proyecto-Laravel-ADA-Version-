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
        // Actualizar ENUM para incluir estados de peticiones
        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente', 'recibido', 'cancelado', 'aprobado', 'denegado') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente', 'recibido', 'cancelado') DEFAULT 'pendiente'");
    }
};
