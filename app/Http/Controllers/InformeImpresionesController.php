<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ImpresoraCheckmkSync;
use Carbon\Carbon;

class InformeImpresionesController extends Controller
{
    /**
     * Obtener estadísticas de impresiones con filtros
     */
    public function obtenerEstadisticas(Request $request)
    {
        try {
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', Carbon::now()->format('Y-m-d'));
            $hostname = $request->input('hostname');
            $modelo = $request->input('modelo');
            $marca = $request->input('marca');
            $agrupacion = $request->input('agrupacion', 'dia'); // dia, semana, mes
            
            // Construir query base
            $query = ImpresoraCheckmkSync::whereBetween('sync_timestamp', [
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            
            // Aplicar filtros
            if ($hostname) {
                $query->where('hostname', 'LIKE', '%' . $hostname . '%');
            }
            
            if ($modelo) {
                $query->where('modelo', 'LIKE', '%' . $modelo . '%');
            }
            
            if ($marca) {
                $query->where('marca', 'LIKE', '%' . $marca . '%');
            }
            
            // Obtener datos para análisis
            $registros = $query->orderBy('sync_timestamp')->get();
            
            // Calcular incrementos de impresiones (diferencia entre registros consecutivos)
            $incrementos = $this->calcularIncrementos($registros, $agrupacion);
            
            // Obtener resumen general
            $resumen = $this->calcularResumen($incrementos);
            
            // Obtener distribución por modelo
            $porModelo = $this->calcularPorModelo($registros);
            
            // Obtener distribución por marca
            $porMarca = $this->calcularPorMarca($registros);
            
            // Obtener top impresoras
            $topImpresoras = $this->calcularTopImpresoras($incrementos);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'serie_temporal' => $incrementos,
                    'resumen' => $resumen,
                    'por_modelo' => $porModelo,
                    'por_marca' => $porMarca,
                    'top_impresoras' => $topImpresoras,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Calcular incrementos de impresiones por período
     */
    private function calcularIncrementos($registros, $agrupacion)
    {
        $agrupados = [];
        $ultimosPorHost = [];
        
        foreach ($registros as $reg) {
            $hostname = $reg->hostname;
            $fecha = Carbon::parse($reg->sync_timestamp);
            
            // Determinar clave de agrupación
            switch ($agrupacion) {
                case 'semana':
                    $clave = $fecha->format('Y-W');
                    $etiqueta = 'Semana ' . $fecha->format('W, Y');
                    break;
                case 'mes':
                    $clave = $fecha->format('Y-m');
                    $etiqueta = $fecha->format('F Y');
                    break;
                default: // día
                    $clave = $fecha->format('Y-m-d');
                    $etiqueta = $fecha->format('d/m/Y');
                    break;
            }
            
            // Inicializar período si no existe
            if (!isset($agrupados[$clave])) {
                $agrupados[$clave] = [
                    'fecha' => $clave,
                    'etiqueta' => $etiqueta,
                    'total' => 0,
                    'bn' => 0,
                    'color' => 0,
                ];
            }
            
            // Calcular incremento respecto al registro anterior del mismo host
            if (isset($ultimosPorHost[$hostname])) {
                $anterior = $ultimosPorHost[$hostname];
                
                // Total
                if ($reg->paginas_total !== null && $anterior['paginas_total'] !== null) {
                    $inc = max(0, $reg->paginas_total - $anterior['paginas_total']);
                    $agrupados[$clave]['total'] += $inc;
                    
                    // Caso 1: Ambos tienen B/N y Color separados - calcular incrementos
                    if ($reg->paginas_bn !== null && $anterior['paginas_bn'] !== null && 
                        $reg->paginas_color !== null && $anterior['paginas_color'] !== null) {
                        $agrupados[$clave]['bn'] += max(0, $reg->paginas_bn - $anterior['paginas_bn']);
                        $agrupados[$clave]['color'] += max(0, $reg->paginas_color - $anterior['paginas_color']);
                    }
                    // Caso 2: El actual tiene B/N y Color pero el anterior no - usar valores actuales
                    elseif ($reg->paginas_bn !== null && $reg->paginas_color !== null &&
                            ($anterior['paginas_bn'] === null || $anterior['paginas_color'] === null)) {
                        // Primera vez que vemos datos separados para esta impresora
                        $agrupados[$clave]['bn'] += $reg->paginas_bn;
                        $agrupados[$clave]['color'] += $reg->paginas_color;
                        // Ajustar el total para que coincida
                        $agrupados[$clave]['total'] = $agrupados[$clave]['bn'] + $agrupados[$clave]['color'];
                    }
                    // Caso 3: Sin separación - asumir todo B/N
                    else {
                        $agrupados[$clave]['bn'] += $inc;
                    }
                }
            } else {
                // Primer registro de esta impresora: contar desde 0
                if ($reg->paginas_total !== null) {
                    $agrupados[$clave]['total'] += $reg->paginas_total;
                    
                    // Si tiene B/N y Color separados, usarlos
                    if ($reg->paginas_bn !== null && $reg->paginas_color !== null) {
                        $agrupados[$clave]['bn'] += $reg->paginas_bn;
                        $agrupados[$clave]['color'] += $reg->paginas_color;
                    } else {
                        // Si no tiene separación, asumir que todo es B/N
                        $agrupados[$clave]['bn'] += $reg->paginas_total;
                    }
                }
            }
            
            // Actualizar último registro del host
            $ultimosPorHost[$hostname] = [
                'paginas_total' => $reg->paginas_total,
                'paginas_bn' => $reg->paginas_bn,
                'paginas_color' => $reg->paginas_color,
            ];
        }
        
        return array_values($agrupados);
    }
    
    /**
     * Calcular resumen general
     */
    private function calcularResumen($incrementos)
    {
        $totalImpresiones = array_sum(array_column($incrementos, 'total'));
        $totalBN = array_sum(array_column($incrementos, 'bn'));
        $totalColor = array_sum(array_column($incrementos, 'color'));
        $dias = count($incrementos);
        
        return [
            'total_impresiones' => $totalImpresiones,
            'total_bn' => $totalBN,
            'total_color' => $totalColor,
            'promedio_diario' => $dias > 0 ? round($totalImpresiones / $dias, 2) : 0,
            'porcentaje_bn' => $totalImpresiones > 0 ? round(($totalBN / $totalImpresiones) * 100, 1) : 0,
            'porcentaje_color' => $totalImpresiones > 0 ? round(($totalColor / $totalImpresiones) * 100, 1) : 0,
        ];
    }
    
    /**
     * Calcular distribución por sede
     */
    /**
     * Calcular distribución por modelo
     */
    private function calcularPorModelo($registros)
    {
        $porModelo = [];
        $ultimosPorHost = [];
        
        foreach ($registros as $reg) {
            $modelo = $reg->modelo ?: 'Modelo desconocido';
            $hostname = $reg->hostname;
            
            if (!isset($porModelo[$modelo])) {
                $porModelo[$modelo] = ['modelo' => $modelo, 'total' => 0, 'bn' => 0, 'color' => 0];
            }
            
            // Calcular incremento
            if (isset($ultimosPorHost[$hostname])) {
                $anterior = $ultimosPorHost[$hostname];
                
                if ($reg->paginas_total !== null && $anterior['paginas_total'] !== null) {
                    $inc = max(0, $reg->paginas_total - $anterior['paginas_total']);
                    $porModelo[$modelo]['total'] += $inc;
                    
                    if ($reg->paginas_bn !== null && $anterior['paginas_bn'] !== null && 
                        $reg->paginas_color !== null && $anterior['paginas_color'] !== null) {
                        $porModelo[$modelo]['bn'] += max(0, $reg->paginas_bn - $anterior['paginas_bn']);
                        $porModelo[$modelo]['color'] += max(0, $reg->paginas_color - $anterior['paginas_color']);
                    } else {
                        $porModelo[$modelo]['bn'] += $inc;
                    }
                }
            } else {
                // Primer registro: contar desde 0
                if ($reg->paginas_total !== null) {
                    $porModelo[$modelo]['total'] += $reg->paginas_total;
                    
                    if ($reg->paginas_bn !== null && $reg->paginas_color !== null) {
                        $porModelo[$modelo]['bn'] += $reg->paginas_bn;
                        $porModelo[$modelo]['color'] += $reg->paginas_color;
                    } else {
                        $porModelo[$modelo]['bn'] += $reg->paginas_total;
                    }
                }
            }
            
            $ultimosPorHost[$hostname] = [
                'paginas_total' => $reg->paginas_total,
                'paginas_bn' => $reg->paginas_bn,
                'paginas_color' => $reg->paginas_color,
            ];
        }
        
        // Ordenar por total descendente
        usort($porModelo, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        return array_values($porModelo);
    }
    
    /**
     * Calcular distribución por marca
     */
    private function calcularPorMarca($registros)
    {
        $porMarca = [];
        $ultimosPorHost = [];
        
        foreach ($registros as $reg) {
            $marca = $reg->marca ?: 'Marca desconocida';
            $hostname = $reg->hostname;
            
            if (!isset($porMarca[$marca])) {
                $porMarca[$marca] = ['marca' => $marca, 'total' => 0, 'bn' => 0, 'color' => 0];
            }
            
            // Calcular incremento
            if (isset($ultimosPorHost[$hostname])) {
                $anterior = $ultimosPorHost[$hostname];
                
                if ($reg->paginas_total !== null && $anterior['paginas_total'] !== null) {
                    $inc = max(0, $reg->paginas_total - $anterior['paginas_total']);
                    $porMarca[$marca]['total'] += $inc;
                    
                    if ($reg->paginas_bn !== null && $anterior['paginas_bn'] !== null && 
                        $reg->paginas_color !== null && $anterior['paginas_color'] !== null) {
                        $porMarca[$marca]['bn'] += max(0, $reg->paginas_bn - $anterior['paginas_bn']);
                        $porMarca[$marca]['color'] += max(0, $reg->paginas_color - $anterior['paginas_color']);
                    } else {
                        $porMarca[$marca]['bn'] += $inc;
                    }
                }
            } else {
                // Primer registro: contar desde 0
                if ($reg->paginas_total !== null) {
                    $porMarca[$marca]['total'] += $reg->paginas_total;
                    
                    if ($reg->paginas_bn !== null && $reg->paginas_color !== null) {
                        $porMarca[$marca]['bn'] += $reg->paginas_bn;
                        $porMarca[$marca]['color'] += $reg->paginas_color;
                    } else {
                        $porMarca[$marca]['bn'] += $reg->paginas_total;
                    }
                }
            }
            
            $ultimosPorHost[$hostname] = [
                'paginas_total' => $reg->paginas_total,
                'paginas_bn' => $reg->paginas_bn,
                'paginas_color' => $reg->paginas_color,
            ];
        }
        
        // Ordenar por total descendente
        usort($porMarca, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        return array_values($porMarca);
    }
    
    /**
     * Calcular top impresoras
     */
    private function calcularTopImpresoras($incrementos)
    {
        // Este método necesitaría recalcular por hostname, pero por simplicidad
        // retornaremos un array vacío y lo calcularemos en el frontend si es necesario
        return [];
    }
    
    /**
    /**
     * Obtener lista de modelos disponibles
     */
    public function obtenerModelos()
    {
        try {
            $modelos = ImpresoraCheckmkSync::select('modelo')
                ->distinct()
                ->whereNotNull('modelo')
                ->where('modelo', '!=', '')
                ->orderBy('modelo')
                ->pluck('modelo');
            
            return response()->json([
                'success' => true,
                'data' => $modelos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener modelos: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Obtener lista de marcas disponibles
     */
    public function obtenerMarcas()
    {
        try {
            $marcas = ImpresoraCheckmkSync::select('marca')
                ->distinct()
                ->whereNotNull('marca')
                ->where('marca', '!=', '')
                ->orderBy('marca')
                ->pluck('marca');
            
            return response()->json([
                'success' => true,
                'data' => $marcas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas: ' . $e->getMessage(),
            ], 500);
        }
    }
}
