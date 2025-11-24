<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Ensure sedes table exists
        if (!DB::getSchemaBuilder()->hasTable('sedes')) {
            return;
        }

        // Default preprogrammed sedes
        $defaults = [
            'Constitución',
            'Cultura',
            'Deportes',
            'Igualdad',
            'Biblioteca',
        ];

        foreach ($defaults as $nombre) {
            $clave = Str::slug($nombre);
            $exists = DB::table('sedes')->where('clave', $clave)->exists();
            if (!$exists) {
                DB::table('sedes')->insert([
                    'nombre' => $nombre,
                    'clave' => $clave,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Import distinct sedes from planos.sede if present
        if (DB::getSchemaBuilder()->hasColumn('planos', 'sede')) {
            $planosSedes = DB::table('planos')
                ->select('sede')
                ->whereNotNull('sede')
                ->whereRaw("TRIM(sede) <> ''")
                ->distinct()
                ->pluck('sede')
                ->toArray();

            foreach ($planosSedes as $nombre) {
                $nombre = trim($nombre);
                if ($nombre === '') continue;
                $clave = Str::slug($nombre);
                $exists = DB::table('sedes')->where('clave', $clave)->exists();
                if (!$exists) {
                    DB::table('sedes')->insert([
                        'nombre' => $nombre,
                        'clave' => $clave,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: we don't want to remove seeded data on rollback
    }
};
