<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Entidad;
use App\Models\TipoEntidad;
use App\Services\CheckMKService;
use Carbon\Carbon;

class ImportadorController extends Controller
{
    protected $checkmkService;

    public function __construct()
    {
        try {
            $this->checkmkService = new CheckMKService();
        } catch (\Exception $e) {
            Log::warning('CheckMK no configurado para importador: ' . $e->getMessage());
            $this->checkmkService = null;
        }
    }

    /**
     * Procesar archivo CSV de impresoras
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importarCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'sincronizar_checkmk' => 'boolean',
            'actualizar_existentes' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sincronizarCheckmk = $request->input('sincronizar_checkmk', true);
        $actualizarExistentes = $request->input('actualizar_existentes', true);

        try {
            $archivo = $request->file('archivo');
            $contenido = file_get_contents($archivo->getPathname());
            
            // Detectar encoding y convertir a UTF-8 si es necesario
            $encoding = mb_detect_encoding($contenido, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
            }

            // Parsear CSV
            $lineas = str_getcsv($contenido, "\n");
            $datos = [];
            foreach ($lineas as $linea) {
                $datos[] = str_getcsv($linea, ';');
            }

            if (empty($datos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo CSV está vacío',
                ], 400);
            }

            // Obtener o crear el tipo de entidad "Impresora"
            $tipoImpresora = TipoEntidad::firstOrCreate(
                ['clave' => 'impresora'],
                [
                    'nombre' => 'Impresora',
                    'icono' => 'printer',
                    'color' => '#4f46e5',
                    'orden' => 1
                ]
            );

            $resultado = $this->procesarDatosCSV($datos, $tipoImpresora->id, $sincronizarCheckmk, $actualizarExistentes);

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al importar CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar los datos del CSV y crear/actualizar entidades
     * 
     * @param array $datos Datos del CSV
     * @param int $tipoEntidadId ID del tipo de entidad
     * @param bool $sincronizarCheckmk Si debe sincronizar con CheckMK
     * @param bool $actualizarExistentes Si debe actualizar registros existentes
     * @return array Resultado del procesamiento
     */
    private function procesarDatosCSV($datos, $tipoEntidadId, $sincronizarCheckmk, $actualizarExistentes)
    {
        $headers = array_shift($datos); // Primera línea son los encabezados
        
        // Mapeo de columnas según el usuario
        // D=Sede, E=Departamento, F=Referencia, G=Numero de Serie, I=IP, L=Marca, M=Modelo, N=División, P=Planta, Q=Ubicación
        $mapeo = [
            'sede' => 3,           // Columna D (índice 3)
            'departamento' => 4,   // Columna E (índice 4)
            'referencia' => 5,     // Columna F (índice 5)
            'numero_serie' => 6,   // Columna G (índice 6)
            'ip' => 8,             // Columna I (índice 8)
            'marca' => 11,         // Columna L (índice 11)
            'modelo' => 12,        // Columna M (índice 12)
            'division' => 13,      // Columna N (índice 13)
            'planta' => 15,        // Columna P (índice 15)
            'ubicacion' => 16,     // Columna Q (índice 16)
        ];

        $resultado = [
            'total' => count($datos),
            'importados' => 0,
            'actualizados' => 0,
            'errores' => 0,
            'sincronizados_checkmk' => 0,
            'detalles' => [],
        ];

        foreach ($datos as $index => $fila) {
            $numeroFila = $index + 2; // +2 porque quitamos header y los arrays empiezan en 0

            try {
                // Extraer datos de la fila
                $datosImpresora = [
                    'sede' => $this->obtenerValor($fila, $mapeo['sede']),
                    'departamento' => $this->obtenerValor($fila, $mapeo['departamento']),
                    'referencia' => $this->obtenerValor($fila, $mapeo['referencia']),
                    'numero_serie' => $this->obtenerValor($fila, $mapeo['numero_serie']),
                    'ip' => $this->obtenerValor($fila, $mapeo['ip']),
                    'marca' => $this->obtenerValor($fila, $mapeo['marca']),
                    'modelo' => $this->obtenerValor($fila, $mapeo['modelo']),
                    'division' => $this->obtenerValor($fila, $mapeo['division']),
                    'planta' => $this->obtenerValor($fila, $mapeo['planta']),
                    'ubicacion' => $this->obtenerValor($fila, $mapeo['ubicacion']),
                ];

                // Validar que tenga al menos referencia o IP
                if (empty($datosImpresora['referencia']) && empty($datosImpresora['ip'])) {
                    throw new \Exception('Falta referencia o IP');
                }

                // Buscar si ya existe por referencia o IP
                $entidadExistente = null;
                if (!empty($datosImpresora['referencia'])) {
                    $entidadExistente = Entidad::where('referencia', $datosImpresora['referencia'])->first();
                }
                if (!$entidadExistente && !empty($datosImpresora['ip'])) {
                    $entidadExistente = Entidad::where('ip', $datosImpresora['ip'])->first();
                }

                $esActualizacion = false;
                $entidad = null;

                if ($entidadExistente) {
                    if ($actualizarExistentes) {
                        // Actualizar entidad existente
                        $entidadExistente->update($datosImpresora);
                        $entidad = $entidadExistente;
                        $esActualizacion = true;
                        $resultado['actualizados']++;
                    } else {
                        // Omitir si no se permiten actualizaciones
                        $resultado['detalles'][] = [
                            'fila' => $numeroFila,
                            'referencia' => $datosImpresora['referencia'],
                            'status' => 'omitido',
                            'mensaje' => 'Ya existe y no se permiten actualizaciones',
                        ];
                        continue;
                    }
                } else {
                    // Crear nueva entidad
                    $datosImpresora['tipo_entidad_id'] = $tipoEntidadId;
                    $datosImpresora['datos'] = json_encode([
                        'nombre' => $datosImpresora['referencia'] ?? 'Impresora',
                    ]);
                    
                    $entidad = Entidad::create($datosImpresora);
                    $resultado['importados']++;
                }

                // Sincronizar con CheckMK si está habilitado y tenemos IP
                $checkmkSync = null;
                if ($sincronizarCheckmk && $this->checkmkService && !empty($datosImpresora['ip'])) {
                    try {
                        $hostname = $this->generarHostname($datosImpresora);
                        
                        // Verificar si el host ya existe en CheckMK
                        if ($this->checkmkService->hostExists($hostname)) {
                            // Actualizar host existente
                            $this->checkmkService->updateHost($hostname, [
                                'ipaddress' => $datosImpresora['ip'],
                                'alias' => $datosImpresora['referencia'] ?? $hostname,
                            ]);
                            $checkmkSync = 'actualizado';
                        } else {
                            // Crear host nuevo con autodescubrimiento
                            $checkmkResult = $this->checkmkService->createHostWithDiscovery(
                                $hostname,
                                $datosImpresora['ip'],
                                [
                                    'alias' => $datosImpresora['referencia'] ?? $hostname,
                                    'tag_agent' => 'no-agent',
                                    'tag_snmp_ds' => 'snmp-v2',
                                    'snmp_community' => 'public',
                                ]
                            );
                            $checkmkSync = $checkmkResult['success'] ? 'creado' : 'error';
                        }

                        // Guardar el hostname de CheckMK en la entidad
                        $entidad->host_checkmk = $hostname;
                        $entidad->save();

                        $resultado['sincronizados_checkmk']++;
                    } catch (\Exception $e) {
                        Log::warning('Error al sincronizar con CheckMK', [
                            'referencia' => $datosImpresora['referencia'],
                            'error' => $e->getMessage(),
                        ]);
                        $checkmkSync = 'error: ' . $e->getMessage();
                    }
                }

                $resultado['detalles'][] = [
                    'fila' => $numeroFila,
                    'referencia' => $datosImpresora['referencia'],
                    'ip' => $datosImpresora['ip'],
                    'status' => $esActualizacion ? 'actualizado' : 'creado',
                    'checkmk' => $checkmkSync,
                ];

            } catch (\Exception $e) {
                $resultado['errores']++;
                $resultado['detalles'][] = [
                    'fila' => $numeroFila,
                    'status' => 'error',
                    'mensaje' => $e->getMessage(),
                ];

                Log::error('Error al procesar fila del CSV', [
                    'fila' => $numeroFila,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $resultado;
    }

    /**
     * Obtener valor de una columna, devolviendo null si no existe o está vacío
     * 
     * @param array $fila Fila del CSV
     * @param int $indice Índice de la columna
     * @return string|null Valor de la columna
     */
    private function obtenerValor($fila, $indice)
    {
        if (!isset($fila[$indice])) {
            return null;
        }

        $valor = trim($fila[$indice]);
        return $valor === '' ? null : $valor;
    }

    /**
     * Generar un hostname válido para CheckMK basado en los datos de la impresora
     * 
     * @param array $datos Datos de la impresora
     * @return string Hostname generado
     */
    private function generarHostname($datos)
    {
        // Prioridad: referencia > IP
        if (!empty($datos['referencia'])) {
            // Limpiar la referencia para que sea un hostname válido
            $hostname = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $datos['referencia']);
            return strtolower($hostname);
        }

        if (!empty($datos['ip'])) {
            // Usar la IP reemplazando puntos por guiones
            return 'printer-' . str_replace('.', '-', $datos['ip']);
        }

        // Fallback: generar un hostname único
        return 'printer-' . uniqid();
    }

    /**
     * Obtener vista previa del CSV sin importar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previsualizarCSV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $archivo = $request->file('archivo');
            $contenido = file_get_contents($archivo->getPathname());
            
            // Detectar encoding y convertir a UTF-8
            $encoding = mb_detect_encoding($contenido, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
            }

            // Parsear CSV
            $lineas = str_getcsv($contenido, "\n");
            $datos = [];
            foreach ($lineas as $linea) {
                $datos[] = str_getcsv($linea, ';');
            }

            if (empty($datos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo CSV está vacío',
                ], 400);
            }

            $headers = $datos[0];
            $filas = array_slice($datos, 1, 10); // Solo las primeras 10 filas para previsualización

            return response()->json([
                'success' => true,
                'data' => [
                    'headers' => $headers,
                    'filas_muestra' => $filas,
                    'total_filas' => count($datos) - 1, // -1 por el header
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al previsualizar CSV', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
