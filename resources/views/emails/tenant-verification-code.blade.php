<x-mail::message>
# Verifica tu correo electrónico

Hola,

Gracias por registrarte en **Emporio Digital**. Para continuar con la creación de tu cuenta para **{{ $companyName }}**, por favor usa el siguiente código de verificación:

<div style="text-align: center; padding: 20px;">
<span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #4f46e5; background: #f3f4f6; padding: 10px 20px; border-radius: 8px;">
{{ $code }}
</span>
</div>

Este código expirará en 30 minutos.

Si no solicitaste este código, puedes ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
