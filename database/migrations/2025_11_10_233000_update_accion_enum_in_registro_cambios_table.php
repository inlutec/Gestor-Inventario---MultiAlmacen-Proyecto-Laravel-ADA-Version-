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
        // Cambiar accion de ENUM a VARCHAR para mayor flexibilidad
        DB::statement("ALTER TABLE registro_cambios MODIFY accion VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE registro_cambios MODIFY accion ENUM('crear','modificar','eliminar','ubicar','consumir','desubicar') NOT NULL");
    }
};
