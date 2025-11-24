<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entity_type'); // impresora, consumible, pedido, etc
            $table->string('key')->index(); // slug único por entity_type
            $table->string('label');
            $table->string('type')->default('text'); // text, number, select, date, boolean
            $table->json('options')->nullable(); // para selects, etc
            $table->boolean('required')->default(false);
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['entity_type','key']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('field_id')->index();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->index(['entity_type','entity_id']);
            $table->foreign('field_id')->references('id')->on('custom_fields')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
