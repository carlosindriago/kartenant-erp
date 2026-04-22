<!DOCTYPE html>
<html lang="es">
<head>
    <title>¡Bienvenido a Kartenant!</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        .container { padding: 30px; max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #eab308 0%, #f59e0b 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; margin: -30px -30px 30px; }
        .code { background-color: #f4f4f4; padding: 10px 15px; border-radius: 5px; font-family: monospace; display: inline-block; font-size: 15px; letter-spacing: 2px; border: 1px solid #ddd; }
        .button { display: inline-block; background-color: #eab308; color: white !important; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .security-box { background-color: #dcfce7; border-left: 4px solid #16a34a; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .step { background-color: #f9fafb; padding: 18px; margin: 15px 0; border-radius: 6px; border: 1px solid #e5e7eb; }
        .step-number { background: #eab308; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">🎉 ¡Bienvenido a Kartenant!</h1>
            <p style="margin: 10px 0 0; opacity: 0.95;">Tu cuenta empresarial está lista</p>
        </div>
        
        <p><strong>Hola, {{ $user->name }}</strong></p>
        <p>Tu espacio de trabajo en Kartenant ha sido configurado exitosamente. Hemos implementado múltiples capas de seguridad para proteger tu negocio.</p>
        
        <div class="security-box">
            <strong>🔐 Seguridad Mejorada Activada:</strong>
            <ul style="margin: 8px 0;">
                <li><strong>Verificación en Dos Pasos (2FA):</strong> Código por email en cada login</li>
                <li><strong>Contraseña Segura:</strong> 20 caracteres hexadecimales</li>
                <li><strong>Cambio Obligatorio:</strong> Deberás cambiar tu contraseña en el primer acceso</li>
                <li><strong>Verificación de Cambios:</strong> Código de confirmación por email</li>
            </ul>
        </div>
        
        <h3 style="color: #1f2937; margin-top: 30px;">📋 Proceso de Acceso</h3>
        
        <div class="step">
            <span class="step-number">1</span>
            <strong>Accede a tu URL única</strong>
            <p style="margin: 10px 0 10px 40px;">Tu empresa tiene un dominio exclusivo:</p>
            <p style="margin: 10px 0 0 40px;"><span class="code">{{ $tenant->domain }}.kartenant.test</span></p>
        </div>

        <div class="step">
            <span class="step-number">2</span>
            <strong>Ingresa tus credenciales temporales</strong>
            <ul style="margin: 10px 0 0 40px;">
                <li><strong>Email:</strong> <span class="code">{{ $user->email }}</span></li>
                <li><strong>Contraseña Temporal:</strong> <span class="code">{{ $temporaryPassword }}</span></li>
            </ul>
        </div>
        
        <div class="step">
            <span class="step-number">3</span>
            <strong>Verifica tu identidad (2FA)</strong>
            <p style="margin: 10px 0 0 40px;">Recibirás un código de 6 dígitos en tu email. Ingrésalo para continuar.</p>
        </div>
        
        <div class="step">
            <span class="step-number">4</span>
            <strong>Cambia tu contraseña</strong>
            <p style="margin: 10px 0 0 40px;">Por seguridad, deberás crear una nueva contraseña. Recibirás otro código de verificación para confirmar el cambio.</p>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ Importante:</strong>
            <ul style="margin: 5px 0;">
                <li>Guarda esta contraseña temporal en un lugar seguro</li>
                <li>Los códigos de verificación expiran en 10 minutos</li>
                <li>Nunca compartas tus códigos de verificación</li>
                <li>Si no solicitaste esta cuenta, contacta a soporte inmediatamente</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="https://{{ $tenant->domain }}.kartenant.test/app" class="button">🚀 Acceder a Mi Panel</a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #6b7280; font-size: 14px;">
            Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.<br>
            Estamos aquí para apoyarte en cada paso.
        </p>
        
        <p style="margin-top: 20px;"><strong>Saludos,</strong></p>
        <p style="color: #eab308; font-weight: bold;">El equipo de Kartenant</p>
    </div>
</body>
</html>