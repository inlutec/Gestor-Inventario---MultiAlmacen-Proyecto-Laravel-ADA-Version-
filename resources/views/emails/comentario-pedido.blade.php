@extends('emails.layout')

@section('contenido')
<h2>Nuevo Comentario en tu Pedido</h2>

<p>Hola {{ $nombreUsuario }},</p>

<p>Se ha agregado un nuevo comentario a tu pedido de material.</p>

<div class="info-box">
    <p><strong>Número de Pedido:</strong> #{{ $pedido->numero_pedido }}</p>
    <p><strong>Estado:</strong> {{ ucfirst($pedido->estado) }}</p>
    <p><strong>Comentado por:</strong> {{ $comentadoPor }}</p>
    <p><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div style="background-color: #f9fafb; border-left: 4px solid #6366f1; padding: 15px; margin: 20px 0;">
    <p style="margin: 0 0 10px 0; font-weight: bold; color: #1e1b4b;">💬 Comentario:</p>
    <p style="margin: 0; color: #1e293b; white-space: pre-wrap;">{{ $comentario }}</p>
</div>

@if(isset($urlSeguimiento) && $urlSeguimiento)
<div style="background-color: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;">
    <p style="margin: 0 0 10px 0; font-weight: bold; color: #1e40af;">📋 Ver Seguimiento Completo</p>
    <p style="margin: 0 0 15px 0; color: #1e3a8a;">Puedes consultar todos los comentarios y actualizaciones de tu pedido:</p>
    <p style="text-align: center; margin: 0;">
        <a href="{{ $urlSeguimiento }}" class="button" style="background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">Ver Seguimiento del Pedido</a>
    </p>
</div>
@endif

<p>Gracias por tu paciencia.</p>
@endsection
