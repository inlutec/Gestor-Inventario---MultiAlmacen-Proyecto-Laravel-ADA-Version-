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
        Schema::create('pedidos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('accion'); // 'creado', 'modificado', 'aprobado', 'rechazado', 'enviado_historico', 'comentario'
            $table->text('descripcion'); // Descripción detallada del cambio
            $table->json('datos_antes')->nullable(); // Estado antes del cambio
            $table->json('datos_despues')->nullable(); // Estado después del cambio
            $table->string('ip_address')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
            
            // Índices
            $table->index(['pedido_id', 'fecha']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_historial');
    }
};
