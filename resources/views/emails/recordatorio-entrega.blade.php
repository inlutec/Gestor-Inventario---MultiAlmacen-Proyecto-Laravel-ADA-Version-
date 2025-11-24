@extends('emails.layout')

@section('contenido')
<h2>⏰ Recordatorio de Entrega Próxima</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Este es un recordatorio de que tienes material pendiente de entrega <strong>mañana</strong>.</p>

<div class="info-box">
    @if($movimiento->detalles && $movimiento->detalles->count() > 0)
        <p><strong>Materiales:</strong></p>
        @foreach($movimiento->detalles as $detalle)
            <p>• {{ $detalle->descripcion ?? 'N/A' }} ({{ $detalle->cantidad }} {{ $detalle->unidad ?? 'ud' }})</p>
        @endforeach
    @endif
    @if($movimiento->destino)
    <p><strong>Destino:</strong> {{ $movimiento->destino }}</p>
    @endif
    <p><strong>Fecha de Movimiento:</strong> {{ $movimiento->fecha_movimiento->format('d/m/Y') }}</p>
    @if($movimiento->numero_documento)
    <p><strong>Nº Documento:</strong> {{ $movimiento->numero_documento }}</p>
    @endif
</div>

<p>Por favor, asegúrate de estar disponible para recibir el material en la fecha indicada.</p>

<p style="text-align: center;">
    <a href="{{ $urlMovimiento }}" class="button">Ver Detalles</a>
</p>
@endsection
