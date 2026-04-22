<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de Caja #{{ $closing->closing_number }}</title>
    <style>
        /* ===== FORMATO TICKET TÉRMICO 80mm ===== */
        @page {
            size: 80mm auto;
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
        .summary-section,
        .totals-section,
        .notes-section,
        .verification-section,
        .footer {
            page-break-inside: avoid;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
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
        }
        .document-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 6px 0 2px 0;
        }
        .document-number {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .date-time {
            font-size: 7pt;
            margin-bottom: 2px;
        }
        
        .info-section {
            margin: 8px 0;
            padding: 5px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .info-section .row {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            margin-bottom: 1px;
        }
        .info-section strong {
            white-space: nowrap;
        }
        
        .totals-section {
            margin: 8px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            padding: 2px 0;
        }
        .total-row.main {
            font-size: 8pt;
            font-weight: bold;
            padding: 4px 0;
            border-top: 2px solid #000;
            margin-top: 2px;
        }
        .total-row.difference {
            font-size: 9pt;
            font-weight: bold;
            padding: 4px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin-top: 4px;
        }
        .total-row.difference.positive {
            color: #2e7d32;
        }
        .total-row.difference.negative {
            color: #c62828;
        }
        
        .summary-section {
            margin: 8px 0;
            padding: 6px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
        .summary-title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 4px;
            text-align: center;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            padding: 2px 0;
        }
        
        .notes-section {
            margin: 8px 0;
            padding: 6px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .notes-title {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .notes-content {
            font-size: 6pt;
            line-height: 1.4;
            color: #333;
        }
        
        .verification-section {
            text-align: center;
            margin: 8px 0;
            padding: 8px;
            border: 2px solid #000;
        }
        .verification-section .title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .verification-section .subtitle {
            font-size: 6pt;
            margin-bottom: 6px;
            color: #666;
        }
        .verification-section .qr-code {
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
        
        .footer {
            text-align: center;
            margin-top: 8px;
            margin-bottom: 0;
            padding-top: 6px;
            padding-bottom: 3mm;
            border-top: 2px dashed #000;
            font-size: 6pt;
        }
        .footer .important {
            font-weight: bold;
            font-size: 7pt;
            margin-bottom: 2px;
            color: #d32f2f;
        }
        .footer .status {
            font-size: 7pt;
            font-weight: bold;
            margin: 4px 0;
            padding: 4px;
            border-radius: 3px;
        }
        .footer .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .footer .status.approved {
            background: #d4edda;
            color: #155724;
        }
        .footer .status.rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="business-name">{{ $tenant->name }}</div>
        @if($tenant->address)
        <div class="business-info">{{ $tenant->address }}</div>
        @endif
        @if($tenant->phone)
        <div class="business-info">Tel: {{ $tenant->phone }}</div>
        @endif
        <div class="document-title">CIERRE DE CAJA</div>
        <div class="document-number">#{{ $closing->closing_number }}</div>
        <div class="date-time">{{ $closing->closed_at->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- INFO SECTION -->
    <div class="info-section">
        <div class="row">
            <strong>Cajero:</strong>
            <span>{{ $closing->closedBy->name }}</span>
        </div>
        <div class="row">
            <strong>Apertura:</strong>
            <span>{{ $opening->opening_number }}</span>
        </div>
        <div class="row">
            <strong>Inicio:</strong>
            <span>{{ $opening->opened_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <strong>Cierre:</strong>
            <span>{{ $closing->closed_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="summary-section">
        <div class="summary-title">RESUMEN DEL DÍA</div>
        <div class="summary-row">
            <strong>Total Transacciones:</strong>
            <span>{{ $closing->total_transactions }}</span>
        </div>
        <div class="summary-row">
            <strong>Ticket Promedio:</strong>
            <span>${{ number_format($closing->average_ticket, 2) }}</span>
        </div>
    </div>

    <!-- TOTALS -->
    <div class="totals-section">
        <div class="total-row">
            <span>Saldo Inicial:</span>
            <span>${{ number_format($closing->opening_balance, 2) }}</span>
        </div>
        <div class="total-row">
            <span>Total Ventas:</span>
            <span>${{ number_format($closing->total_sales, 2) }}</span>
        </div>
        <div class="total-row" style="border-top: 1px dashed #000; margin-top: 4px; padding-top: 4px;">
            <span>▸ Efectivo:</span>
            <span>${{ number_format($closing->total_cash, 2) }}</span>
        </div>
        <div class="total-row">
            <span>▸ Tarjeta:</span>
            <span>${{ number_format($closing->total_card, 2) }}</span>
        </div>
        <div class="total-row">
            <span>▸ Otros:</span>
            <span>${{ number_format($closing->total_other, 2) }}</span>
        </div>
        <div class="total-row main">
            <span>Saldo Esperado:</span>
            <span>${{ number_format($closing->expected_balance, 2) }}</span>
        </div>
        <div class="total-row main">
            <span>Saldo Real:</span>
            <span>${{ number_format($closing->closing_balance, 2) }}</span>
        </div>
        <div class="total-row difference {{ $closing->difference >= 0 ? 'positive' : 'negative' }}">
            <span>Diferencia:</span>
            <span>${{ number_format($closing->difference, 2) }}</span>
        </div>
    </div>

    @if($closing->hasDiscrepancy())
    <div style="text-align: center; padding: 6px; background: {{ $closing->difference > 0 ? '#e8f5e9' : '#ffebee' }}; margin: 8px 0; border-radius: 3px;">
        <div style="font-size: 7pt; font-weight: bold; color: {{ $closing->difference > 0 ? '#2e7d32' : '#c62828' }};">
            {{ $closing->difference > 0 ? '⚠ SOBRANTE DETECTADO' : '⚠ FALTANTE DETECTADO' }}
        </div>
    </div>
    @endif

    <!-- NOTES -->
    @if($closing->notes)
    <div class="notes-section">
        <div class="notes-title">OBSERVACIONES:</div>
        <div class="notes-content">{{ $closing->notes }}</div>
    </div>
    @endif

    @if($closing->discrepancy_notes)
    <div class="notes-section">
        <div class="notes-title">NOTA DE DISCREPANCIA:</div>
        <div class="notes-content">{{ $closing->discrepancy_notes }}</div>
    </div>
    @endif

    <!-- TRANSACTIONS LIST -->
    @if(isset($transactions) && count($transactions) > 0)
    <div style="margin-top: 10px; border-top: 2px solid #000; padding-top: 6px;">
        <div style="text-align: center; font-size: 7pt; font-weight: bold; margin-bottom: 4px;">
            DETALLE DE TRANSACCIONES
        </div>
        
        @foreach($transactions as $index => $transaction)
        <div style="margin-bottom: 4px; padding: 3px; background: {{ $transaction['is_cancelled'] ? '#ffebee' : '#f5f5f5' }}; border-left: 2px solid {{ $transaction['is_cancelled'] ? '#c62828' : '#2e7d32' }};">
            <div style="display: flex; justify-content: space-between; font-size: 6pt; font-weight: bold;">
                <span>{{ $index + 1 }}. #{{ $transaction['invoice_number'] }}</span>
                <span>{{ $transaction['time'] }}</span>
            </div>
            <div style="font-size: 5.5pt; margin-top: 1px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #666;">{{ ucfirst($transaction['payment_method']) }}</span>
                    <span style="font-weight: bold; {{ $transaction['is_cancelled'] ? 'text-decoration: line-through; color: #c62828;' : '' }}">
                        ${{ number_format($transaction['total'], 2) }}
                    </span>
                </div>
                @if($transaction['is_cancelled'])
                <div style="color: #c62828; font-weight: bold; font-size: 5pt;">
                    ❌ ANULADA
                </div>
                @endif
            </div>
        </div>
        @endforeach
        
        <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #999; font-size: 6pt; text-align: center; color: #666;">
            Total: {{ count($transactions) }} transacciones
            ({{ $transactionsCompletedCount }} completadas, {{ $transactionsCancelledCount }} anuladas)
        </div>
    </div>
    @endif

    <!-- QR CODE VERIFICATION -->
    @if($qrCode && $verificationUrl)
    <div class="verification-section">
        <div class="title">🔒 VERIFICACIÓN</div>
        <div class="subtitle">Escanea para verificar autenticidad</div>
        <img src="{{ $qrCode }}" alt="QR Verification" class="qr-code">
        <div class="verification-url" style="font-size: 5pt; margin-top: 3px; word-break: break-all; color: #666;">
            {{ $verificationUrl }}
        </div>
        @if($closing->verification_hash)
        <div style="margin-top: 6px; padding: 4px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 2px;">
            <div style="font-size: 5.5pt; font-weight: bold; text-align: center; margin-bottom: 2px;">
                🔐 CÓDIGO DE SEGURIDAD
            </div>
            <div style="font-size: 4.5pt; font-family: 'Courier New', monospace; word-break: break-all; text-align: center; line-height: 1.2;">
                {{ substr($closing->verification_hash, 0, 48) }}...
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <div class="important">¡CONSERVE ESTE COMPROBANTE!</div>
        
        <div class="status {{ $closing->review_status === 'completed' ? 'approved' : ($closing->review_status === 'rejected' ? 'rejected' : 'pending') }}">
            Estado: 
            @if($closing->review_status === 'completed')
                COMPLETADO ✓
            @elseif($closing->review_status === 'approved')
                APROBADO ✓
            @elseif($closing->review_status === 'rejected')
                RECHAZADO ✗
            @else
                EN PROCESO
            @endif
        </div>
        
        <div style="margin-top: 4px; font-size: 5pt; color: #999;">
            Documento generado electrónicamente<br>
            {{ $tenant->name }}
        </div>
    </div>
</body>
</html>
