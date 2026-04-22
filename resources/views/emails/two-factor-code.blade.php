<!DOCTYPE html>
<html lang="es">
<head>
    <title>Tu Código de Verificación</title>
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; }
        .container { padding: 20px; max-width: 580px; margin: auto; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; }
        .code {
            display: inline-block;
            background-color: #e5e7eb;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            font-family: monospace;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tu Código de Verificación</h2>
        <p>Usa el siguiente código para completar tu inicio de sesión en Kartenant. Este código expirará en 10 minutos.</p>
        <p>Tu código es:</p>
        <div class="code">{{ $code }}</div>
        <p>Si no has intentado iniciar sesión, puedes ignorar este correo electrónico de forma segura.</p>
        <br>
        <p>Gracias,</p>
        <p>El equipo de Kartenant</p>
    </div>
</body>
</html>