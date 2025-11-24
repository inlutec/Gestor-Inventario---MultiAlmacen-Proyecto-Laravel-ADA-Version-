<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabla de configuración de CheckMK
        Schema::create('checkmk_config', function (Blueprint $table) {
            $table->id();
            $table->string('api_url')->default('http://10.66.129.103');
            $table->string('api_user')->default('api_user');
            $table->string('api_password');
            $table->string('site')->default('admin');
            $table->integer('sync_interval_minutes')->default(60); // Intervalo en minutos (1-1440)
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
        });

        // Tabla de logs de sincronización
        Schema::create('checkmk_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('sync_timestamp');
            $table->enum('status', ['success', 'error', 'partial'])->default('success');
            $table->integer('hosts_processed')->default(0);
            $table->integer('hosts_success')->default(0);
            $table->integer('hosts_error')->default(0);
            $table->text('message')->nullable();
            $table->json('details')->nullable(); // Detalles adicionales (errores específicos, etc.)
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->timestamps();
            
            $table->index('sync_timestamp');
            $table->index('status');
        });

        // Insertar configuración por defecto
        DB::table('checkmk_config')->insert([
            'api_url' => 'http://10.66.129.103',
            'api_user' => 'api_user',
            'api_password' => 'wMQrkNQJZR6FULpw',
            'site' => 'admin',
            'sync_interval_minutes' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('checkmk_sync_logs');
        Schema::dropIfExists('checkmk_config');
    }
};
