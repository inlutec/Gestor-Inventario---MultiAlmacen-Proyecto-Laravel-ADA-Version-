<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ImpresoraCheckmkSync;
use App\Models\CheckmkConfig;
use App\Models\CheckmkSyncLog;
use Carbon\Carbon;

class SincronizarCheckmk extends Command
{
    protected $signature = 'checkmk:sync';
    protected $description = 'Sincronizar datos de impresoras desde CheckMK';

    private $checkmkUrl;
    private $checkmkUser;
    private $checkmkPassword;
    private $checkmkSite;

    public function handle()
    {
        $startTime = microtime(true);
        $this->info('🔄 Iniciando sincronización con CheckMK...');
        $this->newLine();

        try {
            // Cargar configuración desde la BD
            $config = CheckmkConfig::first();
            if (!$config) {
                $this->error('❌ No se encontró configuración de CheckMK en la base de datos');
                CheckmkSyncLog::createLog('error', 0, 0, 0, 'No se encontró configuración', null, 0);
                return 1;
            }

            $this->checkmkUrl = $config->api_url;
            $this->checkmkUser = $config->api_user;
            $this->checkmkPassword = $config->api_password;
            $this->checkmkSite = $config->site;

            // Obtener hosts desde CheckMK
            $this->line('📡 Conectando con CheckMK API...');
            $hosts = $this->obtenerHostsCheckmk();

            if (!$hosts || empty($hosts)) {
                $this->error('❌ No se pudieron obtener datos de CheckMK o no hay hosts configurados');
                $duration = round(microtime(true) - $startTime, 2);
                CheckmkSyncLog::createLog('error', 0, 0, 0, 'No se pudieron obtener hosts', null, $duration);
                return 1;
            }

            $this->info("✓ Encontrados " . count($hosts) . " host(s)");
            $this->newLine();

            $sincronizados = 0;
            $errores = 0;
            $timestamp = Carbon::now();
            $errorDetails = [];

            $progressBar = $this->output->createProgressBar(count($hosts));
            $progressBar->start();

            foreach ($hosts as $host) {
                try {
                    $hostname = $host['id'] ?? 'unknown';
                    
                    // Obtener servicios del host
                    $servicios = $this->obtenerServiciosHost($hostname);
                    
                    // Parsear y guardar datos
                    [$datosImpresora, $consumibles] = $this->parsearDatosImpresora($host, $servicios, $timestamp);

                    // Conservar datos_adicionales de la última sincronización (foto, overrides, custom)
                    try {
                        $prev = ImpresoraCheckmkSync::getLatestByHostname($datosImpresora['hostname']);
                        if ($prev && is_array($prev->datos_adicionales)) {
                            $datosImpresora['datos_adicionales'] = $prev->datos_adicionales;
                        }
                    } catch (\Throwable $t) { /* ignore */ }

                    $syncRow = ImpresoraCheckmkSync::create($datosImpresora);

                    // Guardar consumibles dinámicos si existen
                    if (!empty($consumibles)) {
                        foreach ($consumibles as &$c) { $c['sync_id'] = $syncRow->id; }
                        \DB::table('impresoras_consumibles')->insert($consumibles);
                    }
                    
                    $sincronizados++;
                } catch (\Exception $e) {
                    $errores++;
                    $errorDetails[] = [
                        'hostname' => $hostname ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    $this->newLine();
                    $this->warn("⚠️  Error en host {$hostname}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Calcular duración
            $duration = round(microtime(true) - $startTime, 2);

            // Determinar status
            $status = $errores === 0 ? 'success' : ($sincronizados > 0 ? 'partial' : 'error');

            // Actualizar última sincronización en config
            $config->last_sync = $timestamp;
            $config->save();

            // Registrar log
            CheckmkSyncLog::createLog(
                $status,
                count($hosts),
                $sincronizados,
                $errores,
                $errores > 0 ? "Sincronización completada con {$errores} errores" : "Sincronización exitosa",
                $errores > 0 ? ['errors' => $errorDetails] : null,
                $duration
            );

            $this->info("✅ Sincronización completada!");
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Hosts procesados', count($hosts)],
                    ['Sincronizados correctamente', $sincronizados],
                    ['Errores', $errores],
                    ['Duración (seg)', $duration],
                    ['Timestamp', $timestamp->toDateTimeString()],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            $this->error('❌ Error en sincronización: ' . $e->getMessage());
            
            CheckmkSyncLog::createLog(
                'error',
                0,
                0,
                0,
                'Error crítico en sincronización: ' . $e->getMessage(),
                ['exception' => $e->getTraceAsString()],
                $duration
            );
            
            return 1;
        }
    }

    private function obtenerHostsCheckmk()
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/host_config/collections/all";
        
        $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception('No se pudo conectar con CheckMK API: ' . $response->status());
        }

        $data = $response->json();
        return $data['value'] ?? [];
    }

    private function obtenerServiciosHost($hostname)
    {
        // Usar el formato JSON export de vistas que incluye plugin_output
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/view.py?view_name=allservices&output_format=json_export&host=" . urlencode($hostname);
        
        $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
            ->get($url);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        
        // El formato es: [ [headers...], [row1...], [row2...], ... ]
        if (empty($data) || count($data) < 2) {
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

        $consumibles = [];
        $estadoScore = 0; // 0 online, 1 warning, 2 error
        
        foreach ($servicios as $servicio) {
            $serviceDescOriginal = $servicio['service_description'] ?? '';
            $serviceName = strtolower($serviceDescOriginal);
            $serviceState = $servicio['service_state'] ?? 'OK';
            // Normalizar el estado: algunos exports pueden devolver códigos numéricos (0..3)
            // o valores en otra forma. Mapear numéricos a etiquetas y limpiar el string.
            $serviceStateStr = strtoupper(trim((string) $serviceState));
            if (is_numeric($serviceStateStr)) {
                // CheckMK numeric mapping: 0=OK,1=WARN,2=CRIT,3=UNKNOWN
                switch ($serviceStateStr) {
                    case '0': $serviceStateStr = 'OK'; break;
                    case '1': $serviceStateStr = 'WARN'; break;
                    case '2': $serviceStateStr = 'CRIT'; break;
                    case '3': $serviceStateStr = 'UNKNOWN'; break;
                    default: $serviceStateStr = 'UNKNOWN'; break;
                }
            }
            // Usar la versión normalizada en el resto del parseo
            $serviceState = $serviceStateStr;
            $serviceOutput = $servicio['svc_plugin_output'] ?? '';
            
            // Ignorar servicios de monitoreo CheckMK internos (no afectan a estado)
            $isMeta = str_contains($serviceName, 'check_mk') || str_contains($serviceName, 'discovery') || str_contains($serviceName, 'inventory');
            if (!$isMeta) {
                $isSupply = (
                    str_contains($serviceName, 'supply') || str_contains($serviceName, 'toner') || str_contains($serviceName, 'ink') || str_contains($serviceName, 'cartridge') ||
                    str_contains($serviceName, 'drum') || str_contains($serviceName, 'fuser') || str_contains($serviceName, 'imaging') || str_contains($serviceName, 'maintenance') ||
                    str_contains($serviceName, 'belt') || str_contains($serviceName, 'transfer') || str_contains($serviceName, 'waste')
                );
                $isConnectivity = (
                    str_contains($serviceName, 'snmp') || str_contains($serviceName, 'device') || str_contains($serviceName, 'host') || str_contains($serviceName, 'ping') || str_contains($serviceName, 'system')
                );

                $sev = 0;
                if ($serviceState === 'OK') {
                    $sev = 0;
                } elseif ($serviceState === 'WARN') {
                    $sev = 1;
                } elseif ($serviceState === 'CRIT') {
                    if ($isSupply && !$isConnectivity) {
                        $pct = $this->extraerPorcentaje($serviceOutput);
                        if (!is_null($pct)) {
                            $sev = ($pct <= 5) ? 1 : 0; // <=5% warning
                        } else {
                            $sev = 1;
                        }
                    } else {
                        $sev = 2;
                    }
                }
                $estadoScore = max($estadoScore, $sev);
            }

            // Parsear información específica (ahora incluye "supply")
            // Primero intentar extraer otros consumibles (drum, fuser, etc.)
            if (str_contains($serviceName, 'drum') || str_contains($serviceName, 'fuser') || 
                str_contains($serviceName, 'imaging') || str_contains($serviceName, 'maintenance') || 
                str_contains($serviceName, 'transfer') || str_contains($serviceName, 'waste') ||
                str_contains($serviceName, 'belt')) {
                $this->extraerOtrosConsumibles($serviceName, $serviceOutput, $datos);
                $pct = $this->extraerPorcentaje($serviceOutput);
                if (!is_null($pct)) {
                    $consumibles[] = [
                        'hostname' => $hostname,
                        'service_name' => $serviceDescOriginal,
                        'key' => $this->slugify($serviceDescOriginal),
                        'category' => $this->categorizarServicio($serviceName),
                        'percent' => $pct,
                        'state' => $serviceState,
                        'raw_output' => $serviceOutput,
                        'sync_timestamp' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
            // Luego extraer toners si aplica
            elseif (str_contains($serviceName, 'supply') || str_contains($serviceName, 'toner') || 
                    str_contains($serviceName, 'ink') || str_contains($serviceName, 'cartridge')) {
                $this->extraerNivelToner($serviceName, $serviceOutput, $datos, $serviceDescOriginal);
                $pct = $this->extraerPorcentaje($serviceOutput);
                if (!is_null($pct)) {
                    // Usar el nombre COMPLETO del servicio como etiqueta descriptiva
                    $label = $serviceDescOriginal;
                    $category = $this->categorizarConsumible($serviceName, $serviceDescOriginal);
                    $consumibles[] = [
                        'hostname' => $hostname,
                        'service_name' => $label,
                        'key' => $this->slugify($label),
                        'category' => $category,
                        'percent' => $pct,
                        'state' => $serviceState,
                        'raw_output' => $serviceOutput,
                        'sync_timestamp' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
            
            if (str_contains($serviceName, 'pages') || str_contains($serviceName, 'counter')) {
                $this->extraerContadorPaginas($serviceName, $serviceOutput, $datos);
            }
            
            if (str_contains($serviceName, 'uptime')) {
                $this->extraerUptime($serviceOutput, $datos);
            }
        }

    // Estado final según score
    $datos['estado'] = $estadoScore === 2 ? 'error' : ($estadoScore === 1 ? 'warning' : 'online');

    Log::info("Datos finales para {$hostname}: estado={$datos['estado']}, Pages Total=" . ($datos['paginas_total'] ?? 'null') . ", Pages B/N=" . ($datos['paginas_bn'] ?? 'null') . ", Pages Color=" . ($datos['paginas_color'] ?? 'null'));

    return [$datos, $consumibles];
    }

    private function extraerNivelToner($serviceName, $output, &$datos, $serviceDescOriginal = '')
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
            // Buscar en nombre original del servicio Y en el output
            $fullText = strtolower($serviceName . ' ' . $output . ' ' . $serviceDescOriginal);
            
            // Detectar colores en español E inglés
            if (str_contains($fullText, 'cyan') || str_contains($fullText, 'cian')) {
                $datos['toner_cyan'] = $porcentaje;
            } elseif (str_contains($fullText, 'magenta')) {
                $datos['toner_magenta'] = $porcentaje;
            } elseif (str_contains($fullText, 'yellow') || str_contains($fullText, 'amarillo')) {
                $datos['toner_yellow'] = $porcentaje;
            } elseif (str_contains($fullText, 'black') || str_contains($fullText, 'negro')) {
                $datos['toner_black'] = $porcentaje;
            }
        }
    }

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
                if (str_contains($serviceName, 'color')) {
                    $datos['paginas_color'] = $contador;
                    Log::info("Asignado a Color (genérico): " . $contador);
                } elseif (str_contains($serviceName, 'black') || str_contains($serviceName, 'mono')) {
                    $datos['paginas_bn'] = $contador;
                    Log::info("Asignado a B/N (genérico): " . $contador);
                } else {
                    $datos['paginas_total'] = $contador;
                    Log::info("Asignado a Total (genérico): " . $contador);
                }
            }
        }
    }

    private function extraerOtrosConsumibles($serviceName, $output, &$datos)
    {
        if (preg_match('/supply level remaining:\s*(\d+(?:\.\d+)?)%/i', $output, $matches)) {
            $porcentaje = (int) round((float) $matches[1]);
            
            if (str_contains($serviceName, 'drum') || str_contains($serviceName, 'imaging')) {
                $datos['drum_unit'] = $porcentaje;
            } elseif (str_contains($serviceName, 'fuser') || str_contains($serviceName, 'maintenance')) {
                $datos['fuser'] = $porcentaje;
            } elseif (str_contains($serviceName, 'belt') || str_contains($serviceName, 'transfer')) {
                $datos['transfer_belt'] = $porcentaje;
            } elseif (str_contains($serviceName, 'waste')) {
                $datos['waste_toner'] = $porcentaje;
            }
        } elseif (preg_match('/(\d+)%/', $output, $matches)) {
            $porcentaje = (int) $matches[1];
            
            if (str_contains($serviceName, 'drum')) {
                $datos['drum_unit'] = $porcentaje;
            } elseif (str_contains($serviceName, 'fuser')) {
                $datos['fuser'] = $porcentaje;
            }
        }
    }

    
    
    private function extraerUptime($output, &$datos)
    {
        // Buscar días en el output (ej: "45 days", "3d 5h")
        if (preg_match('/(\d+)\s*d(?:ays?)?/i', $output, $matches)) {
            $datos['uptime_dias'] = (int) $matches[1];
        }
    }

    private function extraerPorcentaje(string $output): ?int
    {
        if (preg_match('/supply level remaining:\s*(\d+(?:\.\d+)?)%/i', $output, $m)) {
            return (int) round((float) $m[1]);
        }
        if (preg_match('/(\d+)%/', $output, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function resolverLabelToner(string $serviceName, string $output): string
    {
        foreach (['cyan' => 'Cyan', 'magenta' => 'Magenta', 'yellow' => 'Yellow', 'black' => 'Black'] as $k => $label) {
            if (str_contains($serviceName, $k) || str_contains(strtolower($output), $k)) return $label;
        }
        return 'Toner';
    }

    private function slugify(string $text): string
    {
        $t = strtolower(trim($text));
        $t = preg_replace('/[^a-z0-9]+/','-',$t);
        return trim($t,'-');
    }

    private function categorizarServicio(string $serviceName): string
    {
        $map = [
            'drum' => 'drum', 'imaging' => 'imaging', 'fuser' => 'fuser', 'maintenance' => 'maintenance',
            'belt' => 'belt', 'transfer' => 'belt', 'waste' => 'waste', 'roller' => 'roller', 'kit' => 'kit', 'clean' => 'cleaner', 'head' => 'head', 'adf' => 'adf'
        ];
        foreach ($map as $k => $v) { if (str_contains($serviceName, $k)) return $v; }
        return 'consumable';
    }

    /**
     * Categorizar consumible basándose en el nombre del servicio completo
     */
    private function categorizarConsumible(string $serviceName, string $serviceDescOriginal): string
    {
        $fullText = strtolower($serviceName . ' ' . $serviceDescOriginal);
        
        // Orden de prioridad para categorización
        $categorias = [
            'cartucho' => 'cartridge',
            'cartridge' => 'cartridge',
            'toner' => 'toner',
            'ink' => 'ink',
            'drum' => 'drum',
            'imaging' => 'drum',
            'fuser' => 'fuser',
            'fusor' => 'fuser',
            'maintenance' => 'maintenance',
            'mantenimiento' => 'maintenance',
            'belt' => 'belt',
            'correa' => 'belt',
            'transfer' => 'transfer',
            'waste' => 'waste',
            'residuos' => 'waste',
            'contenedor' => 'container',
            'container' => 'container',
            'roller' => 'roller',
            'rodillo' => 'roller',
            'kit' => 'kit',
            'limpiador' => 'cleaner',
            'cleaner' => 'cleaner',
            'limpiar' => 'cleaner',
            'head' => 'head',
            'cabezal' => 'head',
            'adf' => 'adf',
            'alimentador' => 'feeder',
            'feeder' => 'feeder',
        ];
        
        foreach ($categorias as $keyword => $category) {
            if (str_contains($fullText, $keyword)) {
                return $category;
            }
        }
        
        return 'supply';
    }
}
