@component('mail::message')
# 🚨 CÓDIGO DE VERIFICACIÓN CRÍTICO

## Operación de Archivado de Tenant

Estás recibiendo este email porque se ha iniciado una operación de archivado crítico en el sistema Emporio Digital.

---

## 📋 Detalles de la Operación

**Tenant:** {{ $tenant->name }}
**Dominio:** {{ $tenant->domain }}
**ID del Tenant:** {{ $tenant->id }}
**Operación:** ARCHIVO PERMANENTE
**Fecha:** {{ now()->format('d/m/Y H:i:s') }}
**IP de Origen:** {{ request()->ip() }}

---

## 🔐 Códigos de Verificación

### Código OTP Principal (6 dígitos)
<div style="background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
    <span style="font-size: 32px; font-weight: bold; letter-spacing: 4px; color: #1f2937; font-family: 'Courier New', monospace;">
        {{ $otpData['otp_code'] }}
    </span>
</div>

### Código de Respaldo (Alternativa)
<div style="background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
    <strong>Código de respaldo:</strong>
    <span style="font-family: 'Courier New', monospace; font-weight: bold;">{{ $otpData['context_code'] }}</span>
</div>

### Token de Verificación por Email
<div style="background: #dbeafe; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
    <strong>Token (para formulario de archivado):</strong>
    <span style="font-family: 'Courier New', monospace; font-size: 12px; word-break: break-all;">{{ $otpData['email_token'] }}</span>
</div>

---

## ⏰ Información Importante

- **El código OTP expira en 10 minutos**
- **Tienes un máximo de 3 intentos**
- **El token de email es de un solo uso**
- **Esta operación está siendo monitoreada en tiempo real**

---

## 🚨 ADVERTENCIAS DE SEGURIDAD

⚠️ **NO COMPARTAS ESTOS CÓDIGOS**
Estos códigos son personales y no deben ser compartidos con nadie.

⚠️ **VERIFICA LA OPERACIÓN**
Si no iniciaste esta operación, contacta inmediatamente al equipo de seguridad.

⚠️ **OPERACIÓN IRREVERSIBLE**
El archivado de un tenant es una acción casi irreversible con consecuencias permanentes.

---

## 📞 Contacto de Seguridad

Si esta operación no fue autorizada o tienes dudas de seguridad:

**Email de Seguridad:** security@emporiodigital.com
**Soporte 24/7:** +1-555-EMPO-RIO

---

<div style="text-align: center; margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
    <small style="color: #6b7280;">
        Este es un email automático del sistema de seguridad de Emporio Digital.<br>
        Por favor, no respondas a este mensaje.
    </small>
</div>

<div style="text-align: center; margin-top: 20px;">
    <img src="{{ url('images/emporio-logo.svg') }}" alt="Emporio Digital" style="max-width: 150px;">
</div>
@endcomponent