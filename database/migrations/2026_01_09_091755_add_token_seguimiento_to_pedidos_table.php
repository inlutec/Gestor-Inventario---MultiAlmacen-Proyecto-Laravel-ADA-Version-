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
            $table->string('token_seguimiento', 64)->nullable()->unique()->after('movimiento_id');
            $table->timestamp('token_seguimiento_expira')->nullable()->after('token_seguimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['token_seguimiento', 'token_seguimiento_expira']);
        });
    }
};
