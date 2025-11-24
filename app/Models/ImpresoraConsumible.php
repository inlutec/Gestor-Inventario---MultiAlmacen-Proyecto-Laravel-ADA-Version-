<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ImpresoraConsumible extends Model
{
    use HasFactory;

    protected $table = 'impresoras_consumibles';

    protected $fillable = [
        'sync_id', 'hostname', 'service_name', 'key', 'category', 'percent', 'state', 'raw_output', 'sync_timestamp'
    ];

    public $timestamps = true;

    /**
     * Obtener los últimos consumibles por hostname y clave de servicio
     * Devuelve array agrupado por hostname => [ {label, percent, state, key} ]
     * EXCLUYE los cartuchos de colores principales (cyan, magenta, yellow, black)
     * ya que estos se muestran en el gráfico de barras
     */
    public static function getLatestByHostnames(array $hostnames): array
    {
        if (empty($hostnames)) return [];

        // Primero obtener el último sync_timestamp por hostname
        $lastSyncs = DB::table('impresoras_consumibles')
            ->select('hostname', DB::raw('MAX(sync_timestamp) as last_sync'))
            ->whereIn('hostname', $hostnames)
            ->groupBy('hostname')
            ->get()
            ->keyBy('hostname');

        if ($lastSyncs->isEmpty()) {
            return [];
        }

        // Obtener todos los consumibles de esas últimas sincronizaciones
        $rows = DB::table('impresoras_consumibles')
            ->whereIn('hostname', $hostnames)
            ->where(function($q) use ($lastSyncs) {
                foreach ($lastSyncs as $hostname => $sync) {
                    $q->orWhere(function($qq) use ($hostname, $sync) {
                        $qq->where('hostname', $hostname)
                           ->where('sync_timestamp', $sync->last_sync);
                    });
                }
            })
            ->orderBy('hostname')
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            // Filtrar cartuchos/toners de colores principales (ya están en barras)
            $serviceName = strtolower($r->service_name);
            $esColorPrincipal = (
                (str_contains($serviceName, 'cyan') || str_contains($serviceName, 'cian')) ||
                (str_contains($serviceName, 'magenta')) ||
                (str_contains($serviceName, 'yellow') || str_contains($serviceName, 'amarillo')) ||
                (str_contains($serviceName, 'black') || str_contains($serviceName, 'negro'))
            ) && ($r->category === 'cartridge' || $r->category === 'toner');
            
            // Solo incluir si NO es un color principal
            if (!$esColorPrincipal) {
                $grouped[$r->hostname] = $grouped[$r->hostname] ?? [];
                $grouped[$r->hostname][] = [
                    'label' => $r->service_name,
                    'percent' => $r->percent,
                    'state' => $r->state,
                    'key' => $r->key,
                    'category' => $r->category,
                ];
            }
        }
        return $grouped;
    }
}
