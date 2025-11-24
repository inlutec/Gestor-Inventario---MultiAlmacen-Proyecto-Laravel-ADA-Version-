<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Departamento;

class UserAlmacenSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todos los usuarios
        $users = User::all();
        
        // Obtener todos los departamentos marcados como almacén central
        $almacenes = Departamento::where('es_almacen_central', true)->get();
        
        if ($almacenes->isEmpty()) {
            // Si no hay almacenes centrales, usar el primer departamento como almacén
            $primerDepartamento = Departamento::first();
            if ($primerDepartamento) {
                $almacenes = collect([$primerDepartamento]);
            }
        }
        
        // Asignar todos los almacenes a todos los usuarios (para desarrollo)
        foreach ($users as $user) {
            if ($almacenes->isNotEmpty()) {
                $user->almacenes()->attach($almacenes->pluck('id'));
            }
        }
    }
}