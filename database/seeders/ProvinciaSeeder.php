<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            ['nombre' => 'Almería', 'clave' => 'almeria'],
            ['nombre' => 'Cádiz', 'clave' => 'cadiz'],
            ['nombre' => 'Córdoba', 'clave' => 'cordoba'],
            ['nombre' => 'Granada', 'clave' => 'granada'],
            ['nombre' => 'Huelva', 'clave' => 'huelva'],
            ['nombre' => 'Jaén', 'clave' => 'jaen'],
            ['nombre' => 'Málaga', 'clave' => 'malaga'],
            ['nombre' => 'Sevilla', 'clave' => 'sevilla'],
        ];

        foreach ($provincias as $provincia) {
            DB::table('provincias')->insert([
                'nombre' => $provincia['nombre'],
                'clave' => $provincia['clave'],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}