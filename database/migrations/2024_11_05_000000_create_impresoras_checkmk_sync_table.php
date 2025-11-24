<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impresoras_checkmk_sync', function (Blueprint $table) {
            $table->id();
            $table->string('hostname', 255); // Nombre del host en CheckMK
            $table->string('display_name', 255)->nullable(); // Nombre para mostrar
            $table->string('ip_address', 45)->nullable(); // IP de la impresora
            $table->string('marca', 100)->nullable(); // Marca de la impresora
            $table->string('modelo', 100)->nullable(); // Modelo de la impresora
            $table->string('numero_serie', 100)->nullable(); // Número de serie
            
            // Datos de toners/cartuchos
            $table->integer('toner_cyan')->nullable(); // Porcentaje de toner cyan
            $table->integer('toner_magenta')->nullable(); // Porcentaje de toner magenta
            $table->integer('toner_yellow')->nullable(); // Porcentaje de toner amarillo
            $table->integer('toner_black')->nullable(); // Porcentaje de toner negro
            
            // Otros consumibles
            $table->integer('drum_unit')->nullable(); // Unidad de imagen/drum
            $table->integer('fuser')->nullable(); // Fusor
            $table->integer('transfer_belt')->nullable(); // Correa de transferencia
            $table->integer('waste_toner')->nullable(); // Toner de desecho
            
            // Contadores de páginas
            $table->bigInteger('paginas_total')->nullable(); // Total de páginas impresas
            $table->bigInteger('paginas_color')->nullable(); // Páginas a color
            $table->bigInteger('paginas_bn')->nullable(); // Páginas en blanco y negro
            
            // Estado y tiempo de operación
            $table->string('estado', 50)->nullable(); // Estado del dispositivo (online, offline, warning, error)
            $table->integer('uptime_dias')->nullable(); // Días conectada
            $table->text('mensajes_error')->nullable(); // Mensajes de error o alertas
            
            // Datos adicionales en JSON
            $table->json('datos_adicionales')->nullable(); // Otros datos que envíe CheckMK
            
            // Marca temporal de sincronización
            $table->timestamp('sync_timestamp'); // Cuándo se obtuvieron estos datos
            
            $table->timestamps();
            
            // Índices para búsqueda eficiente
            $table->index('hostname');
            $table->index('ip_address');
            $table->index('sync_timestamp');
            $table->index(['hostname', 'sync_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impresoras_checkmk_sync');
    }
};
