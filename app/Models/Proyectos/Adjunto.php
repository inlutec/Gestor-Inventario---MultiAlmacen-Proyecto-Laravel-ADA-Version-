<?php

namespace App\Models\Proyectos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Adjunto extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'proyectos';
    protected $table = 'adjuntos';

    protected $fillable = [
        'adjuntable_type',
        'adjuntable_id',
        'nombre_original',
        'nombre_archivo',
        'ruta',
        'tipo_mime',
        'tamano',
        'subido_por',
        'descripcion',
    ];

    protected $appends = ['tamano_legible', 'url', 'icono'];

    public function adjuntable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'subido_por');
    }

    public function getTamanoLegibleAttribute()
    {
        $bytes = $this->tamano;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getUrlAttribute()
    {
        return Storage::disk('proyectos')->url($this->ruta);
    }

    public function getIconoAttribute()
    {
        $tipo = explode('/', $this->tipo_mime)[0];
        
        $iconos = [
            'image' => '🖼️',
            'video' => '🎥',
            'audio' => '🎵',
            'application/pdf' => '📄',
            'application/zip' => '📦',
            'text' => '📝',
        ];

        return $iconos[$tipo] ?? $iconos[$this->tipo_mime] ?? '📎';
    }

    public function esImagen()
    {
        return str_starts_with($this->tipo_mime, 'image/');
    }

    public function esPDF()
    {
        return $this->tipo_mime === 'application/pdf';
    }
}
