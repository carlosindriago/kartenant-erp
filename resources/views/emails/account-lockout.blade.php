<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Bloqueada por Seguridad</title>
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 40px 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .warning-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .alert-icon {
            font-size: 48px;
            color: #dc2626;
            text-align: center;
            margin: 20px 0;
        }
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1f2937;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .btn:hover {
            background: #111827;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Alerta de Seguridad</h1>
            <h2>Cuenta Bloqueada Temporalmente</h2>
        </div>

        <div class="content">
            <div class="alert-icon">
                🔒
            </div>

            <h3>Hola, {{ $user->name }}</h3>

            <p>Te escribimos para informarte que tu cuenta en <strong>Emporio Digital</strong> ha sido bloqueada temporalmente por motivos de seguridad.</p>

            <div class="warning-box">
                <h4>🚨 ¿Qué sucedió?</h4>
                <p>Detectamos <strong>3 intentos fallidos</strong> al ingresar el código de verificación en dos pasos (2FA). Para proteger tu cuenta, hemos activado un bloqueo de seguridad automático.</p>

                <p><strong>Duración del bloqueo:</strong> {{ $lockoutDuration }}</p>
            </div>

            <div class="info-box">
                <h4>💡 ¿Qué puedes hacer?</h4>
                <p>Tienes dos opciones para reactivar tu cuenta:</p>

                <ol>
                    <li><strong>Esperar:</strong> El bloqueo se eliminará automáticamente después de {{ $lockoutDuration }}.</li>
                    <li><strong>Contactar soporte:</strong> Si crees que fue un error o necesitas acceso urgente, comunícate con nuestro equipo de soporte.</li>
                </ol>
            </div>

            <h4>📞 Contacto con Soporte</h4>
            <p>Si necesitas ayuda inmediata, puedes contactarnos:</p>
            <ul>
                <li><strong>Email:</strong> soporte@emporiodigital.test</li>
                <li><strong>Teléfono:</strong> +1 234 567 8900</li>
            </ul>

            <div class="info-box">
                <h4>🔐 Información de Seguridad</h4>
                <p><small>
                    <strong>IP del intento:</strong> {{ request()->ip() }}<br>
                    <strong>Fecha y hora:</strong> {{ now()->format('d/m/Y H:i:s') }}<br>
                    <strong>Usuario:</strong> {{ $user->email }}
                </small></p>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('tenant.login') }}" class="btn">
                    Ir a la Página de Login
                </a>
            </div>

            <div class="footer">
                <p>Este es un mensaje automático de seguridad. Por favor no respondas a este email.</p>
                <p>© {{ date('Y') }} Emporio Digital. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>