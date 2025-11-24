<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoEntidad;
use App\Models\Entidad;
use App\Services\CheckMKService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ImportadorStreamingController extends Controller
{
    protected $checkmkService;

    public function __construct()
    {
        $this->checkmkService = new CheckMKService();
    }

    public function importarCSV(Request $request)
    {
        // Verificar autenticación: intentar primero con Sanctum, luego con sesión personalizada
        $user = $request->user('sanctum');
        
        if (!$user) {
            // Intentar con cookie de token
            $token = $request->cookie('token');
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }
        
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt',
            'sincronizar_checkmk' => 'sometimes',
            'actualizar_existentes' => 'sometimes',
        ]);

        $archivo = $request->file('archivo');
        $sincronizarCheckmk = $request->input('sincronizar_checkmk') === '1';
        $actualizarExistentes = $request->input('actualizar_existentes') === '1';

        // Deshabilitar buffering
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        
        return response()->stream(function () use ($archivo, $sincronizarCheckmk, $actualizarExistentes) {
            // Configurar sin buffering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $this->enviarEvento('start', 'Iniciando importación...');

            try {
                $contenido = file_get_contents($archivo->getRealPath());
                $lineas = str_getcsv($contenido, "\n");
                
                $datos = [];
                foreach ($lineas as $linea) {
                    if (trim($linea) !== '') {
                        $datos[] = str_getcsv($linea, ';');
                    }
                }

                array_shift($datos);
                
                $this->enviarEvento('info', sprintf('📄 Archivo cargado: %d filas detectadas', count($datos)));

                $tipoEntidad = TipoEntidad::firstOrCreate(
                    ['clave' => 'impresora'],
                    ['nombre' => 'Impresoras', 'icono' => 'printer', 'color' => '#006A4E', 'orden' => 1]
                );

                $total = count($datos);
                $importados = 0;
                $actualizados = 0;
                $errores = 0;
                $sincronizados = 0;

                $this->enviarProgreso($total, 0, 0, 0);

                foreach ($datos as $index => $fila) {
                    $numeroFila = $index + 2;
                    
                    try {
                        $referencia = $this->obtenerValor($fila, 5);
                        $ip = $this->obtenerValor($fila, 8); // Columna I - Dirección de red

                        if (!$referencia || !$ip) {
                            throw new \Exception('Datos incompletos');
                        }

                        $datosImpresora = [
                            'nombre' => $this->obtenerValor($fila, 5),         // Columna F: Nombre
                            'tipo_entidad_id' => $tipoEntidad->id,
                            'referencia' => $referencia,                        // Columna F: Nombre (usado como ref)
                            'activo' => true,
                            'ip' => $ip,                                        // Columna I: Dirección de red/USB
                            'ubicacion' => $this->obtenerValor($fila, 16),     // Columna Q: Ubicación
                            'marca' => $this->obtenerValor($fila, 11),         // Columna L: Fabricante
                            'modelo' => $this->obtenerValor($fila, 12),        // Columna M: Modelo
                            'numero_serie' => $this->obtenerValor($fila, 6),   // Columna G: Número de Serie
                            'tipo' => null,
                            'color' => null,
                            'grupo' => null,
                            'centro_gestor' => $this->obtenerValor($fila, 3),  // Columna D: Sitio
                            'servicio' => null,
                            'departamento' => $this->obtenerValor($fila, 4),   // Columna E: Departamento
                        ];

                        $entidad = Entidad::where('referencia', $referencia)->first();
                        
                        if ($entidad) {
                            if ($actualizarExistentes) {
                                $entidad->update($datosImpresora);
                                $actualizados++;
                                $this->enviarEvento('success', sprintf('  ↻ Actualizado: %s', $referencia));
                            }
                        } else {
                            $entidad = Entidad::create($datosImpresora);
                            $importados++;
                            $this->enviarEvento('success', sprintf('  ✓ Importado: %s', $referencia));
                        }

                        if ($sincronizarCheckmk) {
                            try {
                                $hostname = $this->generarHostname($referencia);
                                $this->enviarEvento('info', sprintf('🔗 CheckMK: Iniciando sincronización de %s...', $hostname));

                                // Usar el método secuencial con callback para feedback en tiempo real
                                $resultado = $this->checkmkService->createHostWithDiscoverySequential(
                                    $hostname, 
                                    $ip, 
                                    [
                                        'alias' => $referencia,
                                        'tag_agent' => 'no-agent',
                                        'tag_snmp_ds' => 'snmp-v2',
                                        'snmp_community' => 'public',
                                    ],
                                    function($tipo, $mensaje) {
                                        // Enviar feedback en tiempo real al cliente
                                        $this->enviarEvento($tipo, '    ' . $mensaje);
                                    }
                                );

                                if ($resultado['success']) {
                                    $entidad->host_checkmk = $hostname;
                                    $entidad->save();
                                    $sincronizados++;
                                    
                                    if (isset($resultado['already_exists']) && $resultado['already_exists']) {
                                        $this->enviarEvento('warning', sprintf('  ⚠️  Host ya existía en Check_MK'));
                                    } else {
                                        $this->enviarEvento('success', '  ✓ Sincronización completada - autodescubrimiento en proceso');
                                    }
                                } else {
                                    $this->enviarEvento('warning', sprintf('  ⚠️  Error en sincronización: %s', $resultado['error'] ?? 'desconocido'));
                                }

                            } catch (\Exception $e) {
                                $this->enviarEvento('warning', sprintf('  ⚠️  CheckMK: %s', substr($e->getMessage(), 0, 80)));
                            }
                        }
                        
                    } catch (\Exception $e) {
                        $errores++;
                        $this->enviarEvento('error', sprintf('✗ Fila %d: %s', $numeroFila, substr($e->getMessage(), 0, 100)));
                    }

                    $this->enviarProgreso($total, $index + 1, $importados + $actualizados, $errores);
                }

                $this->enviarEvento('complete', json_encode([
                    'total' => $total,
                    'importados' => $importados,
                    'actualizados' => $actualizados,
                    'errores' => $errores,
                    'sincronizados_checkmk' => $sincronizados
                ]));

            } catch (\Exception $e) {
                $this->enviarEvento('error', 'Error fatal: ' . $e->getMessage());
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function enviarEvento($tipo, $mensaje)
    {
        echo "data: " . json_encode(['type' => $tipo, 'message' => $mensaje]) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function enviarProgreso($total, $procesados, $exitosos, $errores)
    {
        echo "data: " . json_encode([
            'type' => 'progress',
            'total' => $total,
            'procesados' => $procesados,
            'exitosos' => $exitosos,
            'errores' => $errores
        ]) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function obtenerValor($fila, $indice)
    {
        return isset($fila[$indice]) ? trim($fila[$indice]) : null;
    }

    private function generarHostname($referencia)
    {
        return Str::slug(strtolower($referencia), '-');
    }
}
