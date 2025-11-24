@extends('emails.layout')

@section('contenido')
<h2>📦 Material Entregado</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Se ha completado la entrega del material solicitado.</p>

<div class="info-box">
    @if($movimiento->detalles && $movimiento->detalles->count() > 0)
        <p><strong>Materiales Entregados:</strong></p>
        @foreach($movimiento->detalles as $detalle)
            <p>• {{ $detalle->descripcion ?? 'N/A' }} ({{ $detalle->cantidad }} {{ $detalle->unidad ?? 'ud' }})</p>
        @endforeach
    @endif
    @if($movimiento->destino)
    <p><strong>Destino:</strong> {{ $movimiento->destino }}</p>
    @endif
    <p><strong>Fecha de Entrega:</strong> {{ $movimiento->fecha_entrega ? \Carbon\Carbon::parse($movimiento->fecha_entrega)->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</p>
    @if($movimiento->entregado_por)
    <p><strong>Entregado por:</strong> {{ $movimiento->usuarioEntrega->nombre ?? 'N/A' }}</p>
    @endif
    @if($movimiento->observaciones)
    <p><strong>Observaciones:</strong> {{ $movimiento->observaciones }}</p>
    @endif
</div>

<p>Puedes revisar los detalles de la entrega en el sistema.</p>

<p style="text-align: center;">
    <a href="{{ $urlMovimiento }}" class="button">Ver Detalles</a>
</p>
@endsection
