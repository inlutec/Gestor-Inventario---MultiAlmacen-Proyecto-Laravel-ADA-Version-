<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos para aprobación parcial en pedidos
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'comentarios_aprobacion')) {
                $table->text('comentarios_aprobacion')->nullable()->after('notas');
            }
            if (!Schema::hasColumn('pedidos', 'aprobacion_parcial')) {
                $table->boolean('aprobacion_parcial')->default(false)->after('estado');
            }
            if (!Schema::hasColumn('pedidos', 'usuario_aprobador_id')) {
                $table->foreignId('usuario_aprobador_id')->nullable()->after('usuario_creador_id')->constrained('usuarios')->nullOnDelete();
            }
            if (!Schema::hasColumn('pedidos', 'fecha_aprobacion')) {
                $table->timestamp('fecha_aprobacion')->nullable()->after('notas');
            }
        });

        // Agregar cantidad aprobada en detalles_pedido
        Schema::table('detalles_pedido', function (Blueprint $table) {
            if (!Schema::hasColumn('detalles_pedido', 'cantidad_aprobada')) {
                $table->decimal('cantidad_aprobada', 10, 2)->nullable()->after('cantidad');
            }
        });

        // Crear tabla de seguimiento de cambios si no existe
        if (!Schema::hasTable('registro_cambios')) {
            Schema::create('registro_cambios', function (Blueprint $table) {
                $table->id();
                $table->string('entidad_tipo');
                $table->unsignedBigInteger('entidad_id');
                $table->string('accion');
                $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
                $table->json('datos_anteriores')->nullable();
                $table->json('datos_nuevos')->nullable();
                $table->text('comentario')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();

                $table->index(['entidad_tipo', 'entidad_id']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['comentarios_aprobacion', 'aprobacion_parcial', 'usuario_aprobador_id', 'fecha_aprobacion']);
        });

        Schema::table('detalles_pedido', function (Blueprint $table) {
            $table->dropColumn('cantidad_aprobada');
        });

        Schema::dropIfExists('registro_cambios');
    }
};
