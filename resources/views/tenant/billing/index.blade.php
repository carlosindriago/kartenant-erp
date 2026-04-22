@extends('tenant.layouts.app')

@section('title', 'Facturación y Suscripción')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Facturación y Suscripción</h1>
        <p class="text-gray-600">Gestiona tu suscripción y sube comprobantes de pago</p>
    </div>

    <!-- Success/Error Messages -->
    @include('tenant.partials.flash-messages')

    <!-- Subscription Status Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Estado de tu Suscripción</h2>
            @if($subscription)
                @if($subscription->status === 'active')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Activa
                    </span>
                @elseif($subscription->status === 'trial')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Período de Prueba
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Vencida
                    </span>
                @endif
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    Sin suscripción
                </span>
            @endif
        </div>

        @if($subscription)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Plan Actual</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->name ?? 'Básico' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Próximo Pago</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $subscription->next_billing_date ? \Carbon\Carbon::parse($subscription->next_billing_date)->format('d/m/Y') : 'N/A' }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Monto Mensual</p>
                    <p class="text-lg font-semibold text-gray-900">${{ number_format($subscription->plan->monthly_price ?? 0, 2, ',', '.') }}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bank Details Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos Bancarios para Transferencia</h3>

            @if($paymentSettings && $paymentSettings->isBankTransferConfigured())
                <div class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-900">Banco</span>
                        </div>
                        <p class="text-lg font-semibold text-blue-900">{{ $paymentSettings->bank_name }}</p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Titular</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ $paymentSettings->bank_account_holder }}</p>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-1">
                                    <span class="text-sm font-medium text-green-900 mr-1">CBU</span>
                                    <svg class="w-4 h-4 text-green-600 cursor-pointer hover:text-green-800" onclick="copyToClipboard('{{ $paymentSettings->bank_account_number }}')" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-mono text-green-900 break-all">{{ $paymentSettings->bank_account_number }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center mb-1">
                                    <span class="text-sm font-medium text-purple-900 mr-1">Alias</span>
                                    <svg class="w-4 h-4 text-purple-600 cursor-pointer hover:text-purple-800" onclick="copyToClipboard('{{ $paymentSettings->bank_routing_number }}')" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-mono text-purple-900 break-all">{{ $paymentSettings->bank_routing_number }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">{{ $paymentSettings->payment_instructions }}</p>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500">Los datos bancarios no están configurados</p>
                </div>
            @endif
        </div>

        <!-- Upload Payment Proof -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Subir Comprobante de Pago</h3>

            <form action="{{ route('tenant.billing.payment-proof.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4">
                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Comprobante de Transferencia <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                            <input type="file" name="file" id="payment-proof-file" class="hidden" accept=".jpg,.jpeg,.png,.pdf" required>
                            <label for="payment-proof-file" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium text-blue-600 hover:text-blue-500">Click para subir archivo</span>
                                    o arrastra y suelta
                                </p>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, PDF (Máx. 5MB)</p>
                            </label>
                        </div>
                        <div id="file-preview" class="mt-2 hidden">
                            <p class="text-sm text-gray-600">Archivo seleccionado: <span id="file-name" class="font-medium"></span></p>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                            Monto Pagado <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                            <input type="number" name="amount" id="amount" step="0.01" min="0" required
                                   class="block w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha del Pago <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="payment_date" id="payment_date" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notas (Opcional)</label>
                        <textarea name="notes" id="notes" rows="3" maxlength="500"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Referencia de transferencia u otros detalles..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 500 caracteres</p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors font-medium">
                        Enviar Comprobante
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Payments -->
    @if($recentPayments && $recentPayments->count() > 0)
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Comprobantes Recientes</h3>
            <div class="space-y-3">
                @foreach($recentPayments as $payment)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${{ number_format($payment->amount, 2, ',', '.') }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->payment_date->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($payment->status === 'pending')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pendiente
                                </span>
                            @elseif($payment->status === 'approved')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Aprobado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Rechazado
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('tenant.billing.history') }}" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                    Ver todo el historial →
                </a>
            </div>
        </div>
    @endif
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show temporary success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
        toast.textContent = '¡Copiado al portapapeles!';
        document.body.appendChild(toast);

        setTimeout(() => {
            document.body.removeChild(toast);
        }, 2000);
    });
}

// File preview
document.getElementById('payment-proof-file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');

    if (file) {
        fileName.textContent = file.name;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
});

// Set default date to today
document.getElementById('payment_date').valueAsDate = new Date();
</script>
@endsection