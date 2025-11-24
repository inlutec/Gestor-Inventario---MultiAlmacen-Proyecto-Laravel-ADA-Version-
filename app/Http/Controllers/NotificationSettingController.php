<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    /**
     * Obtener todas las configuraciones de notificaciones
     */
    public function index()
    {
        try {
            $settings = NotificationSetting::orderBy('evento')->get();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración de un evento
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'notificar_usuario' => 'required|boolean',
            'notificar_admin' => 'required|boolean',
        ]);

        try {
            $setting = NotificationSetting::findOrFail($id);
            $setting->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar múltiples configuraciones a la vez
     */
    public function updateBatch(Request $request)
    {
        $validated = $request->validate([
            'configuraciones' => 'required|array',
            'configuraciones.*.id' => 'required|exists:notification_settings,id',
            'configuraciones.*.notificar_usuario' => 'required|boolean',
            'configuraciones.*.notificar_admin' => 'required|boolean',
        ]);

        try {
            foreach ($validated['configuraciones'] as $config) {
                NotificationSetting::where('id', $config['id'])->update([
                    'notificar_usuario' => $config['notificar_usuario'],
                    'notificar_admin' => $config['notificar_admin'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuraciones actualizadas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
