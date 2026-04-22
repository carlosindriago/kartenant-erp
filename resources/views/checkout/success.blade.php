@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Success Icon -->
        <div class="text-center mb-8">
            <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-2xl mb-6 animate-pulse">
                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">¡Comprobante Enviado!</h1>
            <p class="text-xl text-gray-600">Estamos procesando tu pago</p>
        </div>

        <!-- Info Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-10">
            <div class="space-y-6">
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-green-900 mb-2">✅ ¿Qué sigue?</h3>
                    <ul class="space-y-2 text-green-800">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Nuestro equipo revisará tu comprobante</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Te enviaremos un email cuando sea aprobado (máx. 24h)</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Tu cuenta será activada automáticamente</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-purple-50 rounded-xl p-6">
                    <h4 class="font-bold text-gray-900 mb-3">📋 Detalles de tu Suscripción</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Empresa:</span>
                            <span class="font-semibold text-gray-900">{{ $subscription->tenant->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan:</span>
                            <span class="font-semibold text-gray-900">{{ $subscription->plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ciclo:</span>
                            <span class="font-semibold text-gray-900">{{ $subscription->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Dominio:</span>
                            <span class="font-semibold text-purple-600">{{ $subscription->tenant->domain }}.emporiodigital.test</span>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                    <h4 class="font-bold text-blue-900 mb-2">📧 Revisa tu Email</h4>
                    <p class="text-blue-800 text-sm">
                        Hemos enviado un email a <strong>{{ $subscription->tenant->contact_email }}</strong> con toda la información de tu suscripción.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 space-y-3">
                    <a href="{{ route('landing') }}" class="block w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-center py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-[1.02] transition transform">
                        Volver al Inicio
                    </a>
                    
                    <p class="text-center text-sm text-gray-600">
                        ¿Necesitas ayuda? <a href="mailto:soporte@emporiodigital.com" class="text-purple-600 hover:underline font-semibold">Contacta Soporte</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
