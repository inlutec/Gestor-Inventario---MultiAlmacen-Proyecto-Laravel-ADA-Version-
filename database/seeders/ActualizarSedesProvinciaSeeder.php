<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActualizarSedesProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        // Marcar Córdoba como provincia para sedes existentes sin provincia
        DB::table('sedes')
            ->whereNull('provincia_id')
            ->update(['provincia_id' => 3]); // ID de Córdoba
    }
}