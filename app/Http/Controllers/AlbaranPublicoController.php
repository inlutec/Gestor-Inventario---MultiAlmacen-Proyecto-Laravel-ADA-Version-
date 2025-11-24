<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialMovimiento;
use App\Models\MaterialFirma;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class AlbaranPublicoController extends Controller
{
    /**
     * Ver albarán público (sin autenticación)
     */
    public function ver($token)
    {
        try {
            $movimiento = MaterialMovimiento::with(['detalles', 'firmas', 'usuario'])
                ->where('enlace_publico', $token)
                ->firstOrFail();

            // Verificar que el enlace sea válido
            if (!$movimiento->enlaceEsValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este enlace ha expirado o no es válido',
                ], 410);
            }

            // Determinar si ya está firmado según el tipo o por estado
            $yaFirmado = $movimiento->estado === 'firmado' || (
                $movimiento->tipo === 'entrada' ? (bool)$movimiento->firmaEmisor : (bool)$movimiento->firmaReceptor
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $movimiento->id,
                    'numero_documento' => $movimiento->numero_documento,
                    'tipo' => $movimiento->tipo,
                    'fecha_movimiento' => $movimiento->fecha_movimiento,
                    'origen' => $movimiento->origen,
                    'destino' => $movimiento->destino,
                    'observaciones' => $movimiento->observaciones,
                    'estado' => $movimiento->estado,
                    'detalles' => $movimiento->detalles->map(function($det) {
                        return [
                            'descripcion' => $det->descripcion,
                            'cantidad' => $det->cantidad,
                            'unidad' => $det->unidad,
                            'observaciones' => $det->observaciones,
                        ];
                    }),
                    'firma_emisor' => $movimiento->firmaEmisor ? [
                        'nombre_completo' => $movimiento->firmaEmisor->nombre_completo,
                        'fecha_firma' => $movimiento->firmaEmisor->fecha_firma,
                        'firma_rubrica' => $movimiento->firmaEmisor->firma_rubrica,
                    ] : null,
                    'firma_receptor' => $movimiento->firmaReceptor ? [
                        'nombre_completo' => $movimiento->firmaReceptor->nombre_completo,
                        'fecha_firma' => $movimiento->firmaReceptor->fecha_firma,
                    ] : null,
                    'ya_firmado' => $yaFirmado,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Albarán no encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al ver albarán público', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el albarán',
            ], 500);
        }
    }

    /**
     * Firmar albarán como receptor (sin autenticación)
     */
    public function firmar(Request $request, $token)
    {
        try {
            $movimiento = MaterialMovimiento::where('enlace_publico', $token)
                ->firstOrFail();

            // Verificar que el enlace sea válido
            if (!$movimiento->enlaceEsValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este enlace ha expirado o no es válido',
                ], 410);
            }

            // Para ENTRADAS firma el RECEPTOR (el que recibe localmente)
            // Para SALIDAS firma el RECEPTOR
            $tipoFirmante = 'receptor';

            // Verificar que no tenga ya esta firma
            if ($tipoFirmante === 'receptor' && $movimiento->firmaReceptor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este albarán ya ha sido firmado',
                ], 400);
            }
            if ($tipoFirmante === 'emisor' && $movimiento->firmaEmisor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este albarán ya ha sido firmado',
                ], 400);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'dni' => 'nullable|string|max:20',
                'firma_rubrica' => 'required|string', // Base64
            ]);

            // Crear firma
            MaterialFirma::create([
                'movimiento_id' => $movimiento->id,
                'tipo_firmante' => $tipoFirmante,
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'],
                'dni' => $validated['dni'] ?? null,
                'firma_rubrica' => $validated['firma_rubrica'],
                'ip_address' => $request->ip(),
                'fecha_firma' => now(),
                'datos_adicionales' => [
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Actualizar estado según firmas requeridas
            $movimiento->refresh();
            $movimiento->estado = $movimiento->tieneFirmasCompletas() ? 'firmado' : 'pendiente_firma';
            $movimiento->save();

            return response()->json([
                'success' => true,
                'message' => 'Albarán firmado correctamente',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al firmar albarán público', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al firmar el albarán',
            ], 500);
        }
    }

    /**
     * Descargar PDF del albarán (sin autenticación)
     */
    public function descargarPDF($token)
    {
        try {
            $movimiento = MaterialMovimiento::with(['detalles', 'firmas', 'usuario'])
                ->where('enlace_publico', $token)
                ->firstOrFail();

            // Verificar que el enlace sea válido
            if (!$movimiento->enlaceEsValido()) {
                abort(410, 'Este enlace ha expirado');
            }

            // Verificar que esté firmado completamente según requerimientos
            if (!$movimiento->tieneFirmasCompletas()) {
                abort(403, 'El documento debe estar completamente firmado para descargar el PDF');
            }

            $pdf = Pdf::loadView('pdf.albaran', [
                'movimiento' => $movimiento,
                'detalles' => $movimiento->detalles,
                'firmaEmisor' => $movimiento->firmaEmisor,
                'firmaReceptor' => $movimiento->firmaReceptor,
            ]);

            $filename = "albaran_{$movimiento->numero_documento}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error al descargar PDF público', ['error' => $e->getMessage()]);
            abort(500, 'Error al generar el PDF');
        }
    }
}
