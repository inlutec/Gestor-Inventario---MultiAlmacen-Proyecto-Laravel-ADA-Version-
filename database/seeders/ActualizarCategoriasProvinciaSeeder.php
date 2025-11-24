<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActualizarCategoriasProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        // Marcar Córdoba como provincia para categorías existentes sin provincia
        DB::table('categorias')
            ->whereNull('provincia_id')
            ->update(['provincia_id' => 3]); // ID de Córdoba
    }
}