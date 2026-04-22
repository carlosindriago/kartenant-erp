<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de Caja #{{ $closing->closing_number }}</title>
    <style>
        /* ===== DISEÑO PROFESIONAL Y COMPACTO ===== */
        @page {
            size: A4;
            margin: 1.5cm 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }
        
        /* Header Compacto */
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 3px;
            color: #2c3e50;
        }
        .company-info {
            font-size: 7.5pt;
            color: #7f8c8d;
            line-height: 1.2;
        }
        .document-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 8px 0 3px 0;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        .document-number {
            font-size: 11pt;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        /* Tabla de Información Compacta */
        .info-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .info-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #ecf0f1;
        }
        .info-table td:first-child {
            font-weight: 600;
            width: 28%;
            color: #34495e;
        }
        
        /* Caja de Resumen Compacta */
        .summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-left: 3px solid #3498db;
            padding: 8px 12px;
            margin: 10px 0;
        }
        .summary-title {
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 6px;
            text-align: center;
            color: #2c3e50;
        }
        
        /* Tabla de Totales Compacta */
        .totals-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .totals-table tr {
            border-bottom: 1px solid #ecf0f1;
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .totals-table tr.highlight {
            background: #f8f9fa;
            font-weight: 600;
        }
        .totals-table tr.main {
            background: #e9ecef;
            font-size: 9.5pt;
            font-weight: bold;
        }
        .totals-table tr.difference {
            background: #2c3e50;
            color: #fff;
            font-size: 11pt;
            font-weight: bold;
        }
        .totals-table tr.difference.positive {
            background: #27ae60;
        }
        .totals-table tr.difference.negative {
            background: #e74c3c;
        }
        
        /* Alerta de Discrepancia Compacta */
        .discrepancy-alert {
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .discrepancy-alert.positive {
            background: #d4edda;
            border: 2px solid #27ae60;
            color: #155724;
        }
        .discrepancy-alert.negative {
            background: #f8d7da;
            border: 2px solid #e74c3c;
            color: #721c24;
        }
        
        /* Sección de Notas Compacta */
        .notes-section {
            margin: 10px 0;
            padding: 8px 10px;
            background: #f8f9fa;
            border-left: 3px solid #3498db;
            font-size: 8pt;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 8.5pt;
        }
        
        /* Caja de Verificación Compacta */
        .verification-box {
            margin: 12px 0;
            padding: 10px;
            border: 2px solid #2c3e50;
            text-align: center;
            background: #f8f9fa;
        }
        .verification-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 6px;
            color: #2c3e50;
        }
        .qr-code {
            margin: 8px auto;
            display: block;
        }
        .verification-url {
            font-size: 6.5pt;
            word-break: break-all;
            color: #7f8c8d;
            margin-top: 6px;
        }
        
        /* Badge de Estado Compacto */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin: 5px 0;
            font-size: 8pt;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Footer Compacto */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #2c3e50;
            text-align: center;
            font-size: 7pt;
            color: #7f8c8d;
        }
        
        /* Sección de Firmas Profesional */
        .signature-section {
            margin-top: 25px;
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .signature-box {
            display: table-cell;
            text-align: center;
            vertical-align: bottom;
            width: 33.33%;
            padding: 0 8px;
        }
        .signature-line {
            border-top: 1.5px solid #2c3e50;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 7.5pt;
            font-weight: 600;
            color: #34495e;
        }
        .signature-label {
            font-size: 7pt;
            color: #7f8c8d;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $tenant->name }}</div>
        @if($tenant->address)
        <div class="company-info">{{ $tenant->address }}</div>
        @endif
        @if($tenant->phone)
        <div class="company-info">Tel: {{ $tenant->phone }}</div>
        @endif
        <div class="document-title">CIERRE DE CAJA</div>
        <div class="document-number">#{{ $closing->closing_number }}</div>
        <div style="margin-top: 10px;">
            <span class="status-badge {{ $closing->review_status === 'completed' ? 'approved' : ($closing->review_status === 'rejected' ? 'rejected' : 'pending') }}">
                @if($closing->review_status === 'completed')
                    ✓ COMPLETADO
                @elseif($closing->review_status === 'approved')
                    ✓ APROBADO
                @elseif($closing->review_status === 'rejected')
                    ✗ RECHAZADO
                @else
                    ⏱ EN PROCESO
                @endif
            </span>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td>Cajero:</td>
            <td>{{ $closing->closedBy->name }}</td>
        </tr>
        <tr>
            <td>Apertura Relacionada:</td>
            <td>{{ $opening->opening_number }}</td>
        </tr>
        <tr>
            <td>Fecha/Hora Apertura:</td>
            <td>{{ $opening->opened_at->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td>Fecha/Hora Cierre:</td>
            <td>{{ $closing->closed_at->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>

    <div class="summary-box">
        <div class="summary-title">RESUMEN DEL DÍA</div>
        <table style="width: 100%; margin-top: 5px; font-size: 8.5pt;">
            <tr>
                <td style="padding: 2px 0;"><strong>Total de Transacciones:</strong></td>
                <td style="text-align: right; padding: 2px 0; font-weight: 600;">{{ $closing->total_transactions }}</td>
            </tr>
            <tr>
                <td style="padding: 2px 0;"><strong>Ticket Promedio:</strong></td>
                <td style="text-align: right; padding: 2px 0; font-weight: 600; color: #27ae60;">${{ number_format($closing->average_ticket, 2) }}</td>
            </tr>
        </table>
    </div>

    <table class="totals-table">
        <tr>
            <td>Saldo Inicial</td>
            <td>${{ number_format($closing->opening_balance, 2) }}</td>
        </tr>
        <tr class="highlight">
            <td>Total Ventas del Día</td>
            <td>${{ number_format($closing->total_sales, 2) }}</td>
        </tr>
        <tr>
            <td style="padding-left: 30px;">▸ Efectivo</td>
            <td>${{ number_format($closing->total_cash, 2) }}</td>
        </tr>
        <tr>
            <td style="padding-left: 30px;">▸ Tarjeta</td>
            <td>${{ number_format($closing->total_card, 2) }}</td>
        </tr>
        <tr>
            <td style="padding-left: 30px;">▸ Otros Métodos</td>
            <td>${{ number_format($closing->total_other, 2) }}</td>
        </tr>
        <tr class="main">
            <td>Saldo Esperado</td>
            <td>${{ number_format($closing->expected_balance, 2) }}</td>
        </tr>
        <tr class="main">
            <td>Saldo Real Contado</td>
            <td>${{ number_format($closing->closing_balance, 2) }}</td>
        </tr>
        <tr class="difference {{ $closing->difference >= 0 ? 'positive' : 'negative' }}">
            <td>DIFERENCIA</td>
            <td>${{ number_format($closing->difference, 2) }}</td>
        </tr>
    </table>

    @if($closing->hasDiscrepancy())
    <div class="discrepancy-alert {{ $closing->difference > 0 ? 'positive' : 'negative' }}">
        ⚠ {{ $closing->difference > 0 ? 'SOBRANTE DETECTADO' : 'FALTANTE DETECTADO' }}
        <br>
        <span style="font-size: 14pt;">
            {{ $closing->difference > 0 ? 'Sobran' : 'Faltan' }} 
            ${{ number_format(abs($closing->difference), 2) }}
        </span>
    </div>
    @endif

    @if($closing->notes)
    <div class="notes-section">
        <div class="notes-title">OBSERVACIONES:</div>
        <div>{{ $closing->notes }}</div>
    </div>
    @endif

    @if($closing->discrepancy_notes)
    <div class="notes-section" style="border-left-color: #dc3545;">
        <div class="notes-title">NOTA DE DISCREPANCIA:</div>
        <div>{{ $closing->discrepancy_notes }}</div>
    </div>
    @endif

    <!-- TRANSACTIONS LIST - COMPACTO -->
    @if(isset($transactions) && count($transactions) > 0)
    <div style="margin-top: 12px; page-break-inside: avoid;">
        <h3 style="text-align: center; color: #2c3e50; margin-bottom: 6px; font-size: 10pt; border-bottom: 2px solid #2c3e50; padding-bottom: 4px; font-weight: bold;">
            📋 DETALLE DE TRANSACCIONES
        </h3>
        
        <table style="width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 8px;">
            <thead>
                <tr style="background: #34495e; color: #fff; border-bottom: 2px solid #2c3e50;">
                    <th style="padding: 4px 5px; text-align: left; width: 4%;">#</th>
                    <th style="padding: 4px 5px; text-align: left; width: 14%;">Factura</th>
                    <th style="padding: 4px 5px; text-align: left; width: 9%;">Hora</th>
                    <th style="padding: 4px 5px; text-align: left; width: 18%;">Cliente</th>
                    <th style="padding: 4px 5px; text-align: left; width: 13%;">Pago</th>
                    <th style="padding: 4px 5px; text-align: right; width: 14%;">Monto</th>
                    <th style="padding: 4px 5px; text-align: center; width: 18%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $index => $transaction)
                <tr style="border-bottom: 1px solid #ecf0f1; {{ $transaction['is_cancelled'] ? 'background: #ffebee;' : 'background: #fff;' }}">
                    <td style="padding: 3px 5px; text-align: left; color: #7f8c8d;">{{ $index + 1 }}</td>
                    <td style="padding: 3px 5px; font-weight: 600; color: #2c3e50;">#{{ $transaction['invoice_number'] }}</td>
                    <td style="padding: 3px 5px; color: #34495e;">{{ $transaction['time'] }}</td>
                    <td style="padding: 3px 5px; font-size: 7pt; color: #34495e;">{{ $transaction['customer'] }}</td>
                    <td style="padding: 3px 5px; color: #34495e;">{{ ucfirst($transaction['payment_method']) }}</td>
                    <td style="padding: 3px 5px; text-align: right; font-weight: 600; {{ $transaction['is_cancelled'] ? 'text-decoration: line-through; color: #e74c3c;' : 'color: #27ae60;' }}">
                        ${{ number_format($transaction['total'], 2) }}
                    </td>
                    <td style="padding: 3px 5px; text-align: center;">
                        @if($transaction['is_cancelled'])
                            <span style="background: #e74c3c; color: #fff; padding: 2px 5px; border-radius: 2px; font-size: 6.5pt; font-weight: bold;">
                                ✗ ANULADA
                            </span>
                        @else
                            <span style="background: #27ae60; color: #fff; padding: 2px 5px; border-radius: 2px; font-size: 6.5pt; font-weight: bold;">
                                ✓ OK
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #34495e; color: #fff; border-top: 2px solid #2c3e50; font-weight: bold; font-size: 8pt;">
                    <td colspan="5" style="padding: 5px; text-align: right;">TOTAL:</td>
                    <td style="padding: 5px; text-align: center;" colspan="2">
                        {{ count($transactions) }} transacciones
                        <span style="font-size: 7pt; font-weight: normal; color: #ecf0f1;">
                            ({{ $transactionsCompletedCount }} OK, {{ $transactionsCancelledCount }} anuladas)
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if($qrCode && $verificationUrl)
    <div class="verification-box">
        <div class="verification-title">🔒 VERIFICACIÓN DE AUTENTICIDAD</div>
        <p style="margin: 5px 0; font-size: 7.5pt; color: #7f8c8d;">
            Escanee el código QR para verificar este documento
        </p>
        <img src="{{ $qrCode }}" alt="QR Verification" class="qr-code" style="width: 100px; height: 100px;">
        <div class="verification-url">{{ $verificationUrl }}</div>
        @if($closing->verification_hash)
        <div style="margin-top: 6px; padding: 4px; background: #fff3cd; border: 1px solid #f39c12; border-radius: 2px;">
            <div style="font-size: 7pt; font-weight: bold; text-align: center; color: #856404;">
                🔐 HASH DE SEGURIDAD
            </div>
            <div style="font-size: 6pt; font-family: 'Courier New', monospace; word-break: break-all; text-align: center; color: #7f8c8d; margin-top: 2px;">
                {{ substr($closing->verification_hash, 0, 64) }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                {{ $closing->closedBy->name }}
            </div>
            <div class="signature-label">Firma del Cajero</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                &nbsp;
            </div>
            <div class="signature-label">Firma del Supervisor</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                &nbsp;
            </div>
            <div class="signature-label">Firma del Gerente</div>
        </div>
    </div>

    <div class="footer">
        <p style="font-weight: bold; font-size: 7.5pt;">DOCUMENTO DE USO INTERNO - CONFIDENCIAL</p>
        <p style="font-size: 6.5pt; margin-top: 3px;">Este documento requiere verificación con permisos adecuados</p>
        <p style="margin-top: 5px; font-size: 6pt; color: #95a5a6;">
            Documento generado electrónicamente por {{ $tenant->name }}<br>
            Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>
</body>
</html>
