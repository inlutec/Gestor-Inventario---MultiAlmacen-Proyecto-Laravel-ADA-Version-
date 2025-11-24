<?php

namespace App\Http\Controllers;

use App\Models\ImpresoraCheckmkSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckMKController extends Controller
{
    private $checkmkUrl = 'http://10.66.129.103';
    private $checkmkUser = 'api_user';
    private $checkmkPassword = 'wMQrkNQJZR6FULpw';
    private $checkmkSite = 'admin'; // Site name en CheckMK (ajustar según configuración)

    /**
     * Sincronizar datos de impresoras desde CheckMK
     */
    public function sincronizar(Request $request)
    {
        try {
            \Artisan::call('checkmk:sync');
            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en sincronización CheckMK', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar con CheckMK: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener listado de impresoras sincronizadas (últimos datos)
     */
    public function listarImpresoras()
    {
        try {
            $impresoras = ImpresoraCheckmkSync::getLatestForAllPrinters();
            // Adjuntar consumibles dinámicos
            $hostnames = collect($impresoras)->pluck('hostname')->filter()->values()->all();
            if (!empty($hostnames)) {
                $porHost = \App\Models\ImpresoraConsumible::getLatestByHostnames($hostnames);
                foreach ($impresoras as $i => $imp) {
                    $hn = $imp->hostname ?? null;
                    $impresoras[$i]['consumibles'] = $hn && isset($porHost[$hn]) ? $porHost[$hn] : [];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $impresoras,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener impresoras: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalles de una impresora específica
     */
    public function obtenerImpresora($hostname)
    {
        try {
            $impresora = ImpresoraCheckmkSync::where('hostname', $hostname)
                ->orderBy('sync_timestamp', 'desc')
                ->first();

            if (!$impresora) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impresora no encontrada',
                ], 404);
            }

            // Obtener consumibles
            $consumibles = \App\Models\ImpresoraConsumible::where('hostname', $hostname)
                ->orderBy('sync_timestamp', 'desc')
                ->first();

            $data = [
                'hostname' => $impresora->hostname,
                'display_name' => $impresora->display_name,
                'ip_address' => $impresora->ip_address,
                'estado' => $impresora->estado,
                'marca' => $impresora->datos_adicionales['marca'] ?? null,
                'modelo' => $impresora->datos_adicionales['modelo'] ?? null,
                'paginas_total' => $impresora->paginas_total,
                'paginas_bn' => $impresora->paginas_bn,
                'paginas_color' => $impresora->paginas_color,
                'toner_black' => $consumibles->toner_black ?? null,
                'toner_cyan' => $consumibles->toner_cyan ?? null,
                'toner_magenta' => $consumibles->toner_magenta ?? null,
                'toner_yellow' => $consumibles->toner_yellow ?? null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener impresora: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar datos adicionales de una impresora (sin crear entidad de inventario)
     */
    public function actualizarImpresora(Request $request, $hostname)
    {
        try {
            $imp = ImpresoraCheckmkSync::getLatestByHostname($hostname);
            if (!$imp) {
                return response()->json(['success' => false, 'message' => 'Impresora no encontrada'], 404);
            }

            // Actualizar departamento_id si viene en la petición
            if ($request->has('departamento_id')) {
                $imp->departamento_id = $request->departamento_id;
            }

            $payload = $request->only([
                'referencia', 'ip', 'marca', 'modelo', 'sede', 'numero_serie', 'ubicacion', 'custom_fields'
            ]);

            $extra = $imp->datos_adicionales ?? [];
            foreach ($payload as $k => $v) {
                if ($v !== null) {
                    $extra[$k] = $v;
                }
            }
            $imp->datos_adicionales = $extra;
            $imp->save();

            return response()->json(['success' => true, 'data' => $imp]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir foto para una impresora CheckMK y guardarla como dato adicional
     */
    public function subirFoto(Request $request, $hostname)
    {
        try {
            $request->validate(['photo' => 'required|image|max:5120']);
            $imp = ImpresoraCheckmkSync::getLatestByHostname($hostname);
            if (!$imp) {
                return response()->json(['success' => false, 'message' => 'Impresora no encontrada'], 404);
            }

            $file = $request->file('photo');
            $safe = preg_replace('/[^A-Za-z0-9_.-]/', '_', $hostname);
            $path = $file->storeAs('public/printers', $safe . '_' . time() . '.' . $file->getClientOriginalExtension());
            $publicPath = str_replace('public/', '', $path); // storage link

            $extra = $imp->datos_adicionales ?? [];
            $extra['foto'] = $publicPath; // ej: printers/host_123.jpg
            $imp->datos_adicionales = $extra;
            $imp->save();

            return response()->json(['success' => true, 'path' => '/storage/' . $publicPath]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo subir la foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener historial de una impresora específica
     */
    public function historialImpresora($hostname)
    {
        try {
            $historial = ImpresoraCheckmkSync::getHistoryByHostname($hostname, 100);
            
            return response()->json([
                'success' => true,
                'data' => $historial,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de consumo de una impresora
     */
    public function estadisticasImpresora(Request $request, $hostname)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->subDays(30));
            $endDate = $request->input('end_date', Carbon::now());
            
            $stats = ImpresoraCheckmkSync::getConsumptionStats($hostname, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener hosts (impresoras) desde CheckMK API
     */
    private function obtenerHostsCheckmk()
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/host_config/collections/all";
        
        $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->get($url);

        if (!$response->successful()) {
            Log::error('Error al obtener hosts de CheckMK', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('No se pudo conectar con CheckMK API: ' . $response->status());
        }

        $data = $response->json();
        return $data['value'] ?? [];
    }

    /**
     * Obtener servicios de un host específico
     */
    private function obtenerServiciosHost($hostname)
    {
        // Usar el formato JSON export de vistas que incluye plugin_output
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/view.py?view_name=allservices&output_format=json_export&host=" . urlencode($hostname);
        
        $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
            ->get($url);

        if (!$response->successful()) {
            Log::warning("No se pudieron obtener servicios para host {$hostname}: " . $response->status());
            return [];
        }

        $data = $response->json();
        
        // El formato es: [ [headers...], [row1...], [row2...], ... ]
        if (empty($data) || count($data) < 2) {
            Log::warning("No hay datos de servicios para {$hostname}");
            return [];
        }
        
        $headers = $data[0]; // Primera fila son los headers
        $rows = array_slice($data, 1); // Resto son datos
        
        // Convertir a array asociativo
        $servicios = [];
        foreach ($rows as $row) {
            $servicio = [];
            foreach ($headers as $index => $header) {
                $servicio[$header] = isset($row[$index]) ? $row[$index] : null;
            }
            $servicios[] = $servicio;
        }
        
        return $servicios;
    }

    /**
     * Parsear datos de impresora desde CheckMK
     */
    private function parsearDatosImpresora($host, $servicios, $timestamp)
    {
        $hostname = $host['id'] ?? 'unknown';
        $extensions = $host['extensions'] ?? [];
        
        $datos = [
            'hostname' => $hostname,
            'display_name' => $extensions['alias'] ?? $hostname,
            'ip_address' => $extensions['attributes']['ipaddress'] ?? null,
            'sync_timestamp' => $timestamp,
            'estado' => 'online',
            'datos_adicionales' => [],
        ];

    $estadoScore = 0; // 0 online, 1 warning, 2 error

        // Parsear servicios para extraer información
        foreach ($servicios as $servicio) {
            $serviceName = strtolower($servicio['service_description'] ?? '');
            $serviceState = $servicio['service_state'] ?? 'OK';
            $serviceOutput = $servicio['svc_plugin_output'] ?? '';
            
            Log::debug("Servicio: {$serviceName}, Estado: {$serviceState}, Output: " . substr($serviceOutput, 0, 100));
            
            // Mapear severidad, ignorando meta-servicios y atenuando suministros
            $name = $serviceName;
            $stateStr = is_numeric($serviceState) ? (['OK','WARN','CRIT','UNKNOWN'][(int)$serviceState] ?? 'UNKNOWN') : strtoupper($serviceState);

            // Meta-servicios que no deben afectar al estado global
            $isMeta = str_contains($name, 'check_mk') || str_contains($name, 'discovery') || str_contains($name, 'inventory');
            if ($isMeta) {
                // no afecta a $estadoScore
            } else {
                $isSupply = (
                    str_contains($name, 'supply') || str_contains($name, 'toner') || str_contains($name, 'ink') || str_contains($name, 'cartridge') ||
                    str_contains($name, 'drum') || str_contains($name, 'fuser') || str_contains($name, 'imaging') || str_contains($name, 'maintenance') ||
                    str_contains($name, 'belt') || str_contains($name, 'transfer') || str_contains($name, 'waste')
                );

                // Servicios de conectividad/estado que sí pueden marcar error
                $isConnectivity = (
                    str_contains($name, 'snmp') || str_contains($name, 'device') || str_contains($name, 'host') || str_contains($name, 'ping') || str_contains($name, 'system')
                );

                $sev = 0;
                if ($stateStr === 'OK') {
                    $sev = 0;
                } elseif ($stateStr === 'WARN') {
                    $sev = $isSupply && !$isConnectivity ? 1 : 1; // WARN
                } elseif ($stateStr === 'CRIT') {
                    // Suministros en CRIT => evaluar porcentaje; conectividad => error
                    if ($isSupply && !$isConnectivity) {
                        $pct = $this->extraerPorcentajeGenerico($serviceOutput);
                        if ($pct !== null) {
                            // Solo avisar si por debajo de umbral bajo
                            $sev = ($pct <= 5) ? 1 : 0; // <=5% warning, >5% ok
                        } else {
                            // Sin porcentaje, degradar a warning
                            $sev = 1;
                        }
                    } else {
                        $sev = 2;
                    }
                } else {
                    $sev = max($estadoScore, 0);
                }

                $estadoScore = max($estadoScore, $sev);
            }

            // Parsear información específica de impresoras
            // Toners/Cartuchos (ahora busca "supply" y "cartridge")
            if (str_contains($serviceName, 'supply') || str_contains($serviceName, 'toner') || str_contains($serviceName, 'ink') || str_contains($serviceName, 'cartridge')) {
                $this->extraerNivelToner($serviceName, $serviceOutput, $datos);
            }
            
            // Contadores de páginas
            if (str_contains($serviceName, 'pages') || str_contains($serviceName, 'counter') || str_contains($serviceName, 'páginas')) {
                $this->extraerContadorPaginas($serviceName, $serviceOutput, $datos);
            }
            
            // Otros consumibles
            if (str_contains($serviceName, 'drum') || str_contains($serviceName, 'fuser') || str_contains($serviceName, 'belt') || str_contains($serviceName, 'imaging') || str_contains($serviceName, 'maintenance') || str_contains($serviceName, 'transfer') || str_contains($serviceName, 'waste')) {
                $this->extraerOtrosConsumibles($serviceName, $serviceOutput, $datos);
            }
            
            // Información del dispositivo
            if (str_contains($serviceName, 'device info') || str_contains($serviceName, 'system')) {
                $this->extraerInfoDispositivo($serviceOutput, $datos);
            }

            // Uptime
            if (str_contains($serviceName, 'uptime')) {
                $this->extraerUptime($serviceOutput, $datos);
            }
        }
        
    // Aplicar estado final según score
    $datos['estado'] = $estadoScore === 2 ? 'error' : ($estadoScore === 1 ? 'warning' : 'online');

    Log::info("Datos finales para {$hostname}: estado={$datos['estado']}, Cyan=" . ($datos['toner_cyan'] ?? 'null') . ", Magenta=" . ($datos['toner_magenta'] ?? 'null') . ", Yellow=" . ($datos['toner_yellow'] ?? 'null') . ", Black=" . ($datos['toner_black'] ?? 'null') . ", Pages Total=" . ($datos['paginas_total'] ?? 'null') . ", Pages B/N=" . ($datos['paginas_bn'] ?? 'null') . ", Pages Color=" . ($datos['paginas_color'] ?? 'null'));

        return $datos;
    }

    /**
     * Extrae el primer porcentaje encontrado en un texto, devuelve int o null
     */
    private function extraerPorcentajeGenerico($output)
    {
        if (!is_string($output) || $output === '') return null;
        if (preg_match('/(\d+)(?:\.\d+)?\s*%/', $output, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Extraer nivel de toner del output del servicio
     */
    private function extraerNivelToner($serviceName, $output, &$datos)
    {
        // Formato CheckMK: "Supply level remaining: 100.00%, Supply: 2000 of max. 2000 impressions"
        // También manejar otros formatos: "Cyan: 75%", "Black toner: 45%"
        $porcentaje = null;
        
        // Primero intentar con "Supply level remaining:"
        if (preg_match('/supply level remaining:\s*(\d+(?:\.\d+)?)%/i', $output, $matches)) {
            $porcentaje = (int) round((float) $matches[1]);
        }
        // Si no, buscar cualquier porcentaje
        elseif (preg_match('/(\d+)%/', $output, $matches)) {
            $porcentaje = (int) $matches[1];
        }
        
        if ($porcentaje !== null) {
            // Detectar el color basado en el nombre del servicio
            if (str_contains($serviceName, 'cyan') || str_contains($output, 'cyan') || str_contains($output, 'cian')) {
                $datos['toner_cyan'] = $porcentaje;
            } elseif (str_contains($serviceName, 'magenta') || str_contains($output, 'magenta')) {
                $datos['toner_magenta'] = $porcentaje;
            } elseif (str_contains($serviceName, 'yellow') || str_contains($output, 'yellow') || str_contains($output, 'amarillo')) {
                $datos['toner_yellow'] = $porcentaje;
            } elseif (str_contains($serviceName, 'black') || str_contains($output, 'black') || str_contains($output, 'negro')) {
                $datos['toner_black'] = $porcentaje;
            }
        }
    }

    /**
     * Extraer contador de páginas
     */
    private function extraerContadorPaginas($serviceName, $output, &$datos)
    {
        // Log para debug
        Log::info("extraerContadorPaginas - Service: {$serviceName}, Output: {$output}");
        
        // Formato CheckMK puede incluir: "b/w: 3518, color: 1282, total prints: 4800"
        // Primero buscar si hay desglose de b/w y color
        $hasBW = false;
        $hasColor = false;
        
        // Buscar b/w (blanco y negro)
        if (preg_match('/b\/w:\s*(\d+)/i', $output, $matches)) {
            $datos['paginas_bn'] = (int) $matches[1];
            $hasBW = true;
            Log::info("Encontrado B/W: " . $datos['paginas_bn']);
        }
        
        // Buscar color
        if (preg_match('/color:\s*(\d+)/i', $output, $matches)) {
            $datos['paginas_color'] = (int) $matches[1];
            $hasColor = true;
            Log::info("Encontrado Color: " . $datos['paginas_color']);
        }
        
        // Buscar total prints
        if (preg_match('/total prints:\s*(\d+)/i', $output, $matches)) {
            $datos['paginas_total'] = (int) $matches[1];
            Log::info("Encontrado Total: " . $datos['paginas_total']);
        } elseif (preg_match('/(\d+)/', $output, $matches)) {
            // Si no encontramos patrones específicos, intentar extraer número genérico
            $contador = (int) $matches[1];
            
            // Solo asignar si no tenemos ya datos de b/w o color
            if (!$hasBW && !$hasColor) {
                if (str_contains($serviceName, 'color') || str_contains($output, 'color')) {
                    $datos['paginas_color'] = $contador;
                    Log::info("Asignado a Color (genérico): " . $contador);
                } elseif (str_contains($serviceName, 'black') || str_contains($serviceName, 'mono') || str_contains($output, 'black')) {
                    $datos['paginas_bn'] = $contador;
                    Log::info("Asignado a B/N (genérico): " . $contador);
                } else {
                    $datos['paginas_total'] = $contador;
                    Log::info("Asignado a Total (genérico): " . $contador);
                }
            }
        }
    }

    /**
     * Extraer otros consumibles (drum, fuser, etc)
     */
    private function extraerOtrosConsumibles($serviceName, $output, &$datos)
    {
        if (preg_match('/(\d+)%/', $output, $matches)) {
            $porcentaje = (int) $matches[1];
            
            if (str_contains($serviceName, 'drum')) {
                $datos['drum_unit'] = $porcentaje;
            } elseif (str_contains($serviceName, 'fuser')) {
                $datos['fuser'] = $porcentaje;
            } elseif (str_contains($serviceName, 'belt') || str_contains($serviceName, 'transfer')) {
                $datos['transfer_belt'] = $porcentaje;
            } elseif (str_contains($serviceName, 'waste')) {
                $datos['waste_toner'] = $porcentaje;
            }
        }
    }

    /**
     * Extraer información del dispositivo (marca, modelo, serie)
     */
    private function extraerInfoDispositivo($output, &$datos)
    {
        // Intentar extraer marca y modelo del output
        // Formato típico: "HP LaserJet Pro M404dn, Serial: ABC123"
        if (preg_match('/([A-Z][a-z]+)\s+(.+?)(?:,|\.|$)/i', $output, $matches)) {
            $datos['marca'] = $matches[1];
            $datos['modelo'] = trim($matches[2]);
        }
        
        if (preg_match('/[Ss]erial[:\s]+([A-Z0-9]+)/i', $output, $matches)) {
            $datos['numero_serie'] = $matches[1];
        }
    }

    /**
     * Extraer uptime
     */
    private function extraerUptime($output, &$datos)
    {
        // Buscar días en el output (ej: "45 days", "3d 5h")
        if (preg_match('/(\d+)\s*d(?:ays?)?/i', $output, $matches)) {
            $datos['uptime_dias'] = (int) $matches[1];
        }
    }
}
