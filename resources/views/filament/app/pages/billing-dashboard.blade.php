<x-filament-panels::page>
    @php
        $billingData = $this->getBillingData();
        $subscription = $billingData['subscription'] ?? [];
        $nextPayment = $billingData['next_payment'] ?? [];
        $paymentMethods = $billingData['payment_methods'] ?? [];
    @endphp

    <div class="space-y-6" x-data="billingDashboard()">

        {{-- SUBSCRIPTION STATUS CARD --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Mi Suscripción
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Gestiona los pagos de tu plan mensual
                    </p>
                </div>

                {{-- Status Badge --}}
                <div class="flex items-center gap-3">
                    @if($subscription['status'] === 'active')
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-medium rounded-full">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Activo
                        </span>
                    @elseif($subscription['status'] === 'expired')
                        <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm font-medium rounded-full">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Vencido
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-sm font-medium rounded-full">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Pendiente
                        </span>
                    @endif

                    @if($subscription['on_trial'] ?? false)
                        <span class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-medium rounded-full">
                            🎉 Periodo de Prueba
                        </span>
                    @endif
                </div>
            </div>

            {{-- Plan Details Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Plan Name --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Plan Actual</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $subscription['plan_name'] ?? 'Básico' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $subscription['billing_cycle'] === 'monthly' ? 'Facturación Mensual' : 'Facturación Anual' }}
                    </p>
                </div>

                {{-- Price --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Precio Mensual</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                        ${{ number_format($subscription['price'] ?? 29.99, 2) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">USD</p>
                </div>

                {{-- Next Payment --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                        {{ $subscription['on_trial'] ?? false ? 'Trial Termina En' : 'Próximo Pago' }}
                    </p>
                    <p class="text-2xl font-bold
                        {{ ($subscription['days_until_expiration'] ?? 30) <= 3 ? 'text-red-600 dark:text-red-400' : (($subscription['days_until_expiration'] ?? 30) <= 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white') }}">
                        {{ $subscription['days_until_expiration'] ?? 30 }} días
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ \Carbon\Carbon::parse($nextPayment['due_date'] ?? now()->addDays(30))->format('d/m/Y') }}
                    </p>
                </div>

                {{-- Amount Due --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Monto a Pagar</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        ${{ number_format($nextPayment['amount'] ?? 29.99, 2) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">USD</p>
                </div>
            </div>
        </div>

        {{-- PAYMENT METHODS --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Métodos de Pago Disponibles
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($paymentMethods as $method)
                    @if($method['enabled'] ?? true)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                @if($method['name'] === 'Transferencia Bancaria')
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                @endif
                                {{ $method['name'] }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $method['details'] }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- PAYMENT PROOF UPLOAD --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700"
             x-init="$el.scrollIntoView({ behavior: 'smooth' })"
             x-show="showUpload"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             id="upload-section">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Subir Comprobante de Pago
            </h3>

            <form wire:submit="submitPaymentProof" class="space-y-4">
                {{ $this->form }}

                {{-- Upload Progress --}}
                <div x-show="uploading"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="hidden">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Subiendo archivo...</span>
                            <span x-text="uploadProgress + '%'" class="text-sm text-blue-600 dark:text-blue-400 font-mono">0%</span>
                        </div>
                        <div class="mt-2 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                 :style="'width: ' + uploadProgress + '%'"></div>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button"
                            @click="showUpload = false"
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            :disabled="uploading"
                            class="px-6 py-2 bg-amber-500 text-white font-medium rounded-lg hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2">
                        <svg x-show="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span x-show="!uploading">Subir Comprobante</span>
                        <span x-show="uploading">Procesando...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- PAYMENT HISTORY --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Historial de Pagos
                </h3>
                <button @click="showUpload = true"
                        class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Pago
                </button>
            </div>

            {{ $this->table }}
        </div>
    </div>

    {{-- JavaScript for Interactivity --}}
    <script>
        function billingDashboard() {
            return {
                showUpload: false,
                uploading: false,
                uploadProgress: 0,

                scrollToUpload() {
                    this.showUpload = true;
                    setTimeout(() => {
                        const element = document.getElementById('upload-section');
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth' });
                        }
                    }, 100);
                },

                simulateUpload() {
                    this.uploading = true;
                    this.uploadProgress = 0;

                    const interval = setInterval(() => {
                        this.uploadProgress += 10;
                        if (this.uploadProgress >= 100) {
                            clearInterval(interval);
                            setTimeout(() => {
                                this.uploading = false;
                                this.uploadProgress = 0;
                                this.showUpload = false;
                            }, 500);
                        }
                    }, 200);
                }
            }
        }
    </script>

    {{-- Listen for custom events --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-upload', () => {
                window.billingDashboard.scrollToUpload();
            });
        });
    </script>
</x-filament-panels::page>