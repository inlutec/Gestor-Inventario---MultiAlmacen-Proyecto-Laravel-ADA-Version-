<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CheckmkConfig;

class CheckMKService
{
    private $checkmkUrl;
    private $checkmkUser;
    private $checkmkPassword;
    private $checkmkSite;

    public function __construct()
    {
        $config = CheckmkConfig::first();
        
        if (!$config) {
            throw new \Exception('No se encontró configuración de CheckMK');
        }

        $this->checkmkUrl = $config->api_url;
        $this->checkmkUser = $config->api_user;
        $this->checkmkPassword = $config->api_password;
        $this->checkmkSite = $config->site;
    }

    /**
     * Crear un host en CheckMK
     * 
     * @param string $hostname Nombre del host
     * @param string $ipAddress Dirección IP del host
     * @param array $attributes Atributos adicionales del host
     * @return array Respuesta de la API
     */
    public function createHost($hostname, $ipAddress, $attributes = [])
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/host_config/collections/all";

        $defaultAttributes = [
            'ipaddress' => $ipAddress,
            'tag_agent' => 'no-agent',
            'tag_snmp_ds' => 'snmp-v2',
            'snmp_community' => 'public',
        ];

        $payload = [
            'host_name' => $hostname,
            'folder' => '/',
            'attributes' => array_merge($defaultAttributes, $attributes),
        ];

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Error al crear host en CheckMK', [
                    'hostname' => $hostname,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Error al crear host: ' . $response->body());
            }

            Log::info('Host creado en CheckMK', [
                'hostname' => $hostname,
                'ip' => $ipAddress,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al crear host en CheckMK', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar un host existente en CheckMK
     * 
     * @param string $hostname Nombre del host
     * @param array $attributes Atributos a actualizar
     * @return array Respuesta de la API
     */
    public function updateHost($hostname, $attributes)
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/objects/host_config/{$hostname}";

        $payload = [
            'update_attributes' => $attributes,
        ];

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'If-Match' => '*',
                ])
                ->put($url, $payload);

            if (!$response->successful()) {
                Log::error('Error al actualizar host en CheckMK', [
                    'hostname' => $hostname,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Error al actualizar host: ' . $response->body());
            }

            Log::info('Host actualizado en CheckMK', ['hostname' => $hostname]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al actualizar host en CheckMK', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verificar si un host existe en CheckMK
     * 
     * @param string $hostname Nombre del host
     * @return bool True si existe, False si no existe
     */
    public function hostExists($hostname)
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/objects/host_config/{$hostname}";

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ejecutar autodescubrimiento de servicios para un host
     * 
     * @param string $hostname Nombre del host
     * @param string $mode Modo de descubrimiento (new, remove, fix_all, refresh)
     * @return array Respuesta de la API
     */
    public function discoverServices($hostname, $mode = 'new')
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/service_discovery_run/actions/start/invoke";

        $payload = [
            'host_name' => $hostname,
            'mode' => $mode,
        ];

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Error al descubrir servicios en CheckMK', [
                    'hostname' => $hostname,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Error al descubrir servicios: ' . $response->body());
            }

            Log::info('Autodescubrimiento ejecutado en CheckMK', [
                'hostname' => $hostname,
                'mode' => $mode,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al descubrir servicios en CheckMK', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activar cambios en CheckMK
     * 
     * @param bool $force Forzar activación incluso si hay advertencias
     * @return array Respuesta de la API
     */
    public function activateChanges($force = false)
    {
        try {
            // Primero obtener el ETag de los cambios pendientes
            $changesUrl = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/activation_run/collections/pending_changes";
            
            $changesResponse = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($changesUrl);

            if (!$changesResponse->successful()) {
                Log::error('Error al obtener cambios pendientes en CheckMK', [
                    'status' => $changesResponse->status(),
                    'response' => $changesResponse->body(),
                ]);
                throw new \Exception('Error al obtener cambios pendientes: ' . $changesResponse->body());
            }

            $etag = $changesResponse->header('ETag');
            
            if (!$etag) {
                throw new \Exception('No se pudo obtener el ETag de los cambios pendientes');
            }

            // Ahora activar cambios con el ETag
            $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/domain-types/activation_run/actions/activate-changes/invoke";

            $payload = [
                'sites' => [$this->checkmkSite],
                'force_foreign_changes' => $force,
            ];

            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'If-Match' => $etag,
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Error al activar cambios en CheckMK', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Error al activar cambios: ' . $response->body());
            }

            Log::info('Cambios activados en CheckMK');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al activar cambios en CheckMK', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar un host de CheckMK
     * 
     * @param string $hostname Nombre del host
     * @return array Respuesta de la API
     */
    public function deleteHost($hostname)
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/objects/host_config/{$hostname}";

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($url);

            if (!$response->successful()) {
                Log::error('Error al eliminar host en CheckMK', [
                    'hostname' => $hostname,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception('Error al eliminar host: ' . $response->body());
            }

            Log::info('Host eliminado en CheckMK', ['hostname' => $hostname]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al eliminar host en CheckMK', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtener información de un host
     * 
     * @param string $hostname Nombre del host
     * @return array Información del host
     */
    public function getHost($hostname)
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/objects/host_config/{$hostname}";

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Error al obtener información del host: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Excepción al obtener host en CheckMK', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear host con autodescubrimiento completo
     * Este método combina la creación, descubrimiento y activación
     * 
     * @param string $hostname Nombre del host
     * @param string $ipAddress Dirección IP
     * @param array $attributes Atributos adicionales
     * @return array Resultado del proceso completo
     */
    public function createHostWithDiscovery($hostname, $ipAddress, $attributes = [])
    {
        $result = [
            'hostname' => $hostname,
            'success' => false,
            'steps' => [],
        ];

        try {
            // Paso 1: Crear el host
            $createResult = $this->createHost($hostname, $ipAddress, $attributes);
            $result['steps']['create'] = [
                'success' => true,
                'data' => $createResult,
            ];

            // Paso 2: Ejecutar autodescubrimiento
            $discoverResult = $this->discoverServices($hostname, 'new');
            $result['steps']['discover'] = [
                'success' => true,
                'data' => $discoverResult,
            ];

            // Paso 3: Activar cambios
            $activateResult = $this->activateChanges();
            $result['steps']['activate'] = [
                'success' => true,
                'data' => $activateResult,
            ];

            $result['success'] = true;
            
            Log::info('Host creado con autodescubrimiento completo', [
                'hostname' => $hostname,
                'ip' => $ipAddress,
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            
            Log::error('Error en creación completa de host', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
                'steps_completed' => array_keys($result['steps']),
            ]);
        }

        return $result;
    }

    /**
     * Obtener servicios descubiertos (pendientes de activación) para un host
     * 
     * @param string $hostname Nombre del host
     * @return array Lista de servicios descubiertos
     */
    public function getDiscoveredServices($hostname)
    {
        $url = "{$this->checkmkUrl}/{$this->checkmkSite}/check_mk/api/1.0/objects/host/{$hostname}/collections/services";

        try {
            $response = Http::withBasicAuth($this->checkmkUser, $this->checkmkPassword)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('No se pudieron obtener servicios descubiertos', [
                    'hostname' => $hostname,
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();
            return $data['value'] ?? [];
        } catch (\Exception $e) {
            Log::error('Excepción al obtener servicios descubiertos', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Crear host con autodescubrimiento secuencial (uno por uno)
     * Proporciona feedback detallado para cada paso
     * 
     * @param string $hostname Nombre del host
     * @param string $ipAddress Dirección IP
     * @param array $attributes Atributos adicionales
     * @param callable|null $callback Función callback para reportar progreso
     * @return array Resultado del proceso completo
     */
    public function createHostWithDiscoverySequential($hostname, $ipAddress, $attributes = [], $callback = null)
    {
        $result = [
            'hostname' => $hostname,
            'success' => false,
            'services_discovered' => 0,
            'services' => [],
            'steps' => [],
        ];

        try {
            // Verificar si el host ya existe
            if ($callback) $callback('info', "Verificando si el host ya existe...");
            
            if ($this->hostExists($hostname)) {
                if ($callback) $callback('warning', "Host '$hostname' ya existe en Check_MK. Omitiendo creación y autodescubrimiento.");
                $result['already_exists'] = true;
                $result['success'] = true;
                return $result;
            }

            // Paso 1: Crear el host
            if ($callback) $callback('info', "Creando host en Check_MK...");
            $createResult = $this->createHost($hostname, $ipAddress, $attributes);
            $result['steps']['create'] = [
                'success' => true,
                'data' => $createResult,
            ];
            if ($callback) $callback('success', "✓ Host creado exitosamente");

            // Pequeña pausa para que Check_MK procese
            usleep(500000); // 0.5 segundos

            // Paso 2: Ejecutar autodescubrimiento
            if ($callback) $callback('info', "Iniciando autodescubrimiento SNMP de servicios...");
            $discoverResult = $this->discoverServices($hostname, 'new');
            $result['steps']['discover'] = [
                'success' => true,
                'data' => $discoverResult,
            ];
            
            if ($callback) $callback('info', "⏳ Autodescubrimiento en proceso (puede tardar 10-30 segundos)...");
            
            // Esperar 15 segundos para que CheckMK complete el autodescubrimiento SNMP
            // Este tiempo permite consultar todas las MIBs de impresoras (toners, contadores, etc)
            sleep(15);
            
            if ($callback) $callback('success', "✓ Autodescubrimiento completado");
            
            // No intentamos obtener los servicios aquí - CheckMK los mostrará tras activar cambios
            $result['services_discovered'] = 'pending';
            $result['services'] = [];
            
            if ($callback) $callback('info', "Los servicios aparecerán en la interfaz tras activar los cambios");

            // Paso 3: Activar cambios
            if ($callback) $callback('info', "Activando cambios en Check_MK...");
            $activateResult = $this->activateChanges();
            $result['steps']['activate'] = [
                'success' => true,
                'data' => $activateResult,
            ];
            if ($callback) $callback('success', "✓ Cambios activados correctamente");

            $result['success'] = true;
            
            Log::info('Host creado con autodescubrimiento secuencial', [
                'hostname' => $hostname,
                'ip' => $ipAddress,
                'services_discovered' => 'N/A (procesándose en background)',
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            
            if ($callback) $callback('error', "✗ Error: " . $e->getMessage());
            
            Log::error('Error en creación secuencial de host', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
                'steps_completed' => array_keys($result['steps']),
            ]);
        }

        return $result;
    }
}
