@extends('emails.layout')

@section('contenido')
<h2>⚠️ Fecha de Entrega Vencida</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>La fecha prevista de entrega de un material ha sido superada y requiere tu atención.</p>

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
    <p><strong>Fecha de Movimiento:</strong> 
        <span class="badge badge-danger">{{ $movimiento->fecha_movimiento->format('d/m/Y') }}</span>
    </p>
    <p><strong>Días transcurridos:</strong> {{ $movimiento->fecha_movimiento->diffInDays(now()) }}</p>
    @if($movimiento->numero_documento)
    <p><strong>Nº Documento:</strong> {{ $movimiento->numero_documento }}</p>
    @endif
</div>

<p>Se recomienda contactar con el proveedor o actualizar la fecha prevista de entrega en el sistema.</p>

<p style="text-align: center;">
    <a href="{{ $urlMovimiento }}" class="button">Actualizar Fecha</a>
</p>
@endsection
