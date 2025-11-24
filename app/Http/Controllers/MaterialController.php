<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use App\Models\TipoEntidad;
use App\Models\RegistroCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    private function ensureTipo(): TipoEntidad
    {
        // Crea o recupera el tipo "material" si no existe
        return TipoEntidad::firstOrCreate(
            ['clave' => 'material'],
            [
                'nombre' => 'Pequeño Material',
                'icono' => 'tool',
                'color' => '#38bdf8',
                'orden' => 3,
            ]
        );
    }

    public function index(Request $request)
    {
        $tipo = $this->ensureTipo();
        $query = Entidad::where('tipo_entidad_id', $tipo->id)->orderBy('created_at', 'desc');

        // Aplicar filtro por almacén si se proporciona
        if ($request->has('almacen_seleccionado') && $request->almacen_seleccionado) {
            $almacenId = $request->almacen_seleccionado;
            
            // Obtener el nombre del departamento usando el ID del almacén
            $departamento = \DB::table('departamentos')->where('id', $almacenId)->value('nombre');
            
            if ($departamento) {
                $query->where('departamento', $departamento);
            }
        }
        // También manejar el caso de almacen_ids (para compatibilidad con el middleware)
        elseif ($request->has('almacen_ids') && !empty($request->almacen_ids)) {
            $almacenIds = $request->almacen_ids;
            
            // Obtener los nombres de los departamentos usando los IDs
            $departamentos = \DB::table('departamentos')
                ->whereIn('id', $almacenIds)
                ->pluck('nombre');
                
            if ($departamentos->isNotEmpty()) {
                $query->whereIn('departamento', $departamentos);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_SEARCH(datos, 'one', ?) IS NOT NULL", ["%{$search}%"]);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tipo = $this->ensureTipo();

        $validator = Validator::make($request->all(), [
            'datos' => 'required|array',
            'datos.referencia' => 'required|string|max:100',
            'datos.tipo' => 'nullable|string|max:100',
            'datos.ubicacion' => 'nullable|string|max:150',
            'datos.stock_actual' => 'nullable|integer|min:0',
            'datos.stock_minimo' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = $request->input('datos');
        $datos['stock_actual'] = (int)($datos['stock_actual'] ?? 0);

        $entidad = Entidad::create([
            'tipo_entidad_id' => $tipo->id,
            'datos' => $datos,
            'usuario_creador_id' => $request->user()->id,
        ]);

        $this->registrar('crear', $entidad->id, null, $entidad->datos, $request);

        return response()->json([
            'success' => true,
            'message' => 'Material creado correctamente',
            'data' => $entidad,
        ], 201);
    }

    public function entrada(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:1',
            'nota' => 'nullable|string|max:250',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $entidad = Entidad::findOrFail($id);
        $datos = $entidad->datos ?? [];
        $datos['stock_actual'] = (int)($datos['stock_actual'] ?? 0) + (int)$request->cantidad;
        $entidad->update(['datos' => $datos]);

        $this->registrar('entrada', $entidad->id, null, [
            'cantidad' => (int)$request->cantidad,
            'nota' => $request->nota,
            'stock_actual' => $datos['stock_actual'],
        ], $request);

        return response()->json([
            'success' => true,
            'message' => 'Entrada registrada',
            'data' => $entidad,
        ]);
    }

    public function salida(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:1',
            'nota' => 'nullable|string|max:250',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $entidad = Entidad::findOrFail($id);
        $datos = $entidad->datos ?? [];
        $actual = (int)($datos['stock_actual'] ?? 0);
        $cantidad = (int)$request->cantidad;
        if ($cantidad > $actual) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente',
            ], 422);
        }
        $datos['stock_actual'] = $actual - $cantidad;
        $entidad->update(['datos' => $datos]);

        $this->registrar('salida', $entidad->id, null, [
            'cantidad' => $cantidad,
            'nota' => $request->nota,
            'stock_actual' => $datos['stock_actual'],
        ], $request);

        return response()->json([
            'success' => true,
            'message' => 'Salida registrada',
            'data' => $entidad,
        ]);
    }

    private function registrar(string $accion, int $entidadId, $antes, $despues, Request $request): void
    {
        // Mapear acciones a los valores permitidos por el enum
        $accionEnum = match ($accion) {
            'entrada' => 'modificar',
            'salida' => 'consumir',
            default => $accion,
        };
        RegistroCambio::create([
            'entidad_id' => $entidadId,
            'tipo_entidad' => 'material',
            'accion' => $accionEnum,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'usuario_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);
    }
}
