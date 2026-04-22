<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de Crédito #{{ $saleReturn->return_number }}</title>
    <style>
        /* ===== FORMATO TICKET TÉRMICO 80mm ===== */
        @page {
            size: 80mm 250mm;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            width: 74mm;
            margin: 0 auto;
            padding: 1.5mm;
            page-break-inside: avoid;
        }
        
        /* Evitar saltos de página */
        .header,
        .info-section,
        .reason-section,
        .items-section,
        .totals-section,
        .verification-section,
        .footer {
            page-break-inside: avoid;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
            padding-left: 0;
            padding-right: 0;
            border-bottom: 2px dashed #000;
        }
        .business-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .business-info {
            font-size: 6pt;
            line-height: 1.2;
            margin-bottom: 1px;
        }
        .doc-type {
            font-size: 8pt;
            font-weight: bold;
            margin: 4px 0 2px 0;
            text-transform: uppercase;
            color: #d97706;
        }
        .return-number {
            font-size: 10pt;
            font-weight: bold;
            margin: 2px 0;
        }
        .date-time {
            font-size: 7pt;
            margin-bottom: 2px;
        }
        
        /* Info Section */
        .info-section {
            margin: 8px 0;
            padding: 5px 0;
            padding-left: 0;
            padding-right: 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            max-width: 100%;
            overflow: hidden;
        }
        .info-section .row {
            font-size: 7pt;
            margin-bottom: 1px;
            display: flex;
            justify-content: space-between;
            max-width: 100%;
            overflow: hidden;
        }
        .info-section strong {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-success {
            background: #16a34a;
            color: white;
        }
        .badge-warning {
            background: #d97706;
            color: white;
        }
        
        /* Reason Section */
        .reason-section {
            margin: 8px 0;
            padding: 5px;
            background: #f5f5f5;
            border: 1px solid #999;
        }
        .reason-section .label {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .reason-section .text {
            font-size: 8pt;
        }
        
        /* Items Table */
        .items-section {
            margin: 8px 0;
            max-width: 100%;
            overflow: hidden;
        }
        .items-header {
            font-size: 6pt;
            font-weight: bold;
            padding: 2px 0;
            border-bottom: 1px solid #000;
            display: flex;
            justify-content: space-between;
            max-width: 100%;
            overflow: hidden;
        }
        .item {
            padding: 3px 0;
            border-bottom: 1px dotted #999;
            font-size: 7pt;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .item-code {
            font-size: 6pt;
            color: #666;
            margin-bottom: 1px;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            max-width: 100%;
            overflow: hidden;
        }
        
        /* Totals */
        .totals-section {
            margin: 8px 0;
            padding-top: 5px;
            border-top: 2px solid #000;
            max-width: 100%;
            overflow: hidden;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 7pt;
            max-width: 100%;
            overflow: hidden;
        }
        .total-row.final {
            font-size: 9pt;
            font-weight: bold;
            padding: 4px 0;
            border-top: 2px solid #000;
            margin-top: 2px;
            color: #d97706;
        }
        
        /* Verification QR */
        .verification-section {
            margin: 8px 0;
            padding: 5px;
            border: 1px solid #000;
            text-align: center;
            max-width: 100%;
            overflow: hidden;
        }
        .verification-section .title {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .verification-section .subtitle {
            font-size: 6pt;
            margin-bottom: 5px;
        }
        .verification-section img {
            width: 45mm;
            height: 45mm;
            margin: 3px auto;
            display: block;
        }
        .verification-section .url {
            font-size: 4.5pt;
            word-wrap: break-word;
            word-break: break-all;
            margin: 3px auto;
            padding: 0 2mm;
            max-width: 100%;
            line-height: 1.3;
            overflow-wrap: break-word;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 8px;
            margin-bottom: 0;
            padding-top: 6px;
            padding-bottom: 3mm;
            padding-left: 0;
            padding-right: 0;
            border-top: 2px dashed #000;
            font-size: 6pt;
            max-width: 100%;
            overflow: hidden;
        }
        .footer .important {
            font-weight: bold;
            font-size: 7pt;
            margin-bottom: 2px;
        }
        .footer .warning {
            font-weight: bold;
            color: #d97706;
            margin: 5px 0;
        }
        
        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .small { font-size: 5pt; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="business-name">{{ $tenant->name }}</div>
        @if($tenant->address)
            <div class="business-info">{{ $tenant->address }}</div>
        @endif
        @if($tenant->phone)
            <div class="business-info">Tel: {{ $tenant->phone }}</div>
        @endif
        @if($tenant->cuit)
            <div class="business-info">CUIT: {{ $tenant->cuit }}</div>
        @endif
        <div class="doc-type">Nota de Crédito</div>
        <div class="small">(Devolución)</div>
        <div class="return-number">#{{ $saleReturn->return_number }}</div>
        <div class="date-time">{{ $saleReturn->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Return Info --}}
    <div class="info-section">
        <div class="row">
            <strong>Venta Original:</strong>
            <span>{{ $saleReturn->originalSale->invoice_number }}</span>
        </div>
        <div class="row">
            <strong>Estado:</strong>
            <span class="badge badge-{{ $saleReturn->status === 'completed' ? 'success' : 'warning' }}">
                {{ strtoupper($saleReturn->status) }}
            </span>
        </div>
        <div class="row">
            <strong>Tipo:</strong>
            <span>{{ ucfirst(str_replace('_', ' ', $saleReturn->return_type)) }}</span>
        </div>
        @if($saleReturn->refund_method)
            <div class="row">
                <strong>Reembolso:</strong>
                <span>{{ ucfirst(str_replace('_', ' ', $saleReturn->refund_method)) }}</span>
            </div>
        @endif
    </div>

    {{-- Customer Info --}}
    @if($saleReturn->originalSale->customer)
        <div class="info-section">
            <div class="row">
                <strong>Cliente:</strong>
                <span>{{ $saleReturn->originalSale->customer->name }}</span>
            </div>
            @if($saleReturn->originalSale->customer->document_number)
                <div class="row">
                    <strong>{{ $saleReturn->originalSale->customer->document_type }}:</strong>
                    <span>{{ $saleReturn->originalSale->customer->document_number }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Reason --}}
    @if($saleReturn->reason)
        <div class="reason-section">
            <div class="label">MOTIVO DE DEVOLUCIÓN:</div>
            <div class="text">{{ $saleReturn->reason }}</div>
        </div>
    @endif

    {{-- Processed By --}}
    @if($saleReturn->processedBy)
        <div class="row small" style="margin: 5px 0;">
            <strong>Procesado por:</strong> {{ $saleReturn->processedBy->name }}
        </div>
        <div class="row small" style="margin-bottom: 8px;">
            <strong>Fecha:</strong> {{ $saleReturn->processed_at->format('d/m/Y H:i') }}
        </div>
    @endif

    {{-- Items --}}
    <div class="items-section">
        <div class="items-header">
            <span>ARTÍCULO DEVUELTO</span>
            <span>IMPORTE</span>
        </div>
        @foreach($saleReturn->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product_name }}</div>
                @if($item->product && $item->product->sku)
                    <div class="item-code">Código: {{ $item->product->sku }}</div>
                @endif
                <div class="item-details">
                    <span>{{ $item->quantity }} x ${{ number_format($item->unit_price, 2) }}</span>
                    <span class="bold">${{ number_format($item->unit_price * $item->quantity, 2) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Totals --}}
    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($saleReturn->subtotal, 2) }}</span>
        </div>
        @if($saleReturn->tax_amount > 0)
            <div class="total-row">
                <span>IVA:</span>
                <span>${{ number_format($saleReturn->tax_amount, 2) }}</span>
            </div>
        @endif
        <div class="total-row final">
            <span>TOTAL A REEMBOLSAR:</span>
            <span>${{ number_format($saleReturn->total, 2) }}</span>
        </div>
    </div>

    {{-- Verification QR --}}
    @if(isset($qrCode) && $qrCode)
        <div class="verification-section">
            <div class="title">🔒 VERIFICACIÓN</div>
            <div class="subtitle">Escanea para verificar autenticidad</div>
            <img src="{{ $qrCode }}" alt="QR Verificación">
            @if(isset($verificationUrl) && $verificationUrl)
                <div class="url">{{ $verificationUrl }}</div>
            @endif
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="important">NOTA DE CRÉDITO</div>
        <div class="warning">
            Los productos devueltos han sido<br>reintegrados al inventario
        </div>
        <div style="margin-top: 5px;">{{ $tenant->name }}</div>
        <div class="small" style="margin-top: 3px;">
            Este documento certifica la devolución<br>
            y el reembolso de los productos mencionados
        </div>
    </div>
</body>
</html>
