<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PlanoController extends Controller
{
    public function index(Request $request)
    {
        $query = Plano::query()->with('usuarioCreador');
        if ($request->filled('sede')) {
            $query->where('sede', $request->input('sede'));
        }
        $planos = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $planos,
        ]);
    }

    public function show($id)
    {
        $plano = Plano::with(['entidades.tipoEntidad', 'usuarioCreador'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plano,
        ]);
    }

    public function store(Request $request)
    {
        // Validación de campos base (sin imagen para evitar conflicto de tipo)
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'sede' => 'required|string|max:100',
            'descripcion' => 'sometimes|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->hasFile('imagen')) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => ['imagen' => ['La imagen es requerida']]
            ], 422);
        }

        $file = $request->file('imagen');
        // Verificaciones manuales: MIME y dimensiones exactas 3000x2000
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/jpg'])) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => ['imagen' => ['La imagen debe ser JPG/JPEG']]
            ], 422);
        }
        $size = @getimagesize($file->getPathname());
        if (!$size || $size[0] !== 3000 || $size[1] !== 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => ['imagen' => ['Dimensiones inválidas (debe ser 3000x2000 píxeles)']]
            ], 422);
        }

        $safe = preg_replace('/[^A-Za-z0-9_.-]/', '_', $request->nombre . '_' . $request->sede);
        $path = $file->storeAs('public/planos', $safe . '_' . time() . '.jpg');
        $publicPath = str_replace('public/', '', $path); // storage link

        $plano = Plano::create([
            'nombre' => $request->nombre,
            'sede' => $request->sede,
            'imagen' => $publicPath,
            'descripcion' => $request->descripcion,
            'usuario_creador_id' => optional($request->user())->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plano creado correctamente',
            'data' => $plano,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $plano = Plano::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:100',
            'sede' => 'sometimes|string|max:100',
            'descripcion' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['nombre', 'sede', 'descripcion']);
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $mime = $file->getMimeType();
            if (!in_array($mime, ['image/jpeg', 'image/jpg'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => ['imagen' => ['La imagen debe ser JPG/JPEG']]
                ], 422);
            }
            $size = @getimagesize($file->getPathname());
            if (!$size || $size[0] !== 3000 || $size[1] !== 2000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => ['imagen' => ['Dimensiones inválidas (debe ser 3000x2000 píxeles)']]
                ], 422);
            }
            $safe = preg_replace('/[^A-Za-z0-9_.-]/', '_', ($request->nombre ?? $plano->nombre) . '_' . ($request->sede ?? $plano->sede));
            $path = $file->storeAs('public/planos', $safe . '_' . time() . '.jpg');
            $data['imagen'] = str_replace('public/', '', $path);
        }
        $plano->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plano actualizado correctamente',
            'data' => $plano,
        ]);
    }

    public function destroy($id)
    {
        $plano = Plano::findOrFail($id);
        $plano->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plano eliminado correctamente',
        ]);
    }

    // Ubicaciones de impresoras en un plano
    public function ubicaciones($id)
    {
        $plano = Plano::findOrFail($id);
        $list = \App\Models\PlanoUbicacion::where('plano_id', $plano->id)->get();
        return response()->json(['success' => true, 'data' => $list]);
    }

    public function setUbicacion(Request $request, $id)
    {
        $plano = Plano::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'hostname' => 'required|string',
            'x' => 'required|integer|min:0|max:3000',
            'y' => 'required|integer|min:0|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $ubic = \App\Models\PlanoUbicacion::updateOrCreate(
            ['plano_id' => $plano->id, 'hostname' => $request->hostname],
            ['x' => $request->x, 'y' => $request->y]
        );
        return response()->json(['success' => true, 'data' => $ubic]);
    }

    public function eliminarUbicacion($id, $hostname)
    {
        $plano = Plano::findOrFail($id);
        \App\Models\PlanoUbicacion::where('plano_id', $plano->id)->where('hostname', $hostname)->delete();
        return response()->json(['success' => true]);
    }

    // Obtener la ubicación (y plano asociado) de una impresora por hostname
    public function ubicacionImpresora(Request $request)
    {
        $hostname = $request->query('hostname');
        if (!$hostname) return response()->json(['success' => false, 'message' => 'hostname requerido'], 422);
        $ubic = \App\Models\PlanoUbicacion::where('hostname', $hostname)->latest()->first();
        if (!$ubic) return response()->json(['success' => true, 'data' => null]);
        $plano = $ubic->plano;
        return response()->json(['success' => true, 'data' => [
            'plano' => $plano,
            'ubicacion' => ['x' => $ubic->x, 'y' => $ubic->y]
        ]]);
    }
}
