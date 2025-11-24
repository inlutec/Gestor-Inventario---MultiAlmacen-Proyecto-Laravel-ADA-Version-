<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Departamento;
use App\Models\CustomField;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    // Listado público (autenticado) para usar en selects en toda la app
    public function publicIndex()
    {
        $sedes = Sede::with('departamentos')->orderBy('nombre')->get(['id', 'nombre', 'clave']);
        // Force load minimal departamento fields
        $sedes->each(function ($s) { $s->setRelation('departamentos', $s->departamentos()->orderBy('nombre')->get(['id','sede_id','nombre','clave'])); });
        return response()->json(['success' => true, 'data' => $sedes]);
    }

    public function index()
    {
        $sedes = Sede::with('departamentos')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $sedes]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'clave' => 'nullable|string|max:100',
        ]);
        $data['clave'] = $data['clave'] ?? $this->slugify($data['nombre']);
        $sede = Sede::create($data);
        return response()->json(['success' => true, 'data' => $sede]);
    }

    public function update(Request $request, $id)
    {
        $sede = Sede::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'clave' => 'sometimes|string|max:100',
        ]);
        if (isset($data['nombre']) && !isset($data['clave'])) {
            $data['clave'] = $this->slugify($data['nombre']);
        }
        $sede->update($data);
        return response()->json(['success' => true, 'data' => $sede]);
    }

    public function destroy($id)
    {
        $sede = Sede::findOrFail($id);
        $sede->delete();
        return response()->json(['success' => true]);
    }

    public function addDepartamento(Request $request, $sedeId)
    {
        $sede = Sede::findOrFail($sedeId);
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'clave' => 'nullable|string|max:100',
        ]);
        $data['clave'] = $data['clave'] ?? $this->slugify($data['nombre']);
        $dep = $sede->departamentos()->create($data);
        return response()->json(['success' => true, 'data' => $dep]);
    }

    public function updateDepartamento(Request $request, $id)
    {
        $dep = Departamento::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'clave' => 'sometimes|string|max:100',
        ]);
        if (isset($data['nombre']) && !isset($data['clave'])) {
            $data['clave'] = $this->slugify($data['nombre']);
        }
        $dep->update($data);
        return response()->json(['success' => true, 'data' => $dep]);
    }

    public function deleteDepartamento($id)
    {
        $dep = Departamento::findOrFail($id);
        $dep->delete();
        return response()->json(['success' => true]);
    }

    // Sincroniza los campos personalizados 'departamento' en impresora, consumible y pequeño_material
    public function syncCustomFields()
    {
        $departamentos = Departamento::orderBy('nombre')->pluck('nombre')->toArray();
        $entityTypes = ['impresora', 'consumible', 'pequeño_material'];
        foreach ($entityTypes as $et) {
            $field = CustomField::where('entity_type', $et)->where('key', 'departamento')->first();
            $data = [
                'entity_type' => $et,
                'label' => 'Departamento',
                'key' => 'departamento',
                'type' => 'select',
                'options' => $departamentos,
                'required' => false,
            ];
            if ($field) {
                $field->update(['options' => $departamentos, 'required' => false]);
            } else {
                CustomField::create($data);
            }
        }
        return response()->json(['success' => true, 'options' => $departamentos]);
    }

    private function slugify($text)
    {
        $t = strtolower(trim($text));
        $t = preg_replace('/[^a-z0-9]+/','-',$t);
        return trim($t,'-');
    }
}
