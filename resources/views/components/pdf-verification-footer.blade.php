<div style="border-top: 2px solid #e5e7eb; padding-top: 15px; margin-top: 30px; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="70%" style="vertical-align: top;">
                <p style="margin: 0 0 10px 0; font-size: 12px; color: #374151; font-weight: bold;">
                    🔐 Verificar Autenticidad del Documento
                </p>
                <p style="margin: 0 0 5px 0; font-size: 10px; color: #6b7280;">
                    Este documento está protegido con un código de verificación único.
                </p>
                <p style="margin: 0 0 5px 0; font-size: 10px; color: #6b7280;">
                    Escanee el código QR o visite:
                </p>
                <p style="margin: 0 0 10px 0; font-size: 9px; color: #3b82f6; word-break: break-all;">
                    {{ $url }}
                </p>
                <p style="margin: 0; padding: 8px; background-color: #f3f4f6; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 8px; color: #1f2937; word-break: break-all;">
                    Hash: {{ $hash }}
                </p>
            </td>
            <td width="30%" style="text-align: right; vertical-align: top;">
                <img src="{{ $qr }}" alt="QR de Verificación" style="width: 120px; height: 120px; border: 2px solid #e5e7eb; border-radius: 4px;">
            </td>
        </tr>
    </table>
    <p style="margin: 15px 0 0 0; font-size: 8px; color: #9ca3af; text-align: center;">
        Documento generado por Kartenant • {{ now()->format('d/m/Y H:i') }}
    </p>
</div>
