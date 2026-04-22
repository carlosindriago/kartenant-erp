<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Salida de Mercaderia - {{ $movement->document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.25;
            color: #2c3e50;
            padding: 15mm;
        }

        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 1mm;
            vertical-align: middle;
        }

        .header-table td.company {
            width: 50%;
            text-align: left;
        }

        .header-table td.document {
            width: 50%;
            text-align: right;
        }

        .company-name {
            font-size: 12pt;
            color: #2c3e50;
            font-weight: 700;
            margin-right: 3mm;
            display: inline;
        }

        .company-data {
            font-size: 7.5pt;
            color: #7f8c8d;
            display: inline;
        }

        .document-type {
            font-size: 9pt;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 0.5pt;
        }

        .document-number {
            font-size: 10pt;
            font-weight: 600;
            color: #34495e;
            font-family: 'Courier New', monospace;
        }

        .document-date {
            font-size: 7pt;
            color: #7f8c8d;
        }

        .section {
            margin: 3mm 0;
            padding: 2.5mm;
            border: 0.5pt solid #bdc3c7;
            background: #ffffff;
        }

        .section-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 1pt solid #34495e;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }

        .info-table td {
            width: 33.33%;
            padding: 2mm;
            border: 0.5pt solid #bdc3c7;
            background: #f8f9fa;
            vertical-align: top;
        }

        .info-label {
            font-size: 7.5pt;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
            letter-spacing: 0.2pt;
        }

        .info-value {
            font-size: 9pt;
            color: #2c3e50;
            font-weight: 500;
        }

        .product-box {
            background: #fadbd8;
            padding: 3mm;
            border-left: 3pt solid #e74c3c;
            margin: 2mm 0;
        }

        .product-name {
            font-size: 11pt;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1mm;
        }

        .product-sku {
            font-size: 8pt;
            color: #7f8c8d;
            font-family: 'Courier New', monospace;
        }

        .stock-table {
            width: 100%;
            margin: 3mm 0;
            border-collapse: collapse;
        }

        .stock-table td {
            width: 33.33%;
            padding: 3mm;
            text-align: center;
            border: 1pt solid #bdc3c7;
            vertical-align: middle;
        }

        .stock-table td.previous {
            background: #ecf0f1;
            border-color: #95a5a6;
        }

        .stock-table td.exit {
            background: #fadbd8;
            border-color: #e74c3c;
        }

        .stock-table td.current {
            background: #d6eaf8;
            border-color: #3498db;
        }

        .stock-label {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            display: block;
            margin-bottom: 1.5mm;
            letter-spacing: 0.2pt;
            color: #2c3e50;
        }

        .stock-value {
            font-size: 16pt;
            font-weight: 700;
            display: block;
            line-height: 1;
            margin: 1mm 0;
        }

        .stock-table td.previous .stock-value {
            color: #7f8c8d;
        }

        .stock-table td.exit .stock-value {
            color: #e74c3c;
        }

        .stock-table td.current .stock-value {
            color: #3498db;
        }

        .stock-unit {
            font-size: 7pt;
            display: block;
            margin-top: 1mm;
            color: #7f8c8d;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }

        .details-table td {
            width: 33.33%;
            padding: 2mm;
            border: 0.5pt solid #bdc3c7;
            background: #f8f9fa;
            vertical-align: top;
        }

        .notes-box {
            background: #fef9e7;
            border-left: 3pt solid #f39c12;
            padding: 2.5mm;
            margin: 2mm 0;
        }

        .notes-title {
            font-weight: 600;
            margin-bottom: 1mm;
            font-size: 8.5pt;
            color: #2c3e50;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4mm;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            padding-top: 4mm;
            border-top: 1.5pt solid #2c3e50;
            vertical-align: top;
        }

        .signature-label {
            font-size: 8pt;
            font-weight: 600;
            margin-top: 1mm;
            color: #2c3e50;
            display: block;
        }

        .signature-role {
            font-size: 7pt;
            color: #7f8c8d;
            margin-top: 0.5mm;
            display: block;
        }

        .verification-section {
            margin-top: 6mm;
            padding: 3mm;
            background: #f8f9fa;
            border: 1pt dashed #95a5a6;
            text-align: center;
        }

        .verification-title {
            font-weight: 700;
            font-size: 9pt;
            margin-bottom: 2mm;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .qr-container {
            display: inline-block;
            margin: 2mm 0;
        }

        .verification-info {
            font-size: 7pt;
            color: #7f8c8d;
            margin-top: 1.5mm;
            line-height: 1.3;
        }

        .verification-hash {
            font-family: 'Courier New', monospace;
            font-size: 6.5pt;
            word-break: break-all;
            color: #34495e;
            margin: 1.5mm 0;
            line-height: 1.4;
        }

        .footer {
            margin-top: 6mm;
            padding-top: 3mm;
            border-top: 1pt solid #bdc3c7;
            text-align: center;
            font-size: 7pt;
            color: #7f8c8d;
            line-height: 1.4;
        }

        @page {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="company">
                    <span class="company-name">{{ $tenant->name }}</span>
                    <span class="company-data">
                        @if($tenant->phone) · Tel: {{ $tenant->phone }}@endif
                        @if($tenant->address) · {{ $tenant->address }}@endif
                    </span>
                </td>
                <td class="document">
                    <span class="document-type">SALIDA DE MERCADERIA</span><br>
                    <span class="document-number">{{ $movement->document_number }}</span><br>
                    <span class="document-date">{{ $movement->created_at->format('d/m/Y H:i:s') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Información del Producto</div>
        <div class="product-box">
            <div class="product-name">{{ $product->name }}</div>
            <div class="product-sku">SKU: {{ $product->sku }}</div>
            @if($product->description)
            <div style="margin-top: 1.5mm; font-size: 8.5pt; color: #7f8c8d;">
                {{ $product->description }}
            </div>
            @endif
        </div>

        <table class="stock-table">
            <tr>
                <td class="previous">
                    <span class="stock-label">Stock Anterior</span>
                    <span class="stock-value">{{ number_format($movement->previous_stock) }}</span>
                    <span class="stock-unit">unidades</span>
                </td>
                <td class="exit">
                    <span class="stock-label">Salida</span>
                    <span class="stock-value">-{{ number_format($movement->quantity) }}</span>
                    <span class="stock-unit">unidades</span>
                </td>
                <td class="current">
                    <span class="stock-label">Stock Actual</span>
                    <span class="stock-value">{{ number_format($movement->new_stock) }}</span>
                    <span class="stock-unit">unidades</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalles del Movimiento</div>
        <table class="details-table">
            <tr>
                <td>
                    <div class="info-label">Motivo</div>
                    <div class="info-value">{{ $movement->reason }}</div>
                </td>
                <td>
                    <div class="info-label">{{ $movement->reference ? 'Referencia' : '—' }}</div>
                    <div class="info-value">{{ $movement->reference ?? '—' }}</div>
                </td>
                <td>
                    <div class="info-label">{{ $authorizedBy ? 'Autorizado por' : '—' }}</div>
                    <div class="info-value">{{ $authorizedBy?->name ?? '—' }}</div>
                </td>
            </tr>
            @if($authorizedBy && $movement->authorized_at)
            <tr>
                <td>
                    <div class="info-label">Fecha Autorización</div>
                    <div class="info-value">{{ $movement->authorized_at->format('d/m/Y H:i:s') }}</div>
                </td>
                <td>
                    <div class="info-label">—</div>
                    <div class="info-value">—</div>
                </td>
                <td>
                    <div class="info-label">—</div>
                    <div class="info-value">—</div>
                </td>
            </tr>
            @endif
        </table>

        @if($movement->additional_notes)
        <div class="notes-box">
            <div class="notes-title">Notas Adicionales:</div>
            <div style="font-size: 8.5pt;">{{ $movement->additional_notes }}</div>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Responsable</div>
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-label">Solicitado por</div>
                    <div class="info-value">{{ $movement->user_name }}</div>
                </td>
                <td>
                    <div class="info-label">Fecha y Hora</div>
                    <div class="info-value">{{ $movement->created_at->format('d/m/Y H:i:s') }}</div>
                </td>
                <td>
                    <div class="info-label">—</div>
                    <div class="info-value">—</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <span class="signature-label">Firma del Solicitante</span>
                <span class="signature-role">{{ $movement->user_name }}</span>
            </td>
            <td>
                <span class="signature-label">Firma del Autorizado</span>
                <span class="signature-role">{{ $authorizedBy?->name ?? 'Administrador / Gerente' }}</span>
            </td>
        </tr>
    </table>

    <div class="verification-section">
        <div class="verification-title">Verificación de Autenticidad</div>
        @if($qrCode)
        <div class="qr-container">
            <img src="{{ $qrCode }}" alt="QR Verificacion" style="width: 120px; height: 120px;" />
        </div>
        <div class="verification-info">
            Escanee el código QR para verificar la autenticidad de este documento
        </div>
        @endif
        <div class="verification-hash">
            {{ $movement->verification_hash }}
        </div>
        <div class="verification-info">
            {{ $verificationUrl }}
        </div>
    </div>

    <div class="footer">
        <div>Comprobante verificable generado por {{ config('app.name') }}</div>
        <div style="margin-top: 1mm;">
            Documento válido sin firma autógrafa · La verificación digital garantiza su autenticidad
        </div>
    </div>
</body>
</html>
