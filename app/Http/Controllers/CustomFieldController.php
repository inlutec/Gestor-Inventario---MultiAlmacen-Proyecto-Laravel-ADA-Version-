<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $entity = $request->query('entity_type') ?? $request->query('tipo_entidad');
        $q = CustomField::query()->where('active', true)->orderBy('sort_order');
        if ($entity) $q->where('entity_type', $entity);
        return response()->json(['success' => true, 'data' => $q->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entity_type' => 'required|string',
            'label' => 'required|string',
            'key' => 'nullable|string',
            'type' => 'nullable|string',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'sort_order' => 'nullable|integer',
            'active' => 'boolean',
        ]);
        $data['key'] = $data['key'] ?: $this->slugify($data['label']);
        $field = CustomField::create($data);
        return response()->json(['success' => true, 'data' => $field]);
    }

    public function update(Request $request, $id)
    {
        $field = CustomField::findOrFail($id);
        $data = $request->validate([
            'label' => 'sometimes|string',
            'key' => 'sometimes|string',
            'type' => 'sometimes|string',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'sort_order' => 'nullable|integer',
            'active' => 'boolean',
        ]);
        if (isset($data['label']) && empty($data['key'])) {
            $data['key'] = $this->slugify($data['label']);
        }
        $field->update($data);
        return response()->json(['success' => true, 'data' => $field]);
    }

    public function destroy($id)
    {
        $field = CustomField::findOrFail($id);
        $field->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $items = $request->validate(['orders' => 'required|array']);
        foreach ($items['orders'] as $id => $order) {
            CustomField::where('id', $id)->update(['sort_order' => (int)$order]);
        }
        return response()->json(['success' => true]);
    }

    private function slugify($text)
    {
        $t = strtolower(trim($text));
        $t = preg_replace('/[^a-z0-9]+/','-',$t);
        return trim($t,'-');
    }
}
