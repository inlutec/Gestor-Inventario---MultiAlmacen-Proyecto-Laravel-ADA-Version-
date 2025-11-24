<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $movimiento->tipo === 'entrada' ? 'Entrada' : 'Salida' }} - {{ $movimiento->numero_documento }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.25;
            color: #1a3c1a;
            padding: 16px 18px 12px 18px;
            background: #f6fff6;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #008c45;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }
        .header-logos {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .header-logos img {
            height: 80px;
            max-width: 220px;
            object-fit: contain;
        }
        .header-title {
            text-align: right;
            flex: 1;
        }
        .header-title h1 {
            font-size: 17pt;
            color: #008c45;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .document-number {
            font-size: 12pt;
            color: #008c45;
            font-weight: bold;
        }
        
        .info-section {
            margin-bottom: 12px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            background: #e6f4e6;
            padding: 6px 7px;
            font-weight: bold;
            width: 25%;
            border-bottom: 1px solid #b6dcb6;
        }
        .info-value {
            display: table-cell;
            padding: 6px 7px;
            border-bottom: 1px solid #b6dcb6;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #008c45;
            margin: 12px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 2px solid #b6dcb6;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table thead {
            background: #008c45;
            color: white;
        }
        .table th {
            padding: 7px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        .table td {
            padding: 6px 5px;
            border-bottom: 1px solid #b6dcb6;
            font-size: 9.5pt;
        }
        .table tbody tr:nth-child(even) {
            background: #e6f4e6;
        }
        
        .signatures {
            margin-top: 18px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 48%;
            padding: 8px 10px;
            border: 2px solid #b6dcb6;
            border-radius: 7px;
            vertical-align: top;
        }
        .signature-box + .signature-box {
            margin-left: 3%;
        }
        
        .signature-title {
            font-weight: bold;
            font-size: 11pt;
            color: #008c45;
            margin-bottom: 7px;
            text-align: center;
        }
        
        .signature-image {
            text-align: center;
            margin: 8px 0;
            min-height: 60px;
            border: 1px dashed #b6dcb6;
            padding: 6px;
            background: #f6fff6;
        }
        .signature-image img {
            max-width: 160px;
            max-height: 48px;
        }
        
        .signature-info {
            font-size: 8.5pt;
            color: #1a3c1a;
            margin-top: 6px;
        }
        
        .signature-info div {
            margin: 3px 0;
        }
        
        .signature-name {
            font-weight: bold;
            color: #008c45;
            font-size: 9.5pt;
        }
        
        .footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid #b6dcb6;
            text-align: center;
            font-size: 8.5pt;
            color: #008c45;
        }
        
        .observations {
            background: #e6f4e6;
            border-left: 4px solid #008c45;
            padding: 7px 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .observations-title {
            font-weight: bold;
            color: #008c45;
            margin-bottom: 3px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .badge-entrada {
            background: #b6dcb6;
            color: #008c45;
        }
        .badge-salida {
            background: #ffeaea;
            color: #991b1b;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logos">
            <img src="{{ public_path('images/junta-logo.png') }}" alt="Junta de Andalucía">
            <img src="{{ public_path('images/ada-logo.png') }}" alt="Agencia Digital de Andalucía">
        </div>
        <div class="header-title">
            <h1>{{ $movimiento->tipo === 'entrada' ? 'ENTRADA DE MATERIAL' : 'SALIDA DE MATERIAL' }}</h1>
            <div class="document-number">{{ $movimiento->numero_documento }}</div>
            <div style="margin-top: 7px;">
                <span class="badge badge-{{ $movimiento->tipo }}">
                    {{ $movimiento->tipo === 'entrada' ? 'ENTRADA' : 'SALIDA' }}
                </span>
            </div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Fecha:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($movimiento->fecha_movimiento)->format('d/m/Y H:i') }}</div>
                <div class="info-label">Estado:</div>
                <div class="info-value" style="text-transform: uppercase; font-weight: bold;">{{ $movimiento->estado }}</div>
            </div>
            @if($movimiento->origen)
            <div class="info-row">
                <div class="info-label">Origen:</div>
                <div class="info-value" colspan="3">{{ $movimiento->origen }}</div>
            </div>
            @endif
            @if($movimiento->destino)
            <div class="info-row">
                <div class="info-label">Destino:</div>
                <div class="info-value" colspan="3">{{ $movimiento->destino }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section-title">Detalle de Materiales</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 45%;">Descripción</th>
                <th style="width: 15%;" class="text-center">Cantidad</th>
                <th style="width: 15%;" class="text-center">Unidad</th>
                <th style="width: 15%;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $detalle->descripcion }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-center">{{ $detalle->unidad }}</td>
                <td>{{ $detalle->observaciones ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($movimiento->observaciones)
    <div class="observations">
        <div class="observations-title">Observaciones Generales:</div>
        <div>{{ $movimiento->observaciones }}</div>
    </div>
    @endif

    <div class="section-title">Firmas</div>
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-title">EMISOR</div>
            @if($firmaEmisor)
                <div class="signature-image">
                    <img src="{{ $firmaEmisor->firma_rubrica }}" alt="Firma Emisor" />
                </div>
                <div class="signature-info">
                    <div class="signature-name">{{ $firmaEmisor->nombre_completo }}</div>
                    @if($firmaEmisor->dni)
                    <div>DNI: {{ $firmaEmisor->dni }}</div>
                    @endif
                    <div>Fecha: {{ \Carbon\Carbon::parse($firmaEmisor->fecha_firma)->format('d/m/Y H:i:s') }}</div>
                    <div>IP: {{ $firmaEmisor->ip_address }}</div>
                </div>
            @else
                <div class="signature-image">
                    <div style="color: #94a3b8; padding: 20px;">SIN FIRMAR</div>
                </div>
            @endif
        </div>

        <div class="signature-box">
            <div class="signature-title">RECEPTOR</div>
            @if($firmaReceptor)
                <div class="signature-image">
                    <img src="{{ $firmaReceptor->firma_rubrica }}" alt="Firma Receptor" />
                </div>
                <div class="signature-info">
                    <div class="signature-name">{{ $firmaReceptor->nombre_completo }}</div>
                    @if($firmaReceptor->dni)
                    <div>DNI: {{ $firmaReceptor->dni }}</div>
                    @endif
                    <div>Fecha: {{ \Carbon\Carbon::parse($firmaReceptor->fecha_firma)->format('d/m/Y H:i:s') }}</div>
                    <div>IP: {{ $firmaReceptor->ip_address }}</div>
                </div>
            @else
                <div class="signature-image">
                    <div style="color: #94a3b8; padding: 20px;">PENDIENTE DE FIRMA</div>
                </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <div>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</div>
        <div>Sistema de Gestión de Inventario - Pequeño Material</div>
        <div style="margin-top: 6px; font-size: 9pt; color: #2563eb;">
            Junta de Andalucía · Agencia Digital de Andalucía
        </div>
    </div>
</body>
</html>
