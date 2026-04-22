@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <a href="{{ route('landing') }}" class="inline-block mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </a>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Comienza tu Prueba Gratuita</h1>
            <p class="text-xl text-gray-600">7 días gratis, sin tarjeta de crédito</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <ul class="list-disc list-inside text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('tenant.register') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Step 1: Company Info -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Información de tu Empresa</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre de la Empresa *</label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dominio *</label>
                            <div class="flex rounded-xl overflow-hidden border-2 border-gray-200 focus-within:border-purple-600 transition">
                                <input type="text" name="domain" value="{{ old('domain') }}" required
                                    class="flex-1 px-4 py-3 border-0 focus:ring-0"
                                    placeholder="mi-tienda">
                                <span class="bg-gray-50 px-4 py-3 text-gray-500 font-medium flex items-center">.emporiodigital.test</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Este será tu URL: <span class="font-semibold">tudominio.emporiodigital.test</span></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">CUIT / RUT / RFC</label>
                            <input type="text" name="cuit" value="{{ old('cuit') }}"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition"
                                placeholder="20-12345678-9">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition"
                                placeholder="+54 11 1234-5678">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dirección</label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition"
                                placeholder="Calle Principal 123, Ciudad, Provincia">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Person Info -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Datos del Contacto Principal</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Contacto *</label>
                            <input type="text" name="contact_name" value="{{ old('contact_name') }}" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email del Contacto *</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email') }}" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Admin Info -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Datos del Administrador del Sistema</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre Completo *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contraseña *</label>
                            <input type="password" name="password" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Contraseña *</label>
                            <input type="password" name="password_confirmation" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        </div>
                    </div>
                </div>

                <!-- Step 4: Plan Selection  -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Selecciona tu Plan</h2>
                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="plan_type" value="trial" checked class="peer sr-only">
                            <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-purple-600 peer-checked:bg-purple-50 transition">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold">Prueba Gratuita</h3>
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold">GRATIS</span>
                                </div>
                                <p class="text-gray-600 text-sm">7 días completos, sin tarjeta</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="plan_type" value="paid" class="peer sr-only">
                            <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-purple-600 peer-checked:bg-purple-50 transition">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold">Plan de Pago</h3>
                                    <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-bold">PREMIUM</span>
                                </div>
                                <p class="text-gray-600 text-sm">Acceso completo desde el inicio</p>
                            </div>
                        </label>
                    </div>

                   <!-- Plan selection dropdown (shown only for paid) -->
                    <div id="paid-plan-options" class="hidden">
                        <select name="plan_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition mb-4">
                            <option value="">Selecciona un plan...</option>
                            @foreach($plans as $plan)
                                @if($plan->slug !== 'gratuito')
                                    <option value="{{ $plan->id }}">{{ $plan->name }} - ${{ $plan->price_monthly }}/mes</option>
                                @endif
                            @endforeach
                        </select>
                        <select name="billing_cycle" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual (ahorra 16%)</option>
                        </select>
                    </div>
                </div>

                <!-- Captcha -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Verifica que eres humano *</label>
                    <div class="flex items-center gap-4">
                        <span class="text-lg font-mono bg-gray-100 px-4 py-3 rounded-xl">{{ $captcha_question }} = ?</span>
                        <input type="number" name="captcha_answer" required
                            class="w-32 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                    </div>
                </div>

                <!-- Honeypot (hidden field to trap bots) -->
                <input type="text" name="website" value="" style="display:none;" tabindex="-1" autocomplete="off">

                <!-- Terms -->
                <div>
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="terms" required class="mt-1 w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-600">
                        <span class="ml-3 text-sm text-gray-700">
                            Acepto los <a href="{{ route('legal.terms') }}" target="_blank" class="text-purple-600 hover:underline">Términos de Servicio</a> y 
                            <a href="{{ route('legal.privacy') }}" target="_blank" class="text-purple-600 hover:underline">Política de Privacidad</a>
                        </span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-[1.02] transition transform">
                    Crear mi Cuenta →
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                ¿Ya tienes cuenta? <a href="{{ route('tenant.login.form') }}" class="text-purple-600 font-semibold hover:underline">Inicia sesión</a>
            </p>
        </div>
    </div>
</div>

<script>
// Show/hide paid plan options
document.querySelectorAll('input[name="plan_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const paidOptions = document.getElementById('paid-plan-options');
        if (this.value === 'paid') {
            paidOptions.classList.remove('hidden');
        } else {
            paidOptions.classList.add('hidden');
        }
    });
});
</script>
@endsection
