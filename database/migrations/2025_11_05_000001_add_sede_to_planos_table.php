<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            if (!Schema::hasColumn('planos', 'sede')) {
                $table->string('sede', 100)->nullable()->after('nombre');
            }
            // If 'imagen' was longText base64, we keep it as-is; we'll now store a path as string inside.
        });
    }

    public function down(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            if (Schema::hasColumn('planos', 'sede')) {
                $table->dropColumn('sede');
            }
        });
    }
};
