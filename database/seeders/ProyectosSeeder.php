<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proyectos\Etiqueta;
use Illuminate\Support\Str;

class ProyectosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear etiquetas predefinidas
        $etiquetas = [
            ['nombre' => 'Frontend', 'color' => '#3B82F6', 'descripcion' => 'Desarrollo frontend'],
            ['nombre' => 'Backend', 'color' => '#10B981', 'descripcion' => 'Desarrollo backend'],
            ['nombre' => 'Base de Datos', 'color' => '#8B5CF6', 'descripcion' => 'Trabajo con bases de datos'],
            ['nombre' => 'Diseño', 'color' => '#F59E0B', 'descripcion' => 'Diseño y UI/UX'],
            ['nombre' => 'Documentación', 'color' => '#6B7280', 'descripcion' => 'Documentación técnica'],
            ['nombre' => 'Testing', 'color' => '#EF4444', 'descripcion' => 'Pruebas y QA'],
            ['nombre' => 'Bug', 'color' => '#DC2626', 'descripcion' => 'Corrección de errores'],
            ['nombre' => 'Feature', 'color' => '#059669', 'descripcion' => 'Nueva funcionalidad'],
            ['nombre' => 'Mejora', 'color' => '#0891B2', 'descripcion' => 'Mejora de funcionalidad existente'],
            ['nombre' => 'Urgente', 'color' => '#DC2626', 'descripcion' => 'Requiere atención inmediata'],
            ['nombre' => 'Infraestructura', 'color' => '#64748B', 'descripcion' => 'Infraestructura y DevOps'],
            ['nombre' => 'Seguridad', 'color' => '#991B1B', 'descripcion' => 'Seguridad y vulnerabilidades'],
        ];

        foreach ($etiquetas as $etiqueta) {
            Etiqueta::firstOrCreate(
                ['slug' => Str::slug($etiqueta['nombre'])],
                $etiqueta
            );
        }

        $this->command->info('✓ Etiquetas creadas exitosamente');
    }
}
