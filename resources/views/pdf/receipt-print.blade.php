<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante #{{ $sale->invoice_number }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
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
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h2 {
            font-size: 20px;
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
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .totals {
            margin-top: 30px;
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; padding: 10px; background: #f0f0f0; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px;">
            🖨️ Imprimir
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; margin-left: 10px;">
            ✕ Cerrar
        </button>
    </div>

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
        <div style="font-weight: bold; margin-top: 10px;">COMPROBANTE DE VENTA (No Fiscal)</div>
        <div style="font-size: 16px; font-weight: bold; margin: 10px 0;">#{{ $sale->invoice_number }}</div>
        <div>{{ $sale->created_at->format('d/m/Y H:i') }}</div>
    </div>

    @if($sale->customer)
        <div style="margin: 20px 0; background: #f5f5f5; padding: 15px;">
            <div><strong>Cliente:</strong> {{ $sale->customer->name }}</div>
            @if($sale->customer->document_number)
                <div><strong>{{ $sale->customer->document_type }}:</strong> {{ $sale->customer->document_number }}</div>
            @endif
        </div>
    @endif

    @if($sale->user)
        <div style="margin: 15px 0; background: #f9f9f9; padding: 10px;">
            <div><strong>Atendido por:</strong> {{ $sale->user->name }}</div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">P. Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div style="display: inline-block; text-align: right; min-width: 300px;">
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <strong>Subtotal (Neto):</strong> ${{ number_format($sale->subtotal, 2) }}
            </div>
            @if($sale->tax_amount > 0)
                <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                    <strong>IVA:</strong> ${{ number_format($sale->tax_amount, 2) }}
                </div>
            @endif
            <div style="font-size: 18px; font-weight: bold; padding: 10px 0; border-top: 3px solid #333; margin-top: 10px;">
                <strong>TOTAL:</strong> ${{ number_format($sale->total, 2) }}
            </div>
            <div style="padding: 5px 0; margin-top: 15px; border-top: 1px solid #ddd;">
                <strong>Pago:</strong> {{ ucfirst($sale->payment_method) }}
            </div>
            @if($sale->payment_method === 'cash')
                <div style="padding: 5px 0;">
                    <strong>Recibido:</strong> ${{ number_format($sale->amount_paid, 2) }}
                </div>
                <div style="padding: 5px 0;">
                    <strong>Cambio:</strong> ${{ number_format($sale->change_amount, 2) }}
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

    <div class="footer">
        <p>Gracias por su compra</p>
        <p style="font-weight: bold; color: #999; margin-top: 10px;">Este comprobante NO es válido como factura fiscal</p>
    </div>

    <script>
        // Auto-imprimir al cargar (opcional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
