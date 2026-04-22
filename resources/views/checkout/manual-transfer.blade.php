@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-full flex items-center justify-center mx-auto shadow-xl mb-6">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Completar Pago</h1>
            <p class="text-xl text-gray-600">{{ $subscription->plan->name }} - {{ $subscription->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</p>
        </div>

        <!-- Plan Summary -->
        <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Resumen de tu Plan</h2>
            
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Empresa</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->tenant->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Plan</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Ciclo de Facturación</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total a Pagar</p>
                    <p class="text-3xl font-bold text-purple-600">
                        {{ $checkout['bank_details']['currency'] ?? 'USD' }} {{ number_format($checkout['bank_details']['amount'], 2) }}
                    </p>
                </div>
            </div>

            <!-- Bank Details -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    Datos para Transferencia
                </h3>

                <div class="grid md:grid-cols-2 gap-6">
                    @if(!empty($checkout['bank_details']['bank_name']))
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Banco</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $checkout['bank_details']['bank_name'] }}</p>
                    </div>
                    @endif

                    @if(!empty($checkout['bank_details']['account_holder']))
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Titular</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $checkout['bank_details']['account_holder'] }}</p>
                    </div>
                    @endif

                    @if(!empty($checkout['bank_details']['account_number']))
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Número de Cuenta</p>
                        <p class="text-lg font-semibold text-gray-900 font-mono">{{ $checkout['bank_details']['account_number'] }}</p>
                    </div>
                    @endif

                    @if(!empty($checkout['bank_details']['cbu']))
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">CBU</p>
                        <p class="text-lg font-semibold text-gray-900 font-mono">{{ $checkout['bank_details']['cbu'] }}</p>
                    </div>
                    @endif

                    @if(!empty($checkout['bank_details']['alias']))
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Alias</p>
                        <p class="text-lg font-semibold text-purple-600 font-mono">{{ $checkout['bank_details']['alias'] }}</p>
                    </div>
                    @endif

                    <div class="bg-white rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Referencia</p>
                        <p class="text-lg font-semibold text-gray-900 font-mono">{{ $checkout['bank_details']['reference'] }}</p>
                    </div>
                </div>

                @if(!empty($checkout['instructions']))
                <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <p class="text-sm text-yellow-800">
                        <strong>Importante:</strong> {{ $checkout['instructions'] }}
                    </p>
                </div>
                @endif
            </div>

            <!-- Upload Proof Form -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Enviar Comprobante de Pago</h3>
                
                <form action="{{ route('checkout.upload-proof', $checkout['transaction_id']) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Comprobante de Transferencia *</label>
                        <input type="file" name="proof" required accept="image/*"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition">
                        <p class="mt-1 text-sm text-gray-500">Formatos aceptados: JPG, PNG. Máximo 5MB</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notas (opcional)</label>
                        <textarea name="notes" rows="3"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition"
                            placeholder="Número de transacción, fecha, etc."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-[1.02] transition transform">
                        Enviar Comprobante →
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-sm text-gray-600">
            Una vez enviado el comprobante, tu cuenta será activada en un plazo máximo de 24 horas.
        </p>
    </div>
</div>
@endsection
