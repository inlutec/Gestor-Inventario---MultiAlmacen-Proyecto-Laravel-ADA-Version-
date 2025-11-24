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
        Schema::table('pedidos', function (Blueprint $table) {
            // Agregar campos para el sistema de peticiones públicas
            $table->enum('tipo', ['pedido', 'peticion'])->default('pedido')->after('id');
            $table->date('fecha')->nullable()->after('fecha_pedido');
            $table->string('usuario_solicitante')->nullable()->after('tipo');
            $table->string('email_solicitante')->nullable()->after('usuario_solicitante');
            $table->string('telefono_solicitante', 20)->nullable()->after('email_solicitante');
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete()->after('telefono_solicitante');
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete()->after('sede_id');
            $table->text('observaciones')->nullable()->after('notas');
            $table->json('datos_personalizados')->nullable()->after('datos');
            $table->bigInteger('movimiento_id')->unsigned()->nullable()->after('datos_personalizados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo', 'fecha', 'usuario_solicitante', 'email_solicitante', 
                'telefono_solicitante', 'sede_id', 'departamento_id', 
                'observaciones', 'datos_personalizados', 'movimiento_id'
            ]);
        });
    }
};
