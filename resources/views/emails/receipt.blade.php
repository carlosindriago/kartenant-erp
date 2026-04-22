<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ $tenant->name }}</h1>
        @if($tenant->address)
            <p style="color: #e0e0e0; margin: 5px 0 0 0; font-size: 13px;">{{ $tenant->address }}</p>
        @endif
        <p style="color: #e0e0e0; margin: 10px 0 0 0;">Comprobante de Venta</p>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Estimado/a <strong>{{ $sale->customer->name }}</strong>,</p>
        
        <p>Gracias por su compra. Adjunto encontrará el comprobante de su venta.</p>

        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #22c55e;">
            <h3 style="margin-top: 0; color: #22c55e;">Resumen de Compra</h3>
            <table style="width: 100%; margin: 15px 0;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;"><strong>Número de Comprobante:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">#{{ $sale->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;"><strong>Fecha:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;"><strong>Subtotal:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">${{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                @if($sale->tax_amount > 0)
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;"><strong>IVA:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">${{ number_format($sale->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 12px 0; font-size: 18px;"><strong>TOTAL:</strong></td>
                    <td style="padding: 12px 0; text-align: right; font-size: 18px; color: #22c55e; font-weight: bold;">${{ number_format($sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-top: 1px solid #e0e0e0;"><strong>Método de Pago:</strong></td>
                    <td style="padding: 8px 0; border-top: 1px solid #e0e0e0; text-align: right;">{{ ucfirst($sale->payment_method) }}</td>
                </tr>
            </table>
        </div>

        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0; font-size: 12px; color: #856404;">
                <strong>Nota:</strong> Este comprobante NO es válido como factura fiscal. Es un comprobante interno de venta.
            </p>
        </div>

        <p>Si tiene alguna pregunta sobre su compra, no dude en contactarnos.</p>

        <p style="margin-top: 30px;">Saludos cordiales,<br>
        <strong>{{ $tenant->name }}</strong></p>
    </div>

    <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
        <p>Este es un correo automático, por favor no responda a este mensaje.</p>
        <p style="margin-top: 10px;">© {{ date('Y') }} {{ $tenant->name }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>
