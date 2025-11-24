<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpresoraCheckmkSync extends Model
{
    use HasFactory;

    protected $table = 'impresoras_checkmk_sync';

    protected $fillable = [
        'departamento_id',
        'hostname',
        'display_name',
        'ip_address',
        'marca',
        'modelo',
        'numero_serie',
        'toner_cyan',
        'toner_magenta',
        'toner_yellow',
        'toner_black',
        'drum_unit',
        'fuser',
        'transfer_belt',
        'waste_toner',
        'paginas_total',
        'paginas_color',
        'paginas_bn',
        'estado',
        'uptime_dias',
        'mensajes_error',
        'datos_adicionales',
        'sync_timestamp',
    ];

    protected $casts = [
        'toner_cyan' => 'integer',
        'toner_magenta' => 'integer',
        'toner_yellow' => 'integer',
        'toner_black' => 'integer',
        'drum_unit' => 'integer',
        'fuser' => 'integer',
        'transfer_belt' => 'integer',
        'waste_toner' => 'integer',
        'paginas_total' => 'integer',
        'paginas_color' => 'integer',
        'paginas_bn' => 'integer',
        'uptime_dias' => 'integer',
        'datos_adicionales' => 'array',
        'sync_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Departamento
     */
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Obtener el último registro de sincronización para un hostname
     */
    public static function getLatestByHostname($hostname)
    {
        return self::where('hostname', $hostname)
            ->orderBy('sync_timestamp', 'desc')
            ->first();
    }

    /**
     * Obtener todos los registros de un hostname ordenados por fecha
     */
    public static function getHistoryByHostname($hostname, $limit = null)
    {
        $query = self::where('hostname', $hostname)
            ->orderBy('sync_timestamp', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Obtener estadísticas de consumo entre dos fechas
     */
    public static function getConsumptionStats($hostname, $startDate, $endDate)
    {
        return self::where('hostname', $hostname)
            ->whereBetween('sync_timestamp', [$startDate, $endDate])
            ->orderBy('sync_timestamp', 'asc')
            ->get();
    }

    /**
     * Obtener el listado de todas las impresoras únicas (últimos registros)
     * SOLO impresoras de la última sincronización (activas en CheckMK)
     */
    public static function getLatestForAllPrinters()
    {
        // Obtener el timestamp de la última sincronización global
        $lastSyncTimestamp = self::max('sync_timestamp');
        
        if (!$lastSyncTimestamp) {
            return collect([]);
        }
        
        // Devolver SOLO las impresoras que fueron sincronizadas en esa última ejecución
        return self::where('sync_timestamp', $lastSyncTimestamp)
            ->orderBy('hostname')
            ->get();
    }
}
