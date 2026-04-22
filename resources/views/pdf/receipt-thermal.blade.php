<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $sale->invoice_number }}</title>
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
        .customer-section,
        .items-section,
        .totals-section,
        .payment-section,
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
            font-size: 7pt;
            font-weight: bold;
            margin: 4px 0 2px 0;
            text-transform: uppercase;
        }
        .invoice-number {
            font-size: 10pt;
            font-weight: bold;
            margin: 2px 0;
        }
        .date-time {
            font-size: 7pt;
            margin-bottom: 2px;
        }
        
        /* Customer Info */
        .customer-section {
            margin: 8px 0;
            padding: 5px 0;
            padding-left: 0;
            padding-right: 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            max-width: 100%;
            overflow: hidden;
        }
        .customer-section .row {
            font-size: 7pt;
            margin-bottom: 1px;
            max-width: 100%;
            overflow: hidden;
        }
        .customer-section strong {
            font-weight: bold;
            white-space: nowrap;
        }
        
        /* Items Table */
        .items-section {
            margin: 6px 0;
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
            margin-bottom: 1px;
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
        }
        
        /* Payment Info */
        .payment-section {
            margin: 6px 0;
            padding: 4px 0;
            padding-left: 0;
            padding-right: 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            max-width: 100%;
            overflow: hidden;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            padding: 1px 0;
            max-width: 100%;
            overflow: hidden;
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
        .footer .thank-you {
            font-weight: bold;
            font-size: 7pt;
            margin-bottom: 2px;
        }
        .footer .warning {
            font-weight: bold;
            margin: 3px 0;
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
        <div class="doc-type">Comprobante de Venta</div>
        <div class="small">(No Fiscal)</div>
        <div class="invoice-number">#{{ $sale->invoice_number }}</div>
        <div class="date-time">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Customer Info --}}
    @if($sale->customer)
        <div class="customer-section">
            <div class="row">
                <strong>Cliente:</strong> {{ $sale->customer->name }}
            </div>
            @if($sale->customer->document_number)
                <div class="row">
                    <strong>{{ $sale->customer->document_type }}:</strong> {{ $sale->customer->document_number }}
                </div>
            @endif
        </div>
    @endif

    {{-- Cashier Info --}}
    @if($sale->user)
        <div class="row small" style="margin: 5px 0;">
            <strong>Atendido por:</strong> {{ $sale->user->name }}
        </div>
    @endif

    {{-- Items --}}
    <div class="items-section">
        <div class="items-header">
            <span>ARTÍCULO</span>
            <span>IMPORTE</span>
        </div>
        @foreach($sale->items as $item)
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
            <span>${{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($sale->tax_amount > 0)
            <div class="total-row">
                <span>IVA:</span>
                <span>${{ number_format($sale->tax_amount, 2) }}</span>
            </div>
        @endif
        @if($sale->discount_amount > 0)
            <div class="total-row">
                <span>Descuento:</span>
                <span>-${{ number_format($sale->discount_amount, 2) }}</span>
            </div>
        @endif
        <div class="total-row final">
            <span>TOTAL:</span>
            <span>${{ number_format($sale->total, 2) }}</span>
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="payment-section">
        <div class="payment-row">
            <span class="bold">Método de Pago:</span>
            <span>{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</span>
        </div>
        @if($sale->payment_method === 'cash')
            <div class="payment-row">
                <span>Recibido:</span>
                <span>${{ number_format($sale->amount_paid, 2) }}</span>
            </div>
            <div class="payment-row">
                <span>Cambio:</span>
                <span>${{ number_format($sale->change_amount, 2) }}</span>
            </div>
        @endif
        @if($sale->transaction_reference)
            <div class="payment-row small">
                <span>Referencia:</span>
                <span>{{ $sale->transaction_reference }}</span>
            </div>
        @endif
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
        <div class="thank-you">¡GRACIAS POR SU COMPRA!</div>
        <div class="warning">COMPROBANTE NO FISCAL</div>
        <div style="margin-top: 5px;">{{ $tenant->name }}</div>
        <div class="small" style="margin-top: 3px;">
            Documento generado electrónicamente
        </div>
    </div>
</body>
</html>
