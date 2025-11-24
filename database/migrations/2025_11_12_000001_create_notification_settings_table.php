<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('evento')->unique(); // peticion_creada, peticion_aprobada, etc.
            $table->boolean('notificar_usuario')->default(true);
            $table->boolean('notificar_admin')->default(true);
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('notification_settings')->insert([
            // PETICIONES
            ['evento' => 'peticion_creada', 'notificar_usuario' => true, 'notificar_admin' => true, 'descripcion' => 'Usuario crea una nueva petición de material', 'created_at' => now(), 'updated_at' => now()],
            ['evento' => 'peticion_aprobada', 'notificar_usuario' => true, 'notificar_admin' => false, 'descripcion' => 'Petición aprobada por administrador', 'created_at' => now(), 'updated_at' => now()],
            ['evento' => 'peticion_denegada', 'notificar_usuario' => true, 'notificar_admin' => false, 'descripcion' => 'Petición denegada por administrador', 'created_at' => now(), 'updated_at' => now()],
            
            // MOVIMIENTOS
            ['evento' => 'movimiento_creado', 'notificar_usuario' => false, 'notificar_admin' => true, 'descripcion' => 'Nuevo movimiento de entrada/salida creado', 'created_at' => now(), 'updated_at' => now()],
            ['evento' => 'movimiento_firmado', 'notificar_usuario' => false, 'notificar_admin' => true, 'descripcion' => 'Movimiento firmado completamente', 'created_at' => now(), 'updated_at' => now()],
            ['evento' => 'movimiento_entregado', 'notificar_usuario' => true, 'notificar_admin' => false, 'descripcion' => 'Material marcado como entregado', 'created_at' => now(), 'updated_at' => now()],
            
            // RECORDATORIOS
            ['evento' => 'recordatorio_entrega', 'notificar_usuario' => true, 'notificar_admin' => false, 'descripcion' => 'Recordatorio de fecha prevista de entrega (día anterior)', 'created_at' => now(), 'updated_at' => now()],
            ['evento' => 'entrega_vencida', 'notificar_usuario' => false, 'notificar_admin' => true, 'descripcion' => 'Fecha prevista de entrega superada', 'created_at' => now(), 'updated_at' => now()],
            
            // SOLICITUDES DE REPOSICIÓN
            ['evento' => 'solicitud_reposicion', 'notificar_usuario' => false, 'notificar_admin' => true, 'descripcion' => 'Nueva solicitud de reposición de material', 'created_at' => now(), 'updated_at' => now()],
            
            // FIRMAS
            ['evento' => 'firma_solicitada', 'notificar_usuario' => true, 'notificar_admin' => false, 'descripcion' => 'Se solicita firma remota para un documento', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('notification_settings');
    }
};
