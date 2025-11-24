@extends('emails.layout')

@section('contenido')
<h2>✍️ Solicitud de Firma</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Se ha solicitado tu firma para un documento de material.</p>

<div class="info-box">
    <p><strong>Documento:</strong> {{ $firma->tipo_documento ?? 'Justificante de Material' }}</p>
    <p><strong>Material:</strong> {{ $firma->movimiento->material->nombre ?? 'N/A' }}</p>
    <p><strong>Cantidad:</strong> {{ $firma->movimiento->cantidad }}</p>
    <p><strong>Solicitado por:</strong> {{ $solicitadoPor }}</p>
    <p><strong>Fecha de Solicitud:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    @if($firma->observaciones)
    <p><strong>Observaciones:</strong> {{ $firma->observaciones }}</p>
    @endif
</div>

<p>Por favor, revisa el documento y procede con la firma.</p>

<p style="text-align: center;">
    <a href="{{ $urlFirma }}" class="button">Firmar Documento</a>
</p>

<p style="font-size: 12px; color: #666; margin-top: 20px;">
    <strong>Nota:</strong> Puedes firmar desde tu dispositivo móvil accediendo a la aplicación PWA.
</p>
@endsection
