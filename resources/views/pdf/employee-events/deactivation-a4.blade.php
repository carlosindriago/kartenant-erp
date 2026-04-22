<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Desactivación de Empleado - {{ $user->name }}</title>
    <style>
        /* ===== DISEÑO PROFESIONAL RRHH ===== */
        @page {
            size: A4;
            margin: 1.5cm 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }
        
        /* Header Corporativo */
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 12px;
            margin-bottom: 15px;
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
            font-size: 15pt;
            font-weight: bold;
            margin: 10px 0 5px 0;
            color: #e74c3c;
            letter-spacing: 0.5px;
        }
        .document-number {
            font-size: 10pt;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        /* Secciones de información */
        .info-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-left: 4px solid #e74c3c;
            padding: 12px;
            margin: 12px 0;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ecf0f1;
        }
        .info-label {
            font-weight: 600;
            color: #34495e;
            width: 35%;
        }
        .info-value {
            color: #2c3e50;
            width: 65%;
        }
        
        /* Alerta de desactivación */
        .deactivation-alert {
            background: #ffebee;
            border: 2px solid #e74c3c;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
            border-radius: 3px;
        }
        .alert-title {
            font-weight: bold;
            font-size: 10pt;
            color: #c62828;
            margin-bottom: 6px;
        }
        
        /* Verificación */
        .verification-box {
            margin: 15px 0;
            padding: 12px;
            border: 2px solid #2c3e50;
            text-align: center;
            background: #f8f9fa;
        }
        .verification-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .qr-code {
            margin: 10px auto;
            display: block;
        }
        .verification-url {
            font-size: 6.5pt;
            word-break: break-all;
            color: #7f8c8d;
            margin-top: 6px;
        }
        
        /* Firmas */
        .signature-section {
            margin-top: 30px;
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
            margin-top: 45px;
            padding-top: 5px;
            font-size: 8pt;
            font-weight: 600;
            color: #34495e;
        }
        .signature-label {
            font-size: 7pt;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #2c3e50;
            text-align: center;
            font-size: 7pt;
            color: #7f8c8d;
        }
        
        /* Sello oficial */
        .official-seal {
            background: #fff3cd;
            border: 2px solid #f39c12;
            padding: 8px;
            margin: 15px 0;
            text-align: center;
            border-radius: 3px;
        }
        .seal-title {
            font-weight: bold;
            font-size: 9pt;
            color: #856404;
            margin-bottom: 4px;
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
        <div class="document-title">❌ DESACTIVACIÓN DE EMPLEADO</div>
        <div class="document-number">Documento N° {{ $event->document_number }}</div>
        <div class="company-info" style="margin-top: 5px;">
            Fecha: {{ $event->changed_at->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- Alerta de Desactivación -->
    <div class="deactivation-alert">
        <div class="alert-title">⚠️ EMPLEADO DESACTIVADO</div>
        <div style="font-size: 8pt; color: #721c24;">
            El acceso al sistema ha sido revocado a partir de la fecha indicada
        </div>
    </div>

    <!-- Datos del Empleado -->
    <div class="info-box">
        <div class="section-title">📋 DATOS DEL EMPLEADO</div>
        <div class="info-row">
            <div class="info-label">Nombre Completo:</div>
            <div class="info-value">{{ $user->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $user->email }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Estado Anterior:</div>
            <div class="info-value">
                <span style="color: #27ae60; font-weight: bold;">● ACTIVO</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Estado Actual:</div>
            <div class="info-value">
                <span style="color: #e74c3c; font-weight: bold;">● INACTIVO</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha de Desactivación:</div>
            <div class="info-value">{{ $event->changed_at->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>

    <!-- Motivo de Desactivación -->
    <div class="info-box">
        <div class="section-title">📝 MOTIVO DE DESACTIVACIÓN</div>
        <div style="padding: 8px; background: #fff; border-radius: 2px; color: #2c3e50;">
            {{ $event->reason }}
        </div>
    </div>

    <!-- Responsable de la Desactivación -->
    <div class="info-box">
        <div class="section-title">👤 DESACTIVACIÓN REALIZADA POR</div>
        <div class="info-row">
            <div class="info-label">Responsable:</div>
            <div class="info-value">{{ $changedBy->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $changedBy->email }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha/Hora:</div>
            <div class="info-value">{{ $event->changed_at->format('d/m/Y H:i:s') }}</div>
        </div>
        @if($changedBy->roles->count() > 0)
        <div class="info-row">
            <div class="info-label">Rol:</div>
            <div class="info-value" style="font-weight: 600; color: #3498db;">
                {{ $changedBy->roles->first()->name }}
            </div>
        </div>
        @endif
    </div>

    <!-- Notas Adicionales -->
    @if($event->additional_notes)
    <div class="info-box">
        <div class="section-title">📌 OBSERVACIONES ADICIONALES</div>
        <div style="padding: 5px 0; color: #2c3e50;">
            {{ $event->additional_notes }}
        </div>
    </div>
    @endif

    <!-- Sello Oficial -->
    <div class="official-seal">
        <div class="seal-title">⚖️ DOCUMENTO OFICIAL - RECURSOS HUMANOS</div>
        <div style="font-size: 7pt; color: #856404;">
            Este documento certifica la desactivación del empleado en el sistema<br>
            y tiene validez legal ante auditorías laborales
        </div>
    </div>

    <!-- Verificación QR -->
    @if($qrCode && $verificationUrl)
    <div class="verification-box">
        <div class="verification-title">🔒 VERIFICACIÓN DE AUTENTICIDAD</div>
        <p style="margin: 5px 0; font-size: 7.5pt; color: #7f8c8d;">
            Escanee el código QR para verificar la autenticidad de este documento
        </p>
        <img src="{{ $qrCode }}" alt="QR Verification" class="qr-code" style="width: 100px; height: 100px;">
        <div class="verification-url">{{ $verificationUrl }}</div>
        @if($event->verification_hash)
        <div style="margin-top: 8px; padding: 6px; background: #fff3cd; border: 1px solid #f39c12; border-radius: 2px;">
            <div style="font-size: 7pt; font-weight: bold; text-align: center; color: #856404;">
                🔐 HASH DE SEGURIDAD
            </div>
            <div style="font-size: 6pt; font-family: 'Courier New', monospace; word-break: break-all; text-align: center; color: #7f8c8d; margin-top: 3px;">
                {{ substr($event->verification_hash, 0, 64) }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Firmas -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                {{ $changedBy->name }}
            </div>
            <div class="signature-label">Firma del Responsable de RRHH</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                &nbsp;
            </div>
            <div class="signature-label">Firma del Gerente</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                {{ $user->name }}
            </div>
            <div class="signature-label">Recibido por el Empleado</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p style="font-weight: bold; font-size: 7.5pt;">DOCUMENTO CONFIDENCIAL - RECURSOS HUMANOS</p>
        <p style="font-size: 6.5pt; margin-top: 3px;">Este documento contiene información sensible y debe ser manejado con confidencialidad</p>
        <p style="margin-top: 5px; font-size: 6pt; color: #95a5a6;">
            Documento generado electrónicamente por {{ $tenant->name }}<br>
            Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>
</body>
</html>
