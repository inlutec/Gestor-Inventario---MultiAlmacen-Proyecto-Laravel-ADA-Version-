<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_movimientos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('material_movimientos')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('accion'); // creado, modificado, firmado_emisor, firmado_receptor, entregado, estado_cambiado, eliminado
            $table->text('descripcion');
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
            
            $table->index(['movimiento_id', 'fecha']);
            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_movimientos_historial');
    }
};
