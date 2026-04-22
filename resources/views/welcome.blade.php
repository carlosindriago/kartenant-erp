<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kartenant: El sistema de punto de venta (POS) y gestión de inventario todo en uno para tu negocio. Simplifica tus ventas, controla tu stock y cumple con la AFIP sin complicaciones.">
    <meta name="keywords" content="POS, punto de venta, inventario, facturación, AFIP, Argentina, SaaS, software para negocios, Kartenant">
    <meta name="author" content="Kartenant">

    <title>Kartenant - Tu Aliado en Punto de Venta y Gestión</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-gray-900">Kartenant</a>
            <nav class="space-x-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Iniciar Sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Registrarse</a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <main>
        <section class="bg-white">
            <div class="container mx-auto px-6 py-20 text-center">
                <h1 class="text-4xl font-bold text-gray-900 md:text-6xl">La Gestión de tu Negocio, Simplificada</h1>
                <p class="mt-4 text-lg text-gray-600 md:text-xl">Kartenant es el sistema de Punto de Venta (POS) e inventario que te ayuda a crecer. Dedica más tiempo a tus clientes y menos a la administración.</p>
                <div class="mt-8">
                    <a href="{{ route('register') }}" class="bg-blue-600 text-white px-8 py-3 rounded-md text-lg font-medium hover:bg-blue-700">Empieza Gratis por 14 días</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Todo lo que necesitas en un solo lugar</h2>
                    <p class="text-gray-600 mt-2">Desde el cobro hasta el reporte fiscal, te tenemos cubierto.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H7a3 3 0 00-3 3v4a3 3 0 003 3z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Punto de Venta Rápido</h3>
                        <p class="text-gray-600">Un POS intuitivo y ágil para que tus ventas fluyan sin interrupciones. Compatible con lectores de códigos de barra.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Gestión de Inventario</h3>
                        <p class="text-gray-600">Controla tu stock en tiempo real, gestiona productos, categorías y proveedores de forma centralizada.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Facturación y Cumplimiento AFIP</h3>
                        <p class="text-gray-600">Genera facturas y notas de crédito. Calculamos los impuestos por ti para que tus declaraciones sean sencillas.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Gestión de Múltiples Usuarios</h3>
                        <p class="text-gray-600">Asigna roles y permisos a tus empleados. Controla quién tiene acceso a qué información.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Reportes Inteligentes</h3>
                        <p class="text-gray-600">Obtén reportes de ventas, productos más vendidos, y más. Toma decisiones basadas en datos.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="text-blue-600 mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Seguridad Avanzada</h3>
                        <p class="text-gray-600">Tus datos están seguros con nosotros. Registramos cada acción para una auditoría completa y tranquilidad.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="bg-white py-20">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Planes para cada tipo de negocio</h2>
                    <p class="text-gray-600 mt-2">Elige el plan que mejor se adapte a tus necesidades. Sin contratos a largo plazo.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <!-- Basic Plan -->
                    <div class="border rounded-lg p-8 flex flex-col">
                        <h3 class="text-2xl font-bold text-center mb-4">Básico</h3>
                        <p class="text-center text-gray-600 mb-8">Para emprendedores y pequeños negocios que recién comienzan.</p>
                        <div class="text-center text-4xl font-bold mb-8">$10 <span class="text-lg font-normal">/mes</span></div>
                        <ul class="space-y-4 text-gray-600 mb-8 flex-grow">
                            <li><span class="text-green-500 mr-2">✔</span>1 Usuario</li>
                            <li><span class="text-green-500 mr-2">✔</span>Punto de Venta</li>
                            <li><span class="text-green-500 mr-2">✔</span>Gestión de Inventario (hasta 500 productos)</li>
                            <li><span class="text-green-500 mr-2">✔</span>Soporte por email</li>
                        </ul>
                        <a href="{{ route('register') }}" class="bg-blue-600 text-white text-center px-6 py-3 rounded-md font-medium hover:bg-blue-700 mt-auto">Elegir Plan</a>
                    </div>
                    <!-- Pro Plan -->
                    <div class="border-2 border-blue-600 rounded-lg p-8 flex flex-col relative">
                        <span class="bg-blue-600 text-white text-xs font-bold uppercase px-3 py-1 rounded-full absolute top-0 -mt-3 right-4">Más Popular</span>
                        <h3 class="text-2xl font-bold text-center mb-4">Profesional</h3>
                        <p class="text-center text-gray-600 mb-8">Para negocios en crecimiento que necesitan más funcionalidades.</p>
                        <div class="text-center text-4xl font-bold mb-8">$25 <span class="text-lg font-normal">/mes</span></div>
                        <ul class="space-y-4 text-gray-600 mb-8 flex-grow">
                            <li><span class="text-green-500 mr-2">✔</span>Hasta 5 Usuarios</li>
                            <li><span class="text-green-500 mr-2">✔</span>Todo en el plan Básico</li>
                            <li><span class="text-green-500 mr-2">✔</span>Reportes Avanzados</li>
                            <li><span class="text-green-500 mr-2">✔</span>Gestión de Múltiples Cajas</li>
                            <li><span class="text-green-500 mr-2">✔</span>Soporte prioritario</li>
                        </ul>
                        <a href="{{ route('register') }}" class="bg-blue-600 text-white text-center px-6 py-3 rounded-md font-medium hover:bg-blue-700 mt-auto">Elegir Plan</a>
                    </div>
                    <!-- Enterprise Plan -->
                    <div class="border rounded-lg p-8 flex flex-col">
                        <h3 class="text-2xl font-bold text-center mb-4">Empresarial</h3>
                        <p class="text-center text-gray-600 mb-8">Soluciones a medida para grandes empresas y cadenas.</p>
                        <div class="text-center text-4xl font-bold mb-8">Contacto</div>
                        <ul class="space-y-4 text-gray-600 mb-8 flex-grow">
                            <li><span class="text-green-500 mr-2">✔</span>Usuarios Ilimitados</li>
                            <li><span class="text-green-500 mr-2">✔</span>Todo en el plan Profesional</li>
                            <li><span class="text-green-500 mr-2">✔</span>Integraciones a medida (API)</li>
                            <li><span class="text-green-500 mr-2">✔</span>Soporte dedicado 24/7</li>
                            <li><span class="text-green-500 mr-2">✔</span>Marca blanca</li>
                        </ul>
                        <a href="mailto:ventas@kartenant.com" class="bg-gray-800 text-white text-center px-6 py-3 rounded-md font-medium hover:bg-gray-900 mt-auto">Contáctanos</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-20">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Lo que dicen nuestros clientes</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <p class="text-gray-600 mb-4">"Kartenant transformó la gestión de mi ferretería. Ahora tengo control total sobre mi inventario y las ventas son mucho más rápidas. ¡Lo recomiendo!"</p>
                        <div class="font-bold text-gray-900">Ernesto G.</div>
                        <div class="text-sm text-gray-500">Dueño de Ferretería</div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <p class="text-gray-600 mb-4">"El sistema es muy fácil de usar y el soporte técnico es excelente. Cumplir con AFIP nunca fue tan sencillo."</p>
                        <div class="font-bold text-gray-900">María L.</div>
                        <div class="text-sm text-gray-500">Propietaria de Tienda de Ropa</div>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <p class="text-gray-600 mb-4">"Implementamos Kartenant en nuestras 3 sucursales y la gestión centralizada es una maravilla. Los reportes nos dan una visión clara de todo el negocio."</p>
                        <div class="font-bold text-gray-900">Carlos R.</div>
                        <div class="text-sm text-gray-500">Gerente de Cadena de Tiendas</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="bg-white py-20">
            <div class="container mx-auto px-6 max-w-3xl">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Preguntas Frecuentes</h2>
                </div>
                <div class="space-y-4">
                    <details class="p-4 border rounded-lg">
                        <summary class="font-bold cursor-pointer">¿Necesito instalar algo?</summary>
                        <p class="mt-2 text-gray-600">No. Kartenant es un servicio en la nube. Solo necesitas un navegador web y conexión a internet para acceder desde cualquier dispositivo.</p>
                    </details>
                    <details class="p-4 border rounded-lg">
                        <summary class="font-bold cursor-pointer">¿Mis datos están seguros?</summary>
                        <p class="mt-2 text-gray-600">Absolutamente. Utilizamos encriptación de nivel bancario y hacemos copias de seguridad diarias. Además, nuestro sistema de logs registra cada acción para tu tranquilidad.</p>
                    </details>
                    <details class="p-4 border rounded-lg">
                        <summary class="font-bold cursor-pointer">¿Qué pasa si necesito ayuda?</summary>
                        <p class="mt-2 text-gray-600">Ofrecemos soporte por email en el plan Básico y soporte prioritario por chat y teléfono en los planes superiores. Nuestro equipo está listo para ayudarte.</p>
                    </details>
                    <details class="p-4 border rounded-lg">
                        <summary class="font-bold cursor-pointer">¿Puedo cancelar en cualquier momento?</summary>
                        <p class="mt-2 text-gray-600">Sí, puedes cancelar tu suscripción en cualquier momento sin penalizaciones. No tenemos contratos de permanencia.</p>
                    </details>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="font-bold text-lg mb-4">Kartenant</h3>
                    <p class="text-gray-400">Simplificando la gestión de tu negocio.</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Producto</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white">Características</a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-white">Precios</a></li>
                        <li><a href="#faq" class="text-gray-400 hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Términos de Servicio</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Política de Privacidad</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Contacto</h3>
                    <ul class="space-y-2">
                        <li><a href="mailto:soporte@kartenant.com" class="text-gray-400 hover:text-white">soporte@kartenant.com</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Facebook</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Twitter</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-6 text-center text-gray-500">
                &copy; {{ date('Y') }} Kartenant. Todos los derechos reservados.
            </div>
        </div>
    </footer>

</body>
</html>