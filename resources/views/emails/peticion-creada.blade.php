@extends('emails.layout')

@section('contenido')
<h2>Nueva Petición de Material</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Se ha creado una nueva petición de material que requiere tu atención.</p>

<div class="info-box">
    <p><strong>Número de Petición:</strong> #{{ $peticion->id }}</p>
    <p><strong>Usuario:</strong> {{ $peticion->usuario_solicitante ?? 'N/A' }}</p>
    <p><strong>Departamento:</strong> {{ $peticion->departamento->nombre ?? 'N/A' }}</p>
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
    @else
        <p><strong>Material Solicitado:</strong> N/A</p>
    @endif
    <p><strong>Fecha de Solicitud:</strong> {{ $peticion->created_at->format('d/m/Y H:i') }}</p>
    @if($peticion->observaciones)
    <p><strong>Observaciones:</strong> {{ $peticion->observaciones }}</p>
    @endif
</div>

@if($esAdmin)
<p style="text-align: center;">
    <a href="{{ $urlPeticion }}" class="button">Ver Petición</a>
</p>
@endif

<p>Puedes revisar esta petición en el sistema de gestión de material.</p>
@endsection
