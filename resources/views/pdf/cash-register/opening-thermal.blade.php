<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Apertura de Caja #{{ $opening->opening_number }}</title>
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
        
        /* Info Section */
        .info-section {
            margin: 8px 0;
            padding: 5px 0;
            padding-left: 0;
            padding-right: 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .info-section .row {
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            margin-bottom: 1px;
            max-width: 100%;
        }
        .info-section strong {
            white-space: nowrap;
        }
        
        /* Balance Section */
        .balance-section {
            margin: 8px 0;
            padding: 8px 0;
            background: #f5f5f5;
            text-align: center;
        }
        .balance-label {
            font-size: 8pt;
            margin-bottom: 4px;
            color: #666;
        }
        .balance-amount {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
        }
        
        /* Notes Section */
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
        
        /* Verification Section */
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
        }
        .footer .important {
            font-weight: bold;
            font-size: 7pt;
            margin-bottom: 2px;
            color: #d32f2f;
        }
        .footer .warning {
            font-size: 6pt;
            margin-top: 4px;
            color: #666;
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
        <div class="document-title">APERTURA DE CAJA</div>
        <div class="document-number">#{{ $opening->opening_number }}</div>
        <div class="date-time">{{ $opening->opened_at->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- INFO SECTION -->
    <div class="info-section">
        <div class="row">
            <strong>Cajero:</strong>
            <span>{{ $opening->openedBy->name }}</span>
        </div>
        <div class="row">
            <strong>Estado:</strong>
            <span>{{ $opening->status === 'open' ? 'ABIERTA' : 'CERRADA' }}</span>
        </div>
        <div class="row">
            <strong>Fecha Apertura:</strong>
            <span>{{ $opening->opened_at->format('d/m/Y') }}</span>
        </div>
        <div class="row">
            <strong>Hora Apertura:</strong>
            <span>{{ $opening->opened_at->format('H:i:s') }}</span>
        </div>
    </div>

    <!-- BALANCE -->
    <div class="balance-section">
        <div class="balance-label">SALDO INICIAL</div>
        <div class="balance-amount">${{ number_format($opening->opening_balance, 2) }}</div>
    </div>

    <!-- NOTES -->
    @if($opening->notes)
    <div class="notes-section">
        <div class="notes-title">OBSERVACIONES:</div>
        <div class="notes-content">{{ $opening->notes }}</div>
    </div>
    @endif

    <!-- VERIFICATION -->
    @if($qrCode && $verificationUrl)
    <div class="verification-section">
        <div class="title">🔒 VERIFICACIÓN</div>
        <div class="subtitle">Escanea para verificar autenticidad</div>
        <img src="{{ $qrCode }}" alt="QR Verification" class="qr-code">
        <div class="url">{{ $verificationUrl }}</div>
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <div class="important">¡CONSERVE ESTE COMPROBANTE!</div>
        <div class="warning">
            Este documento es de uso interno.<br>
            Requiere verificación con permisos adecuados.
        </div>
        <div style="margin-top: 4px; font-size: 5pt; color: #999;">
            Documento generado electrónicamente<br>
            {{ $tenant->name }}
        </div>
    </div>
</body>
</html>
