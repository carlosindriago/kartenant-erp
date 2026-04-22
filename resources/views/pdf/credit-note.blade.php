<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de Crédito #{{ $saleReturn->return_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #d97706;
            padding-bottom: 15px;
            margin-bottom: 20px;
            background: linear-gradient(to bottom, #fef3c7, #fff);
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .header h2 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #000;
        }
        .header .document-type {
            font-size: 16px;
            font-weight: bold;
            color: #d97706;
            margin: 10px 0;
            text-transform: uppercase;
            border: 2px solid #d97706;
            display: inline-block;
            padding: 8px 20px;
            border-radius: 5px;
            background: white;
        }
        .header .return-number {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin: 10px 0;
        }
        .alert-box {
            background: #fef3c7;
            border-left: 5px solid #d97706;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .alert-box strong {
            color: #d97706;
            display: block;
            margin-bottom: 5px;
        }
        .original-sale-info {
            margin: 20px 0;
            background: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 5px solid #3b82f6;
        }
        .original-sale-info .title {
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .customer-info {
            margin: 20px 0;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        .customer-info strong {
            color: #000;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .reason-section {
            background: #fff7ed;
            border: 1px dashed #d97706;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .reason-section .title {
            font-weight: bold;
            color: #d97706;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #d97706;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #fef3c7;
        }
        .item-reason {
            font-size: 10px;
            color: #666;
            font-style: italic;
            margin-top: 3px;
        }
        .totals {
            margin-top: 30px;
            text-align: right;
        }
        .totals-table {
            display: inline-block;
            text-align: right;
            min-width: 300px;
        }
        .totals-row {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .totals-row strong {
            display: inline-block;
            width: 150px;
            text-align: right;
            padding-right: 20px;
        }
        .totals-row span {
            display: inline-block;
            width: 120px;
            text-align: right;
        }
        .total-final {
            font-size: 18px;
            font-weight: bold;
            padding: 10px 0;
            border-top: 3px solid #d97706;
            margin-top: 10px;
            color: #d97706;
        }
        .refund-info {
            background: #dcfce7;
            border-left: 5px solid #16a34a;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .refund-info .title {
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .footer .warning {
            margin-top: 10px;
            font-weight: bold;
            color: #d97706;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }
        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h2>{{ $tenant->name }}</h2>
        @if($tenant->address)
            <div style="font-size: 11px; color: #666; margin-top: 3px;">{{ $tenant->address }}</div>
        @endif
        @if($tenant->phone)
            <div style="font-size: 11px; color: #666;">Tel: {{ $tenant->phone }}</div>
        @endif
        @if($tenant->cuit)
            <div style="font-size: 11px; color: #666;">CUIT: {{ $tenant->cuit }}</div>
        @endif
        <div style="margin: 15px 0;">
            <div class="document-type">📄 NOTA DE CRÉDITO</div>
        </div>
        <div class="return-number">#{{ $saleReturn->return_number }}</div>
        <div>{{ $saleReturn->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Alert Box --}}
    <div class="alert-box">
        <strong>🔄 DOCUMENTO DE DEVOLUCIÓN</strong>
        Este documento certifica la devolución de productos y el reembolso correspondiente.
        Los productos devueltos han sido reintegrados al inventario.
    </div>

    {{-- Original Sale Reference --}}
    <div class="original-sale-info">
        <div class="title">📋 REFERENCIA A VENTA ORIGINAL</div>
        <div class="info-row">
            <strong>Factura Original:</strong> {{ $saleReturn->originalSale->invoice_number }}
        </div>
        <div class="info-row">
            <strong>Fecha de Venta:</strong> {{ $saleReturn->originalSale->created_at->format('d/m/Y H:i') }}
        </div>
        <div class="info-row">
            <strong>Total de Venta Original:</strong> ${{ number_format($saleReturn->originalSale->total, 2) }}
        </div>
        <div class="info-row">
            <strong>Tipo de Devolución:</strong> 
            <span class="badge {{ $saleReturn->return_type === 'full' ? 'badge-warning' : 'badge-success' }}">
                {{ $saleReturn->return_type === 'full' ? 'COMPLETA' : 'PARCIAL' }}
            </span>
        </div>
    </div>

    {{-- Customer Info --}}
    @if($saleReturn->originalSale->customer)
        <div class="customer-info">
            <div class="info-row">
                <strong>Cliente:</strong> {{ $saleReturn->originalSale->customer->name }}
            </div>
            @if($saleReturn->originalSale->customer->document_number)
                <div class="info-row">
                    <strong>{{ $saleReturn->originalSale->customer->document_type }}:</strong> {{ $saleReturn->originalSale->customer->document_number }}
                </div>
            @endif
            @if($saleReturn->originalSale->customer->email)
                <div class="info-row">
                    <strong>Email:</strong> {{ $saleReturn->originalSale->customer->email }}
                </div>
            @endif
            @if($saleReturn->originalSale->customer->phone)
                <div class="info-row">
                    <strong>Teléfono:</strong> {{ $saleReturn->originalSale->customer->phone }}
                </div>
            @endif
        </div>
    @endif

    {{-- Reason --}}
    @if($saleReturn->reason)
        <div class="reason-section">
            <div class="title">📝 RAZÓN DE LA DEVOLUCIÓN</div>
            <div>{{ $saleReturn->reason }}</div>
        </div>
    @endif

    {{-- Returned Items Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Producto Devuelto</th>
                <th class="text-center" style="width: 15%;">Cantidad</th>
                <th class="text-right" style="width: 17.5%;">Precio Unit.</th>
                <th class="text-right" style="width: 17.5%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReturn->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->product && $item->product->sku)
                            <br><small style="color: #666;">Código: {{ $item->product->sku }}</small>
                        @endif
                        @if($item->return_reason)
                            <div class="item-reason">
                                ⚠️ Razón: {{ $item->return_reason }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-table">
            <div class="totals-row">
                <strong>Subtotal Devuelto:</strong>
                <span>${{ number_format($saleReturn->subtotal, 2) }}</span>
            </div>
            @if($saleReturn->tax_amount > 0)
                <div class="totals-row">
                    <strong>IVA Devuelto:</strong>
                    <span>${{ number_format($saleReturn->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="totals-row total-final">
                <strong>TOTAL A REEMBOLSAR:</strong>
                <span>${{ number_format($saleReturn->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Refund Info --}}
    <div class="refund-info">
        <div class="title">💰 INFORMACIÓN DEL REEMBOLSO</div>
        <div class="info-row">
            <strong>Método de Reembolso:</strong> 
            @switch($saleReturn->refund_method)
                @case('cash')
                    💵 Efectivo
                    @break
                @case('card')
                    💳 Tarjeta
                    @break
                @case('transfer')
                    🏦 Transferencia Bancaria
                    @break
                @case('credit_note')
                    📄 Nota de Crédito para Compras Futuras
                    @break
                @default
                    {{ ucfirst($saleReturn->refund_method) }}
            @endswitch
        </div>
        <div class="info-row">
            <strong>Estado:</strong> 
            <span class="badge badge-success">{{ strtoupper($saleReturn->status) }}</span>
        </div>
        <div class="info-row">
            <strong>Procesado por:</strong> {{ $saleReturn->processedBy->name }}
        </div>
        <div class="info-row">
            <strong>Fecha de Procesamiento:</strong> {{ $saleReturn->processed_at->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Verification Section --}}
    @if(isset($qrCode) && $qrCode)
        <div style="margin-top: 40px; padding: 20px; background: #f0f7ff; border: 2px solid #3b82f6; border-radius: 8px; text-align: center;">
            <h3 style="font-size: 14px; color: #1e40af; margin-bottom: 10px; font-weight: bold;">
                🔒 VERIFICACIÓN DE AUTENTICIDAD
            </h3>
            <p style="font-size: 11px; color: #475569; margin-bottom: 15px;">
                Escanea este código QR para verificar la autenticidad de este documento
            </p>
            <div style="margin: 15px auto; text-align: center;">
                <img src="{{ $qrCode }}" alt="QR de Verificación" style="width: 120px; height: 120px; border: 3px solid #3b82f6; border-radius: 8px; padding: 5px; background: white;">
            </div>
            @if(isset($verificationUrl) && $verificationUrl)
                <p style="font-size: 9px; color: #64748b; margin-top: 10px; word-break: break-all;">
                    {{ $verificationUrl }}
                </p>
            @endif
            <p style="font-size: 10px; color: #475569; margin-top: 10px; font-weight: bold;">
                ✓ Documento verificable y protegido con hash SHA-256
            </p>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p><strong>IMPORTANTE:</strong> Este documento certifica la devolución y el reembolso de los productos mencionados.</p>
        <p class="warning">Los productos devueltos han sido reintegrados al inventario.</p>
        <p style="margin-top: 10px;">{{ $tenant->name }}</p>
        <p style="margin-top: 5px; font-size: 9px;">
            Este documento fue generado electrónicamente y es válido como comprobante de devolución.
        </p>
    </div>
</body>
</html>
