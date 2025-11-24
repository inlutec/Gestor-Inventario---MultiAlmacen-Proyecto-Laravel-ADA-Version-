<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smtp_config', function (Blueprint $table) {
            $table->enum('provider', ['custom', 'microsoft365', 'gmail', 'otros'])->default('custom')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('smtp_config', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
