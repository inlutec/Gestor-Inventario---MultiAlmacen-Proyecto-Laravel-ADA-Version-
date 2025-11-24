@extends('emails.layout')

@section('contenido')
<h2>📅 Fecha de Entrega Establecida</h2>

<p>Hola {{ $nombre_usuario }},</p>

<p>Te informamos que se ha establecido la fecha prevista de entrega para tu movimiento de material.</p>

<div class="info-box" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 20px; margin: 20px 0;">
    <p style="font-size: 16px; margin-bottom: 10px;"><strong>📦 Información del Movimiento:</strong></p>
    
    <p><strong>Tipo:</strong> 
        <span class="badge {{ $tipo_movimiento == 'Entrada' ? 'badge-success' : 'badge-warning' }}">
            {{ $tipo_movimiento }}
        </span>
    </p>
    
    <p><strong>Número de Documento:</strong> {{ $numero_documento ?? 'N/A' }}</p>
    
    @if($origen)
    <p><strong>Origen:</strong> {{ $origen }}</p>
    @endif
    
    @if($destino)
    <p><strong>Destino:</strong> {{ $destino }}</p>
    @endif
</div>

<div class="info-box" style="background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 20px; margin: 20px 0;">
    <p style="font-size: 18px; font-weight: bold; color: #e65100; text-align: center; margin: 0;">
        📅 Fecha Prevista de Entrega
    </p>
    <p style="font-size: 24px; font-weight: bold; text-align: center; color: #00695c; margin: 15px 0;">
        {{ $fecha_entrega }}
    </p>
</div>

@if($observaciones)
<div class="info-box">
    <p><strong>Observaciones:</strong></p>
    <p>{{ $observaciones }}</p>
</div>
@endif

<p style="color: #666; font-size: 14px; margin-top: 25px;">
    Por favor, asegúrate de estar disponible en la fecha indicada para recibir el material.
</p>

<p style="color: #666; font-size: 14px;">
    Si tienes alguna duda o necesitas cambiar la fecha, ponte en contacto con el departamento correspondiente.
</p>

@endsection
