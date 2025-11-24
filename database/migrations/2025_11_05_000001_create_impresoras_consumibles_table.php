<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('impresoras_consumibles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->string('hostname')->index();
            $table->string('service_name'); // Nombre original del servicio en CheckMK
            $table->string('key')->nullable()->index(); // clave normalizada (slug)
            $table->string('category')->nullable(); // toner, drum, fuser, belt, roller, kit, etc
            $table->integer('percent')->nullable();
            $table->string('state')->nullable(); // OK, WARN, CRIT, UNKNOWN
            $table->text('raw_output')->nullable();
            $table->timestamp('sync_timestamp')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impresoras_consumibles');
    }
};
