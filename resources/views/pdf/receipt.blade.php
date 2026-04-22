<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $sale->invoice_number }}</title>
    <style>
        /* Formato Ticket Térmico 80mm */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            width: 80mm;
            padding: 10mm 5mm;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h2 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #000;
        }
        .header .document-type {
            font-size: 14px;
            font-weight: bold;
            color: #666;
            margin: 5px 0;
        }
        .header .invoice-number {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin: 10px 0;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #333;
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
            background-color: #f9f9f9;
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
            border-top: 3px solid #333;
            margin-top: 10px;
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
            color: #999;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
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
        <div class="document-type">COMPROBANTE DE VENTA (No Fiscal)</div>
        <div class="invoice-number">#{{ $sale->invoice_number }}</div>
        <div>{{ $sale->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Customer Info --}}
    @if($sale->customer)
        <div class="customer-info">
            <div class="info-row">
                <strong>Cliente:</strong> {{ $sale->customer->name }}
            </div>
            @if($sale->customer->document_number)
                <div class="info-row">
                    <strong>{{ $sale->customer->document_type }}:</strong> {{ $sale->customer->document_number }}
                </div>
            @endif
            @if($sale->customer->email)
                <div class="info-row">
                    <strong>Email:</strong> {{ $sale->customer->email }}
                </div>
            @endif
            @if($sale->customer->phone)
                <div class="info-row">
                    <strong>Teléfono:</strong> {{ $sale->customer->phone }}
                </div>
            @endif
        </div>
    @endif

    {{-- Cashier Info --}}
    @if($sale->user)
        <div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 5px;">
            <div class="info-row">
                <strong>Atendido por:</strong> {{ $sale->user->name }}
            </div>
        </div>
    @endif

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Producto</th>
                <th class="text-center" style="width: 15%;">Cantidad</th>
                <th class="text-right" style="width: 17.5%;">Precio Unit.</th>
                <th class="text-right" style="width: 17.5%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->product && $item->product->sku)
                            <br><small style="color: #666;">Código: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="totals-table">
            <div class="totals-row">
                <strong>Subtotal (Neto):</strong>
                <span>${{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->tax_amount > 0)
                <div class="totals-row">
                    <strong>IVA:</strong>
                    <span>${{ number_format($sale->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="totals-row total-final">
                <strong>TOTAL:</strong>
                <span>${{ number_format($sale->total, 2) }}</span>
            </div>
            <div class="totals-row" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                <strong>Método de Pago:</strong>
                <span>{{ ucfirst($sale->payment_method) }}</span>
            </div>
            @if($sale->payment_method === 'cash')
                <div class="totals-row">
                    <strong>Pagado:</strong>
                    <span>${{ number_format($sale->amount_paid, 2) }}</span>
                </div>
                <div class="totals-row">
                    <strong>Cambio:</strong>
                    <span>${{ number_format($sale->change_amount, 2) }}</span>
                </div>
            @endif
            @if($sale->transaction_reference)
                <div class="totals-row">
                    <strong>Referencia:</strong>
                    <span style="font-size: 10px;">{{ $sale->transaction_reference }}</span>
                </div>
            @endif
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
        <p>Gracias por su compra</p>
        <p class="warning">Este comprobante NO es válido como factura fiscal</p>
        <p style="margin-top: 10px;">{{ $tenant->name }}</p>
    </div>
</body>
</html>
