@extends('emails.layout')

@section('contenido')
<h2>❌ Petición Denegada</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Lamentablemente, tu petición de material ha sido <span class="badge badge-danger">DENEGADA</span></p>

<div class="info-box">
    <p><strong>Número de Petición:</strong> #{{ $peticion->id }}</p>
    @if($peticion->detalles && $peticion->detalles->count() > 0)
        <p><strong>Materiales Solicitados:</strong></p>
        @foreach($peticion->detalles as $index => $detalle)
            @if($index < 3)
            <p>• {{ $detalle->entidad->datos['referencia'] ?? 'N/A' }} 
                ({{ $detalle->cantidad ?? 0 }} {{ $detalle->unidad ?? 'ud' }})
            </p>
            @endif
        @endforeach
        @if($peticion->detalles->count() > 3)
        <p><em>... y {{ $peticion->detalles->count() - 3 }} más</em></p>
        @endif
    @endif
    <p><strong>Denegada por:</strong> {{ $denegadoPor }}</p>
    <p><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    @if($motivo)
    <p><strong>Motivo:</strong> {{ $motivo }}</p>
    @endif
</div>

<p>Si tienes alguna duda o deseas solicitar material nuevamente, puedes crear una nueva petición en el sistema.</p>

@if($urlSeguimiento)
<p style="text-align: center; margin-top: 20px;">
    <a href="{{ $urlSeguimiento }}" class="button" style="background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;">Seguir mi Petición</a>
</p>
@endif

<p style="text-align: center; margin-top: 10px;">
    <a href="{{ $urlPeticion }}" class="button" style="background-color: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">Ver Detalles</a>
</p>
@endsection
