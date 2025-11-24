@extends('emails.layout')

@section('contenido')
<h2>Nuevo Movimiento de Material</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Se ha registrado un nuevo movimiento de material en el sistema.</p>

<div class="info-box">
    <p><strong>Tipo de Movimiento:</strong> 
        <span class="badge {{ $movimiento->tipo == 'entrada' ? 'badge-success' : 'badge-warning' }}">
            {{ strtoupper($movimiento->tipo ?? 'N/A') }}
        </span>
    </p>
    @if($movimiento->detalles && $movimiento->detalles->count() > 0)
        <p><strong>Materiales:</strong></p>
        @foreach($movimiento->detalles->take(3) as $detalle)
            <p>• {{ $detalle->descripcion ?? 'N/A' }} ({{ $detalle->cantidad }} {{ $detalle->unidad ?? 'ud' }})</p>
        @endforeach
        @if($movimiento->detalles->count() > 3)
            <p><em>... y {{ $movimiento->detalles->count() - 3 }} más</em></p>
        @endif
    @endif
    @if($movimiento->origen)
    <p><strong>Origen:</strong> {{ $movimiento->origen }}</p>
    @endif
    @if($movimiento->destino)
    <p><strong>Destino:</strong> {{ $movimiento->destino }}</p>
    @endif
    @if($movimiento->numero_documento)
    <p><strong>Nº Documento:</strong> {{ $movimiento->numero_documento }}</p>
    @endif
    <p><strong>Registrado por:</strong> {{ $movimiento->usuario->nombre ?? 'N/A' }}</p>
    <p><strong>Fecha:</strong> {{ $movimiento->created_at->format('d/m/Y H:i') }}</p>
    @if($movimiento->observaciones)
    <p><strong>Observaciones:</strong> {{ $movimiento->observaciones }}</p>
    @endif
</div>

<p style="text-align: center;">
    <a href="{{ $urlMovimiento }}" class="button">Ver Movimiento</a>
</p>
@endsection
