<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Apertura de Caja #{{ $opening->opening_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #666;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            margin: 15px 0 5px 0;
        }
        .document-number {
            font-size: 14pt;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .balance-box {
            background: #f8f9fa;
            border: 2px solid #000;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .balance-label {
            font-size: 12pt;
            color: #666;
            margin-bottom: 10px;
        }
        .balance-amount {
            font-size: 28pt;
            font-weight: bold;
            color: #000;
        }
        .notes-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .verification-box {
            margin: 30px 0;
            padding: 20px;
            border: 3px solid #000;
            text-align: center;
        }
        .verification-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .qr-code {
            margin: 15px auto;
            display: block;
        }
        .verification-url {
            font-size: 8pt;
            word-break: break-all;
            color: #666;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }
        .signature-box {
            text-align: center;
            width: 40%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
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
        <div class="document-title">APERTURA DE CAJA</div>
        <div class="document-number">#{{ $opening->opening_number }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td>Cajero:</td>
            <td>{{ $opening->openedBy->name }}</td>
        </tr>
        <tr>
            <td>Estado:</td>
            <td><strong>{{ $opening->status === 'open' ? 'ABIERTA' : 'CERRADA' }}</strong></td>
        </tr>
        <tr>
            <td>Fecha de Apertura:</td>
            <td>{{ $opening->opened_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Hora de Apertura:</td>
            <td>{{ $opening->opened_at->format('H:i:s') }}</td>
        </tr>
    </table>

    <div class="balance-box">
        <div class="balance-label">SALDO INICIAL</div>
        <div class="balance-amount">${{ number_format($opening->opening_balance, 2) }}</div>
    </div>

    @if($opening->notes)
    <div class="notes-section">
        <div class="notes-title">OBSERVACIONES:</div>
        <div>{{ $opening->notes }}</div>
    </div>
    @endif

    @if($qrCode && $verificationUrl)
    <div class="verification-box">
        <div class="verification-title">🔒 VERIFICACIÓN DE AUTENTICIDAD</div>
        <p style="margin: 10px 0; font-size: 10pt; color: #666;">
            Escanee el código QR o visite la URL para verificar este documento
        </p>
        <img src="{{ $qrCode }}" alt="QR Verification" class="qr-code" style="width: 150px; height: 150px;">
        <div class="verification-url">{{ $verificationUrl }}</div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                Firma del Cajero<br>
                {{ $opening->openedBy->name }}
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Firma del Supervisor
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>DOCUMENTO DE USO INTERNO</strong></p>
        <p>Este documento requiere verificación con permisos adecuados</p>
        <p style="margin-top: 10px; font-size: 8pt;">
            Documento generado electrónicamente - {{ $tenant->name }}<br>
            Generado el {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>
</body>
</html>
