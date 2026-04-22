<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrada de Mercaderia - {{ $movement->document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            line-height: 1.3;
            padding: 5mm;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 1mm;
        }
        
        .document-type {
            font-weight: bold;
            font-size: 10pt;
            margin: 2mm 0;
        }
        
        .section {
            margin: 3mm 0;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 2mm;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1mm;
            text-decoration: underline;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }
        
        .label {
            font-weight: bold;
        }
        
        .value {
            text-align: right;
        }
        
        .stock-box {
            text-align: center;
            border: 2px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: #f0f0f0;
        }
        
        .stock-value {
            font-size: 14pt;
            font-weight: bold;
        }
        
        .positive {
            color: #28a745;
        }
        
        .qr-section {
            text-align: center;
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 2px dashed #000;
        }
        
        .qr-code {
            margin: 2mm auto;
        }
        
        .verification-hash {
            font-size: 7pt;
            word-break: break-all;
            margin-top: 2mm;
        }
        
        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 8pt;
            border-top: 2px dashed #000;
            padding-top: 3mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $tenant->name }}</div>
        <div style="font-size: 8pt;">{{ $tenant->email }}</div>
        <div class="document-type">ENTRADA DE MERCADERIA</div>
        <div style="font-size: 8pt; margin-top: 1mm;">Doc: {{ $movement->document_number }}</div>
        <div style="font-size: 7pt;">{{ $movement->created_at->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="section">
        <div class="section-title">PRODUCTO</div>
        <div class="row">
            <span class="label">SKU:</span>
            <span class="value">{{ $product->sku }}</span>
        </div>
        <div style="margin: 1mm 0;">
            <strong>{{ $product->name }}</strong>
        </div>
        @if($product->description)
        <div style="font-size: 8pt; color: #666;">
            {{ Str::limit($product->description, 60) }}
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">MOVIMIENTO</div>
        <div class="row">
            <span class="label">Stock Anterior:</span>
            <span class="value">{{ number_format($movement->previous_stock) }} und</span>
        </div>
        <div class="row">
            <span class="label positive">Entrada:</span>
            <span class="value positive"><strong>+{{ number_format($movement->quantity) }} und</strong></span>
        </div>
        <div class="stock-box">
            <div style="font-size: 8pt;">STOCK ACTUAL</div>
            <div class="stock-value">{{ number_format($movement->new_stock) }} und</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">DETALLES</div>
        <div class="row">
            <span class="label">Motivo:</span>
        </div>
        <div style="margin: 1mm 0;">{{ $movement->reason }}</div>
        
        @if($movement->supplier_id && $movement->supplier)
        <div class="row">
            <span class="label">Proveedor:</span>
        </div>
        <div style="margin: 1mm 0;">
            {{ $movement->supplier->name }}
            @if($movement->supplier->contact_name)
                <br><span style="font-size: 7pt;">{{ $movement->supplier->contact_name }}</span>
            @endif
        </div>
        @endif
        
        @if($movement->invoice_reference)
        <div class="row">
            <span class="label">Factura/Guia:</span>
            <span class="value">{{ $movement->invoice_reference }}</span>
        </div>
        @endif
        
        @if($movement->batch_number)
        <div class="row">
            <span class="label">Lote:</span>
            <span class="value">{{ $movement->batch_number }}</span>
        </div>
        @endif
        
        @if($movement->expiry_date)
        <div class="row">
            <span class="label">Vencimiento:</span>
            <span class="value">{{ $movement->expiry_date->format('d/m/Y') }}</span>
        </div>
        @endif
        
        @if($movement->reference)
        <div class="row">
            <span class="label">Referencia:</span>
            <span class="value">{{ $movement->reference }}</span>
        </div>
        @endif
    </div>

    @if($movement->additional_notes)
    <div class="section">
        <div class="section-title">NOTAS</div>
        <div style="font-size: 8pt;">{{ $movement->additional_notes }}</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">RESPONSABLE</div>
        <div class="row">
            <span class="label">Recibido por:</span>
        </div>
        <div style="margin: 1mm 0;">{{ $movement->user_name }}</div>
        <div style="font-size: 8pt; color: #666;">
            {{ $movement->created_at->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="qr-section">
        <div style="font-weight: bold; margin-bottom: 2mm;">VERIFICACION</div>
        @if($qrCode)
        <div class="qr-code">
            <img src="{{ $qrCode }}" alt="QR Verificacion" style="width: 100px; height: 100px;" />
        </div>
        @endif
        <div class="verification-hash">
            Hash: {{ substr($movement->verification_hash, 0, 32) }}...
        </div>
        <div style="font-size: 6pt; margin-top: 2mm; word-wrap: break-word; word-break: break-all; overflow-wrap: break-word; line-height: 1.2;">
            {{ $verificationUrl }}
        </div>
    </div>

    <div class="footer">
        <div>Comprobante Verificable</div>
        <div style="font-size: 7pt; margin-top: 1mm;">
            Generado por {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
