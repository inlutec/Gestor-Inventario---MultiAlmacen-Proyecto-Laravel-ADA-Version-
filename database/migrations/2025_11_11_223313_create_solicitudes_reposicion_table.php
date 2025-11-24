<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_reposicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('entidad_id')->constrained('entidades')->onDelete('cascade');
            $table->integer('cantidad_solicitada')->default(1);
            $table->enum('estado', ['pendiente', 'notificado', 'cancelado'])->default('pendiente');
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_notificacion')->nullable();
            $table->date('prevision_llegada')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas frecuentes
            $table->index(['entidad_id', 'estado']);
            $table->index(['usuario_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_reposicion');
    }
};
