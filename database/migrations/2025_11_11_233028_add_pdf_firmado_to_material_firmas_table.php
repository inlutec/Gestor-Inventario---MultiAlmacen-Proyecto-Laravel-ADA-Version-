<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            $table->string('pdf_firmado_path')->nullable()->after('firma_rubrica');
            $table->string('metodo_firma')->default('rubrica')->after('pdf_firmado_path'); // rubrica, certificado_digital
            $table->text('certificado_info')->nullable()->after('metodo_firma'); // Info del certificado si aplica
        });
    }

    public function down(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            $table->dropColumn(['pdf_firmado_path', 'metodo_firma', 'certificado_info']);
        });
    }
};
