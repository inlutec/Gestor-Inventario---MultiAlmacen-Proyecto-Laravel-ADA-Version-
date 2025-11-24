<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            if (!Schema::hasColumn('material_firmas', 'tipo_firma')) {
                $table->string('tipo_firma')->default('rubrica');
            }
            $table->text('cert_subject')->nullable();
            $table->text('cert_issuer')->nullable();
            $table->string('cert_serial')->nullable();
            $table->string('cert_thumbprint')->nullable();
            $table->text('raw_cert_pem')->nullable();
            $table->string('algoritmo')->nullable();
            $table->string('challenge_hash')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('material_firmas', function (Blueprint $table) {
            $table->dropColumn([
                'cert_subject','cert_issuer','cert_serial','cert_thumbprint','raw_cert_pem','algoritmo','challenge_hash','tipo_firma'
            ]);
        });
    }
};
