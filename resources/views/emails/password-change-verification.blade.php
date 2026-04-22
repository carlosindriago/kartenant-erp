<!DOCTYPE html>
<html lang="es">
<head>
    <title>Código de Verificación para Cambio de Contraseña</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; }
        .container { padding: 20px; max-width: 580px; margin: auto; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; }
        .code {
            display: inline-block;
            background-color: #dcfce7;
            border: 2px solid #16a34a;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            font-family: monospace;
            margin: 15px 0;
            color: #166534;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Código de Verificación para Cambio de Contraseña</h2>
        <p>Has solicitado cambiar tu contraseña en Kartenant. Para completar este proceso de forma segura, necesitamos verificar tu identidad.</p>
        
        <p><strong>Tu código de verificación es:</strong></p>
        <div class="code">{{ $code }}</div>
        
        <div class="warning">
            <strong>⚠️ Importante:</strong>
            <ul style="margin: 5px 0;">
                <li>Este código expirará en <strong>10 minutos</strong></li>
                <li>Solo se puede usar una vez</li>
                <li>No compartas este código con nadie</li>
            </ul>
        </div>
        
        <p>Ingresa este código en la página de cambio de contraseña para confirmar tu nueva contraseña.</p>
        
        <p><strong>¿No solicitaste este cambio?</strong></p>
        <p>Si no has intentado cambiar tu contraseña, ignora este correo. Tu contraseña actual permanecerá sin cambios. Por seguridad, te recomendamos cambiar tu contraseña lo antes posible.</p>
        
        <br>
        <p>Gracias por mantener tu cuenta segura,</p>
        <p><strong>El equipo de Kartenant</strong></p>
    </div>
</body>
</html>
