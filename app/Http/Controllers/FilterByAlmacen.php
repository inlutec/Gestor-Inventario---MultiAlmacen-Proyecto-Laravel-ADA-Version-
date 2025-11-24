<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterByAlmacen
{
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario es administrador, no aplicar filtro por defecto
        // pero sí respetar el almacén seleccionado explícitamente
        if (Auth::check() && Auth::user()->role === 'admin') {
            // Si hay un almacén seleccionado explícitamente, usarlo
            if ($request->has('almacen_seleccionado') && $request->almacen_seleccionado) {
                $request->merge(['almacen_ids' => [$request->almacen_seleccionado]]);
            }
            return $next($request);
        }

        // Si el usuario está autenticado, determinar qué almacenes usar
        if (Auth::check()) {
            $almacenIds = [];
            
            // Prioridad 1: Almacén seleccionado explícitamente en la petición
            if ($request->has('almacen_seleccionado') && $request->almacen_seleccionado) {
                // Verificar que el usuario tenga acceso a este almacén
                try {
                    if (method_exists(Auth::user(), 'tieneAccesoAlmacen') && Auth::user()->tieneAccesoAlmacen($request->almacen_seleccionado)) {
                        $almacenIds = [$request->almacen_seleccionado];
                    }
                } catch (\Exception $e) {
                    // Si hay un error al verificar el acceso, no aplicar filtro
                    \Log::warning('Error verificando acceso a almacén: ' . $e->getMessage());
                }
            }
            
            // Prioridad 2: Si no hay almacén seleccionado, usar todos los almacenes del usuario
            if (empty($almacenIds)) {
                $almacenes = Auth::user()->almacenes;
                $almacenIds = $almacenes ? $almacenes->pluck('id')->toArray() : [];
            }
            
            // Añadir los IDs de almacén al request para que los controladores puedan usarlos
            if (!empty($almacenIds)) {
                $request->merge(['almacen_ids' => $almacenIds]);
            }
        }

        return $next($request);
    }
}