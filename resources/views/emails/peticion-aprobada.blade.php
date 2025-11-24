@extends('emails.layout')

@section('contenido')
<h2>✅ Petición Aprobada</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Tu petición de material ha sido <span class="badge badge-success">APROBADA</span></p>

<div class="info-box">
    <p><strong>Número de Petición:</strong> #{{ $peticion->id }}</p>
    @if($peticion->detalles && $peticion->detalles->count() > 0)
        @foreach($peticion->detalles as $index => $detalle)
            @if($index < 3)
            <p><strong>Material {{ $index + 1 }}:</strong> 
                {{ $detalle->entidad->datos['referencia'] ?? 'N/A' }} 
                ({{ $detalle->cantidad ?? 0 }} {{ $detalle->unidad ?? 'ud' }})
            </p>
            @endif
        @endforeach
        @if($peticion->detalles->count() > 3)
        <p><em>... y {{ $peticion->detalles->count() - 3 }} material(es) más</em></p>
        @endif
    @endif
    <p><strong>Aprobada por:</strong> {{ $aprobadoPor }}</p>
    <p><strong>Fecha de Aprobación:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    @if($peticion->comentarios_aprobacion)
    <p><strong>Observaciones:</strong> {{ $peticion->comentarios_aprobacion }}</p>
    @endif
</div>

<p>El material será preparado y te notificaremos cuando esté listo para su entrega.</p>

<p style="text-align: center;">
    <a href="{{ $urlPeticion }}" class="button">Ver Detalles</a>
</p>
@endsection
