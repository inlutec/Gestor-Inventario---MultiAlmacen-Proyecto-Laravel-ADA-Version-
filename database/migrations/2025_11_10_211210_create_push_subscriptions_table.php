<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('usuarios')->onDelete('cascade');
            $table->string('endpoint', 500); // Cambiado de text a string con longitud
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('device_type', 20)->nullable(); // 'android', 'ios', 'web'
            $table->timestamps();
            
            $table->unique('endpoint');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
