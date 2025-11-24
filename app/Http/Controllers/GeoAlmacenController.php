<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Departamento;
use App\Models\Sede;
use App\Models\Provincia;

class GeoAlmacenController extends Controller
{
    /**
     * Obtener almacenes con coordenadas para el mapa público
     */
    public function almacenesPublicos()
    {
        $almacenes = Departamento::where('es_almacen', true)
            ->with(['sede.provincia'])
            ->get()
            ->map(function($departamento) {
                // Coordenadas aproximadas de cada provincia en Andalucía
                $coordenadas = $this->getCoordenadasProvincia($departamento->sede->provincia->nombre ?? '');
                
                return [
                    'id' => $departamento->id,
                    'nombre' => $departamento->nombre,
                    'sede' => $departamento->sede->nombre ?? '',
                    'provincia' => $departamento->sede->provincia->nombre ?? '',
                    'provincia_id' => $departamento->sede->provincia_id ?? null,
                    'lat' => $coordenadas['lat'],
                    'lng' => $coordenadas['lng'],
                    'direccion' => $departamento->direccion ?? ''
                ];
            });

        // Obtener todas las provincias disponibles
        $provincias = Provincia::orderBy('nombre')->pluck('nombre')->unique()->values()->toArray();

        return response()->json([
            'success' => true,
            'almacenes' => $almacenes,
            'provincias' => $provincias
        ]);
    }

    /**
     * Obtener almacenes agrupados por provincia
     */
    public function almacenesPorProvincia()
    {
        $almacenes = Departamento::where('es_almacen', true)
            ->with(['sede.provincia'])
            ->get()
            ->groupBy(function($departamento) {
                return $departamento->sede->provincia->nombre ?? 'Sin provincia';
            })
            ->map(function($grupo, $nombreProvincia) {
                $provincia = $grupo->first()->sede->provincia;
                $coordenadas = $this->getCoordenadasProvincia($nombreProvincia);
                
                return [
                    'provincia' => $nombreProvincia,
                    'provincia_id' => $provincia->id ?? null,
                    'lat' => $coordenadas['lat'],
                    'lng' => $coordenadas['lng'],
                    'almacenes' => $grupo->map(function($departamento) {
                        return [
                            'id' => $departamento->id,
                            'nombre' => $departamento->nombre,
                            'sede' => $departamento->sede->nombre ?? '',
                            'direccion' => $departamento->direccion ?? ''
                        ];
                    })->toArray()
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $almacenes
        ]);
    }

    /**
     * Obtener sedes por provincia para el formulario
     */
    public function sedesPorProvincia(Request $request)
    {
        $provinciaId = $request->get('provincia_id');
        
        if (!$provinciaId) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere ID de provincia'
            ], 400);
        }

        $sedes = Sede::where('provincia_id', $provinciaId)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json([
            'success' => true,
            'data' => $sedes
        ]);
    }

    /**
     * Coordenadas aproximadas de las provincias de Andalucía
     */
    private function getCoordenadasProvincia($provincia)
    {
        $coordenadas = [
            'Almería' => ['lat' => 36.8381, 'lng' => -2.4597],
            'Cádiz' => ['lat' => 36.5203, 'lng' => -6.2885],
            'Córdoba' => ['lat' => 37.8882, 'lng' => -4.7794],
            'Granada' => ['lat' => 37.1882, 'lng' => -3.6067],
            'Huelva' => ['lat' => 37.2614, 'lng' => -6.9447],
            'Jaén' => ['lat' => 37.7796, 'lng' => -3.7846],
            'Málaga' => ['lat' => 36.7202, 'lng' => -4.4203],
            'Sevilla' => ['lat' => 37.3891, 'lng' => -5.9845]
        ];

        return $coordenadas[$provincia] ?? ['lat' => 37.5, 'lng' => -4.5]; // Coordenadas por defecto
    }
}