@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="inline-block mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </a>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Política de Privacidad</h1>
            <p class="text-lg text-gray-600">Última actualización: {{ date('d/m/Y') }}</p>
        </div>

        <!-- Content Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
            <div class="prose prose-lg max-w-none">
                <h2>1. Introducción</h2>
                <p>En Emporio Digital, valoramos tu privacidad y estamos comprometidos a proteger tus datos personales. Esta Política de Privacidad explica qué información recopilamos, cómo la usamos y tus derechos respecto a ella.</p>

                <h2>2. Información que Recopilamos</h2>
                <h3>2.1 Información que Proporcionas</h3>
                <ul>
                    <li><strong>Datos de Registro:</strong> Nombre, email, contraseña, nombre de empresa, dominio, CUIT/RUT/RFC, dirección, teléfono</li>
                    <li><strong>Información de Contacto:</strong> Nombre y email de la persona de contacto</li>
                    <li><strong>Datos de Pago:</strong> Información de tarjeta de crédito procesada por proveedores de pago externos</li>
                    <li><strong>Datos del Negocio:</strong> Productos, clientes, ventas, inventario que ingresas en el sistema</li>
                </ul>

                <h3>2.2 Información Recopilada Automáticamente</h3>
                <ul>
                    <li><strong>Datos de Uso:</strong> Páginas visitadas, características utilizadas, tiempo de uso</li>
                    <li><strong>Información Técnica:</strong> Dirección IP, tipo de navegador, sistema operativo, zona horaria</li>
                    <li><strong>Cookies:</strong> Usamos cookies para mantener tu sesión y mejorar la experiencia</li>
                </ul>

                <h2>3. Cómo Usamos tu Información</h2>
                <p>Utilizamos la información recopilada para:</p>
                <ul>
                    <li>Proporcionar y mantener nuestro servicio</li>
                    <li>Procesar transacciones y enviar confirmaciones</li>
                    <li>Comunicarnos contigo sobre actualizaciones y soporte</li>
                    <li>Mejorar y personalizar tu experiencia</li>
                    <li>Detectar y prevenir fraude y abusos</li>
                    <li>Cumplir con obligaciones legales</li>
                    <li>Enviar comunicaciones de marketing (con tu consentimiento)</li>
                </ul>

                <h2>4. Base Legal para el Procesamiento</h2>
                <p>Procesamos tus datos personales basándonos en:</p>
                <ul>
                    <li><strong>Ejecución del Contrato:</strong> Para proporcionar el servicio que contrataste</li>
                    <li><strong>Intereses Legítimos:</strong> Para mejorar nuestro servicio y prevenir fraude</li>
                    <li><strong>Consentimiento:</strong> Para comunicaciones de marketing</li>
                    <li><strong>Obligación Legal:</strong> Para cumplir con requisitos legales</li>
                </ul>

                <h2>5. Compartir Información</h2>
                <h3>5.1 No Vendemos tus Datos</h3>
                <p>Nunca vendemos tu información personal a terceros.</p>

                <h3>5.2 Compartimos con:</h3>
                <ul>
                    <li><strong>Proveedores de Servicios:</strong> Hosting, procesamiento de pagos, análisis (bajo estrictos acuerdos de confidencialidad)</li>
                    <li><strong>Requerimientos Legales:</strong> Cuando la ley lo requiera o para proteger nuestros derechos</li>
                    <li><strong>Transferencias Comerciales:</strong> En caso de fusión, adquisición o venta de activos</li>
                </ul>

                <h2>6. Seguridad de los Datos</h2>
                <p>Implementamos medidas de seguridad técnicas y organizativas para proteger tus datos:</p>
                <ul>
                    <li>Encriptación SSL/TLS para transmisión de datos</li>
                    <li>Contraseñas hasheadas con bcrypt</li>
                    <li>Bases de datos aisladas por tenant</li>
                    <li>Backups automáticos diarios</li>
                    <li>Monitoreo de seguridad 24/7</li>
                    <li>Acceso restringido por roles y permisos</li>
                </ul>

                <h2>7. Retención de Datos</h2>
                <p>Retenemos tus datos personales mientras:</p>
                <ul>
                    <li>Tu cuenta esté activa</li>
                    <li>Sea necesario para proporcionarte el servicio</li>
                    <li>Sea requerido por obligaciones legales</li>
                </ul>
                <p>Después de la cancelación de tu cuenta, tus datos se eliminan después de 30 días, salvo que la ley requiera retención más prolongada.</p>

                <h2>8. Tus Derechos</h2>
                <p>Tienes derecho a:</p>
                <ul>
                    <li><strong>Acceso:</strong> Solicitar una copia de tus datos personales</li>
                    <li><strong>Rectificación:</strong> Corregir datos inexactos o incompletos</li>
                    <li><strong>Eliminación:</strong> Solicitar la eliminación de tus datos ("derecho al olvido")</li>
                    <li><strong>Portabilidad:</strong> Recibir tus datos en formato estructurado</li>
                    <li><strong>Oposición:</strong> Oponerte al procesamiento de tus datos</li>
                    <li><strong>Limitación:</strong> Solicitar restricción del procesamiento</li>
                    <li><strong>Revocación:</strong> Retirar consentimiento en cualquier momento</li>
                </ul>
                <p>Para ejercer estos derechos, contacta a: <strong>privacidad@emporiodigital.com</strong></p>

                <h2>9. Cookies</h2>
                <h3>9.1 Tipos de Cookies que Usamos</h3>
                <ul>
                    <li><strong>Esenciales:</strong> Necesarias para el funcionamiento del sitio</li>
                    <li><strong>Funcionales:</strong> Recuerdan tus preferencias</li>
                    <li><strong>Analíticas:</strong> Ayudan a entender cómo usas el servicio</li>
                </ul>

                <h3>9.2 Control de Cookies</h3>
                <p>Puedes controlar y/o eliminar cookies según desees en la configuración de tu navegador.</p>

                <h2>10. Transferencias Internacionales</h2>
                <p>Tus datos pueden ser transferidos y procesados en servidores ubicados fuera de tu país de residencia. Garantizamos protección adecuada mediante:</p>
                <ul>
                    <li>Cláusulas contractuales estándar aprobadas</li>
                    <li>Certificaciones de privacidad</li>
                    <li>Medidas de seguridad adicionales</li>
                </ul>

                <h2>11. Menores de Edad</h2>
                <p>Nuestro servicio no está dirigido a menores de 18 años. No recopilamos intencionalmente información de menores. Si descubres que un menor ha proporcionado datos, contáctanos para eliminarlos.</p>

                <h2>12. Cambios a esta Política</h2>
                <p>Podemos actualizar esta Política de Privacidad periódicamente. Te notificaremos sobre cambios significativos mediante:</p>
                <ul>
                    <li>Email a tu dirección registrada</li>
                    <li>Aviso destacado en el servicio</li>
                    <li>Actualización de la fecha "Última actualización"</li>
                </ul>

                <h2>13. Cumplimiento con GDPR</h2>
                <p>Para usuarios en la Unión Europea, cumplimos con el Reglamento General de Protección de Datos (GDPR). Tienes derechos adicionales bajo GDPR que respetamos completamente.</p>

                <h2>14. Contacto</h2>
                <p>Para preguntas sobre esta Política de Privacidad o para ejercer tus derechos, contáctanos:</p>
                <p>
                    <strong>Email de Privacidad:</strong> privacidad@emporiodigital.com<br>
                    <strong>Email General:</strong> soporte@emporiodigital.com<br>
                    <strong>Sitio web:</strong> <a href="https://emporiodigital.test" class="text-purple-600 hover:underline">emporiodigital.test</a>
                </p>

                <div class="bg-purple-50 p-6 rounded-xl mt-8">
                    <h3 class="text-purple-900 font-bold mb-2">📧 Delegado de Protección de Datos</h3>
                    <p class="text-purple-800 text-sm">Si tienes preocupaciones sobre cómo manejamos tus datos, puedes contactar a nuestro Delegado de Protección de Datos en: <strong>dpo@emporiodigital.com</strong></p>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <a href="{{ route('tenant.register.form') }}" class="text-purple-600 font-semibold hover:underline">
                    ← Volver al registro
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
