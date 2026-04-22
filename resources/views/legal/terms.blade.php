@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="inline-block mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </a>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Términos de Servicio</h1>
            <p class="text-lg text-gray-600">Última actualización: {{ date('d/m/Y') }}</p>
        </div>

        <!-- Content Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
            <div class="prose prose-lg max-w-none">
                <h2>1. Aceptación de los Términos</h2>
                <p>Al acceder y utilizar Emporio Digital, aceptas estar sujeto a estos Términos de Servicio y todas las leyes y regulaciones aplicables. Si no estás de acuerdo con alguno de estos términos, no debes utilizar este servicio.</p>

                <h2>2. Descripción del Servicio</h2>
                <p>Emporio Digital es una plataforma SaaS (Software as a Service) que proporciona herramientas de gestión empresarial, incluyendo:</p>
                <ul>
                    <li>Sistema de punto de venta (POS)</li>
                    <li>Gestión de inventario</li>
                    <li>Facturación y reportes</li>
                    <li>Gestión de clientes y proveedores</li>
                </ul>

                <h2>3. Registro y Cuenta</h2>
                <h3>3.1 Elegibilidad</h3>
                <p>Debes tener al menos 18 años y la capacidad legal para celebrar contratos vinculantes para usar este servicio.</p>

                <h3>3.2 Información de Registro</h3>
                <p>Debes proporcionar información precisa, completa y actualizada durante el proceso de registro. Eres responsable de mantener la confidencialidad de tu cuenta y contraseña.</p>

                <h3>3.3 Responsabilidad de la Cuenta</h3>
                <p>Eres responsable de todas las actividades que ocurran bajo tu cuenta. Debes notificarnos inmediatamente sobre cualquier uso no autorizado.</p>

                <h2>4. Planes y Pagos</h2>
                <h3>4.1 Planes de Suscripción</h3>
                <p>Ofrecemos diferentes planes de suscripción con características y precios variables. Los detalles están disponibles en nuestra página de precios.</p>

                <h3>4.2 Período de Prueba</h3>
                <p>Ofrecemos un período de prueba gratuito de 7 días. Solo se permite un período de prueba por dirección IP. Después del período de prueba, debes seleccionar un plan de pago para continuar usando el servicio.</p>

                <h3>4.3 Pagos</h3>
                <p>Los pagos se procesan mensual o anualmente según tu plan seleccionado. Todos los precios están en dólares estadounidenses (USD) salvo que se indique lo contrario.</p>

                <h3>4.4 Renovación Automática</h3>
                <p>Las suscripciones se renuevan automáticamente al final de cada período de facturación. Puedes cancelar la renovación automática en cualquier momento desde tu panel de control.</p>

                <h2>5. Uso Aceptable</h2>
                <p>Al usar nuestro servicio, aceptas NO:</p>
                <ul>
                    <li>Violar leyes o regulaciones aplicables</li>
                    <li>Infringir los derechos de propiedad intelectual de terceros</li>
                    <li>Transmitir virus, malware o código malicioso</li>
                    <li>Intentar obtener acceso no autorizado a nuestros sistemas</li>
                    <li>Usar el servicio para spam o actividades fraudulentas</li>
                    <li>Realizar ingeniería inversa del software</li>
                </ul>

                <h2>6. Propiedad Intelectual</h2>
                <p>Emporio Digital y todo su contenido, características y funcionalidad son propiedad exclusiva de nuestra empresa y están protegidos por leyes de propiedad intelectual internacionales.</p>

                <h2>7. Privacidad y Datos</h2>
                <h3>7.1 Tus Datos</h3>
                <p>Mantienes todos los derechos sobre los datos que ingresas en el sistema. Consulta nuestra <a href="{{ route('legal.privacy') }}" class="text-purple-600 hover:underline">Política de Privacidad</a> para más detalles.</p>

                <h3>7.2 Seguridad</h3>
                <p>Implementamos medidas de seguridad razonables para proteger tus datos, pero no podemos garantizar seguridad absoluta.</p>

                <h2>8. Cancelación y Terminación</h2>
                <h3>8.1 Por Tu Parte</h3>
                <p>Puedes cancelar tu suscripción en cualquier momento desde tu panel de control. La cancelación será efectiva al final del período de facturación actual.</p>

                <h3>8.2 Por Nuestra Parte</h3>
                <p>Podemos suspender o terminar tu acceso si violas estos términos o por razones operativas legítimas con previo aviso.</p>

                <h3>8.3 Efecto de la Terminación</h3>
                <p>Tras la terminación, tu acceso al servicio cesará y tus datos podrán ser eliminados después de un período de gracia de 30 días.</p>

                <h2>9. Limitación de Responsabilidad</h2>
                <p>En la máxima medida permitida por la ley, Emporio Digital no será responsable por daños indirectos, incidentales, especiales, consecuentes o punitivos, incluyendo pérdida de beneficios, datos o uso.</p>

                <h2>10. Garantías</h2>
                <p>El servicio se proporciona "tal cual" y "según disponibilidad". No garantizamos que el servicio será ininterrumpido, seguro o libre de errores.</p>

                <h2>11. Modificaciones</h2>
                <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Los cambios significativos se notificarán con al menos 30 días de anticipación.</p>

                <h2>12. Ley Aplicable</h2>
                <p>Estos términos se regirán e interpretarán de acuerdo con las leyes de Argentina, sin considerar sus disposiciones sobre conflictos de leyes.</p>

                <h2>13. Contacto</h2>
                <p>Para preguntas sobre estos Términos de Servicio, contáctanos en:</p>
                <p>
                    <strong>Email:</strong> soporte@emporiodigital.com<br>
                    <strong>Sitio web:</strong> <a href="https://emporiodigital.test" class="text-purple-600 hover:underline">emporiodigital.test</a>
                </p>
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
