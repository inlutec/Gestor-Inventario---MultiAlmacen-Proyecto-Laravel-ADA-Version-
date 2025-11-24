@extends('emails.layout')

@section('content')
<tr>
    <td style="padding: 30px; background-color: #ffffff;">
        <!-- Encabezado con icono -->
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #047857 100%); width: 80px; height: 80px; border-radius: 50%; line-height: 80px; font-size: 40px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                ✅
            </div>
        </div>
        
        <h2 style="color: #059669; margin-top: 0; font-size: 26px; text-align: center; font-weight: 700;">
            ¡Stock Disponible!
        </h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #374151; margin: 20px 0; text-align: center;">
            Estimado/a <strong>{{ $usuario_nombre }}</strong>,
        </p>
        
        <p style="font-size: 16px; line-height: 1.8; color: #374151; margin: 25px 0;">
            Nos complace informarle que el material que había solicitado <strong>ya se encuentra disponible</strong> en nuestro almacén. Gracias por su paciencia durante este tiempo de espera.
        </p>
        
        <!-- Detalles del material -->
        <div style="background: linear-gradient(to bottom, #f9fafb 0%, #ffffff 100%); padding: 25px; border-radius: 12px; margin: 30px 0; border: 2px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="color: #111827; margin: 0 0 20px 0; font-size: 18px; font-weight: 600; border-bottom: 2px solid #059669; padding-bottom: 10px;">
                📦 Detalles del Material
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-weight: 600; width: 45%; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">📌</span>
                        Material:
                    </td>
                    <td style="padding: 12px 0; color: #111827; font-weight: bold; font-size: 16px;">{{ $material_nombre }}</td>
                </tr>
                @if($material_referencia && $material_referencia !== 'Sin referencia')
                <tr style="background-color: #f9fafb;">
                    <td style="padding: 12px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">🔖</span>
                        Referencia:
                    </td>
                    <td style="padding: 12px 0; color: #111827; font-family: monospace; font-size: 15px;">{{ $material_referencia }}</td>
                </tr>
                @endif
                @if($material_descripcion)
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">📝</span>
                        Descripción:
                    </td>
                    <td style="padding: 12px 0; color: #374151; line-height: 1.6;">{{ $material_descripcion }}</td>
                </tr>
                @endif
                <tr style="background-color: #f9fafb;">
                    <td style="padding: 12px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">🔢</span>
                        Cantidad solicitada:
                    </td>
                    <td style="padding: 12px 0;">
                        <span style="display: inline-block; background-color: #059669; color: white; padding: 6px 16px; border-radius: 20px; font-weight: bold; font-size: 16px;">
                            {{ $cantidad_solicitada }} unidades
                        </span>
                    </td>
                </tr>
                @if($prevision_llegada)
                <tr>
                    <td style="padding: 12px 0; color: #6b7280; font-weight: 600; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">📅</span>
                        Disponible desde:
                    </td>
                    <td style="padding: 12px 0; color: #059669; font-weight: 700; font-size: 15px;">{{ $prevision_llegada }}</td>
                </tr>
                @endif
                @if($notas)
                <tr style="background-color: #fef3c7;">
                    <td style="padding: 12px 0; color: #92400e; font-weight: 600; vertical-align: top;">
                        <span style="display: inline-block; width: 24px; text-align: center; margin-right: 8px;">💬</span>
                        Observaciones:
                    </td>
                    <td style="padding: 12px 0; color: #78350f; font-style: italic; line-height: 1.6;">{{ $notas }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <!-- Llamada a la acción -->
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 25px; border-radius: 12px; margin: 30px 0; text-align: center; box-shadow: 0 4px 6px rgba(5, 150, 105, 0.3);">
            <p style="margin: 0 0 15px 0; color: #ffffff; font-size: 18px; font-weight: 600;">
                🎯 Siguiente Paso
            </p>
            <p style="margin: 0 0 20px 0; color: #d1fae5; font-size: 15px; line-height: 1.7;">
                Ya puede realizar su pedido a través de nuestra <strong>plataforma de gestión de material</strong>.<br>
                Acceda al sistema y complete su solicitud para recibir el material.
            </p>
            <div style="margin: 20px 0 0 0;">
                <a href="{{ config('app.url') }}" 
                   style="display: inline-block; background-color: #ffffff; color: #047857; padding: 14px 35px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s;">
                    🚀 Acceder a la Plataforma
                </a>
            </div>
        </div>
        
        <!-- Información adicional -->
        <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 25px 0; border-radius: 4px;">
            <p style="margin: 0 0 10px 0; color: #1e40af; font-weight: 700; font-size: 15px;">
                ℹ️ Información Importante
            </p>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #1e3a8a; font-size: 14px; line-height: 1.8;">
                <li style="margin-bottom: 8px;">El stock está garantizado para su solicitud</li>
                <li style="margin-bottom: 8px;">Complete su pedido lo antes posible para asegurar la disponibilidad</li>
                <li style="margin-bottom: 8px;">Si tiene alguna duda, contacte con el departamento correspondiente</li>
            </ul>
        </div>
        
        <!-- Agradecimiento -->
        <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center; border: 1px solid #fbbf24;">
            <p style="margin: 0; color: #92400e; font-size: 16px; font-weight: 600;">
                🙏 Gracias por su espera
            </p>
            <p style="margin: 10px 0 0 0; color: #78350f; font-size: 14px; line-height: 1.6;">
                Valoramos su paciencia y confianza en nuestro sistema de gestión.<br>
                Estamos aquí para ayudarle en todo lo que necesite.
            </p>
        </div>
        
        <!-- Firma -->
        <div style="margin-top: 40px; padding-top: 25px; border-top: 2px solid #e5e7eb;">
            <p style="font-size: 16px; line-height: 1.6; color: #374151; margin: 0 0 8px 0;">
                Atentamente,
            </p>
            <p style="font-size: 17px; color: #111827; margin: 0; font-weight: 700;">
                Sistema de Gestión de Material
            </p>
            <p style="font-size: 14px; color: #6b7280; margin: 8px 0 0 0; font-style: italic;">
                Junta de Andalucía - Agencia Digital de Andalucía
            </p>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <!-- Pie de página -->
        <div style="text-align: center;">
            <p style="font-size: 12px; color: #9ca3af; margin: 0 0 5px 0;">
                ⚡ Este es un mensaje automático generado por el sistema
            </p>
            <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                Por favor, no responda directamente a este correo
            </p>
        </div>
    </td>
</tr>
@endsection
