<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckmkSyncLog extends Model
{
    protected $table = 'checkmk_sync_logs';

    protected $fillable = [
        'sync_timestamp',
        'status',
        'hosts_processed',
        'hosts_success',
        'hosts_error',
        'message',
        'details',
        'duration_seconds',
    ];

    protected $casts = [
        'sync_timestamp' => 'datetime',
        'details' => 'array',
    ];

    /**
     * Obtener los últimos N logs
     */
    public static function getLatest($limit = 100)
    {
        return self::orderBy('sync_timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Crear un nuevo log de sincronización
     */
    public static function createLog($status, $processed, $success, $error, $message = null, $details = null, $duration = null)
    {
        return self::create([
            'sync_timestamp' => now(),
            'status' => $status,
            'hosts_processed' => $processed,
            'hosts_success' => $success,
            'hosts_error' => $error,
            'message' => $message,
            'details' => $details,
            'duration_seconds' => $duration,
        ]);
    }
}
