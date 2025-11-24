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
        Schema::table('entidades', function (Blueprint $table) {
            // Campos específicos para impresoras
            $table->string('referencia', 100)->nullable()->after('custom_fields')->comment('Referencia interna de la impresora');
            $table->string('numero_serie', 100)->nullable()->after('referencia')->comment('Número de serie del fabricante');
            $table->string('ip', 50)->nullable()->after('numero_serie')->comment('Dirección IP de la impresora');
            $table->string('marca', 100)->nullable()->after('ip')->comment('Marca de la impresora');
            $table->string('modelo', 100)->nullable()->after('marca')->comment('Modelo de la impresora');
            $table->string('division', 100)->nullable()->after('modelo')->comment('División organizativa');
            $table->string('planta', 100)->nullable()->after('division')->comment('Planta del edificio');
            $table->string('ubicacion', 255)->nullable()->after('planta')->comment('Ubicación específica');
            $table->string('host_checkmk', 100)->nullable()->after('ubicacion')->comment('Nombre del host en CheckMK');
            $table->string('sede', 100)->nullable()->after('host_checkmk')->comment('Sede o centro');
            $table->string('departamento', 100)->nullable()->after('sede')->comment('Departamento');
            
            // Índices para búsquedas comunes
            $table->index('referencia');
            $table->index('ip');
            $table->index('host_checkmk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropIndex(['referencia']);
            $table->dropIndex(['ip']);
            $table->dropIndex(['host_checkmk']);
            
            $table->dropColumn([
                'referencia',
                'numero_serie',
                'ip',
                'marca',
                'modelo',
                'division',
                'planta',
                'ubicacion',
                'host_checkmk',
                'sede',
                'departamento',
            ]);
        });
    }
};
