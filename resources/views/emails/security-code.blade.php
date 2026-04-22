<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Seguridad</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .code-box {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .security-code {
            font-size: 32px;
            font-weight: bold;
            color: #1976d2;
            letter-spacing: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kartenant</h1>
        <h2>Código de Seguridad</h2>
    </div>

    <p>Hola {{ $user->name }},</p>

    <p>Has solicitado restablecer tu contraseña. Utiliza el siguiente código de seguridad para continuar con el proceso:</p>

    <div class="code-box">
        <p>Tu código de seguridad es:</p>
        <div class="security-code">{{ $securityCode }}</div>
        <p><small>Este código expira en 10 minutos</small></p>
    </div>

    <div class="warning">
        <strong>⚠️ Importante:</strong>
        <ul>
            <li>Este código es válido por 10 minutos únicamente</li>
            <li>No compartas este código con nadie</li>
            <li>Si no solicitaste este código, ignora este mensaje</li>
        </ul>
    </div>

    <p>Después de ingresar el código, podrás responder tus preguntas de seguridad y establecer una nueva contraseña.</p>

    <div class="footer">
        <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
        <p><strong>Kartenant</strong> - Tu sistema de gestión empresarial</p>
    </div>
</body>
</html>
