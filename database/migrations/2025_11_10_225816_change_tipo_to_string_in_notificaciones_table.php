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
        Schema::table('notificaciones', function (Blueprint $table) {
            // Cambiar tipo de ENUM a string para soportar más tipos de notificaciones
            DB::statement("ALTER TABLE notificaciones MODIFY tipo VARCHAR(50) DEFAULT 'info'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            DB::statement("ALTER TABLE notificaciones MODIFY tipo ENUM('info', 'warning', 'error') DEFAULT 'info'");
        });
    }
};
