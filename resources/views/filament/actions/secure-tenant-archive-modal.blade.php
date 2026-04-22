@props(['tenant', 'userCount', 'dataAge'])

<!-- Multi-Step Secure Archive Modal -->
<div x-data="secureArchiveModal()" x-init="init()">
    <form id="archive-form" method="POST" action="">
        @csrf

    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">
                Paso <span x-text="currentStep"></span> de <span x-text="totalSteps"></span>
            </span>
            <span class="text-sm font-medium"
                  :class="getTimeRemainingClass()">
                ⏱️ <span x-text="timeRemaining"></span>
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-300"
                 :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
        </div>
        <div class="flex justify-between mt-2">
            <template x-for="step in steps" :key="step.number">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                         :class="getStepClass(step.number)">
                        <span x-text="step.number"></span>
                    </div>
                    <span class="text-xs mt-1 text-gray-600" x-text="step.title"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Security Level Indicator -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex space-x-1">
                    <template x-for="level in securityLevels" :key="level">
                        <div class="w-2 h-6 rounded-full"
                             :class="level <= currentStep ? 'bg-green-500' : 'bg-gray-300'"></div>
                    </template>
                </div>
                <span class="text-sm font-medium text-gray-700">
                    Nivel de Seguridad: <span x-text="getSecurityLevelText()" class="font-bold"></span>
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-green-700">Protegido con 2FA</span>
            </div>
        </div>
    </div>

    <!-- Step 1: Impact Assessment -->
    <div x-show="currentStep === 1" x-transition>
        <div class="space-y-6">
            <div class="bg-red-100 border-4 border-red-500 rounded-lg p-6">
                <h3 class="text-xl font-bold text-red-900 mb-3">🚨 Evaluación Crítica de Impacto</h3>
                <p class="text-red-800 font-medium">
                    Para archivar <strong>"{{ $tenant->name }}"</strong>, debes comprender completamente el impacto:
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border-2 border-red-300 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $userCount }}</div>
                    <div class="text-sm text-gray-600">Usuarios Afectados</div>
                </div>
                <div class="bg-white border-2 border-red-300 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $dataAge }} días</div>
                    <div class="text-sm text-gray-600">Historial de Datos</div>
                </div>
                <div class="bg-white border-2 border-red-300 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-600">IRREVERSIBLE</div>
                    <div class="text-sm text-gray-600">Nivel de Impacto</div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    📝 Evaluación Detallada del Impacto *
                </label>
                <textarea name="impact_assessment" required rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                          placeholder="Describe exhaustivamente el impacto de este archivado incluyendo consecuencias para usuarios, negocio y sistemas..."></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Esta evaluación quedará permanentemente registrada y puede ser auditada.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="nextStep()"
                        class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors">
                    Continuar a Verificación Legal →
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Legal Compliance -->
    <div x-show="currentStep === 2" x-transition>
        <div class="space-y-6">
            <div class="bg-purple-100 border-4 border-purple-500 rounded-lg p-6">
                <h3 class="text-xl font-bold text-purple-900 mb-3">⚖️ Cumplimiento Legal y Regulatorio</h3>
                <p class="text-purple-800 font-medium">
                    Verifica el cumplimiento con todos los requisitos legales y regulatorios antes de proceder.
                </p>
            </div>

            <div class="space-y-4">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" name="legal_retention_confirmed" required
                           class="mt-1 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <div>
                        <span class="font-medium text-gray-900">Períodos de Retención Legal Confirmados</span>
                        <p class="text-sm text-gray-600">Confirmo que se han cumplido todos los períodos de retención legal de datos según GDPR, SOX y otras regulaciones aplicables.</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" name="contractual_obligations_met" required
                           class="mt-1 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <div>
                        <span class="font-medium text-gray-900">Obligaciones Contractuales Cumplidas</span>
                        <p class="text-sm text-gray-600">Confirmo que se han cumplido todas las obligaciones contractuales incluyendo notificaciones previas y períodos de gracia.</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" name="data_backup_verified" required
                           class="mt-1 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <div>
                        <span class="font-medium text-gray-900">Backup Completo Verificado</span>
                        <p class="text-sm text-gray-600">Confirmo que existe un backup completo y verificado de todos los datos en almacenamiento seguro accesible para auditoría.</p>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    📋 Justificación Legal del Archivado *
                </label>
                <textarea name="legal_rationale" required rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                          placeholder="Describe la base legal y regulatoria para este archivado..."></textarea>
            </div>

            <div class="flex justify-between">
                <button type="button" @click="previousStep()"
                        class="bg-gray-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-600 transition-colors">
                    ← Anterior
                </button>
                <button type="button" @click="nextStep()"
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition-colors">
                    Continuar a Autenticación →
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Multi-Factor Authentication -->
    <div x-show="currentStep === 3" x-transition>
        <div class="space-y-6">
            <div class="bg-blue-100 border-4 border-blue-500 rounded-lg p-6">
                <h3 class="text-xl font-bold text-blue-900 mb-3">🔐 Autenticación Multi-Factor Obligatoria</h3>
                <p class="text-blue-800 font-medium">
                    Se requiere verificación de identidad a través de múltiples factores de seguridad.
                </p>
            </div>

            <!-- Password Verification -->
            <div class="bg-white border-2 border-blue-300 rounded-lg p-6">
                <h4 class="font-semibold text-blue-900 mb-4">
                    🔑 Factor 1: Contraseña de Administrador
                </h4>
                <input type="password" name="admin_password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ingresa tu contraseña de administrador">
                <p class="text-xs text-gray-500 mt-1">Verificación primaria de identidad.</p>
            </div>

            <!-- OTP Verification -->
            <div class="bg-white border-2 border-blue-300 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-blue-900">
                        🔢 Factor 2: Código de Verificación (OTP)
                    </h4>
                    <button type="button" @click="generateOTP()"
                            :disabled="otpGenerating || otpGenerated"
                            class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors">
                        <span x-show="!otpGenerating && !otpGenerated">Generar Código</span>
                        <span x-show="otpGenerating">Generando...</span>
                        <span x-show="otpGenerated">Código Enviado ✓</span>
                    </button>
                </div>

                <div x-show="otpGenerated" class="space-y-3">
                    <input type="text" name="otp_code" required maxlength="6"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ingresa el código de 6 dígitos"
                           x-model="otpInput">

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Código de respaldo:</strong> <span x-text="contextCode"></span>
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            Si no recibes el correo, puedes usar este código de respaldo.
                        </p>
                    </div>

                    <p class="text-xs text-gray-500">
                        El código ha sido enviado a tu email administrativo y expira en 10 minutos.
                    </p>
                </div>
            </div>

            <!-- Email Verification -->
            <div class="bg-white border-2 border-blue-300 rounded-lg p-6">
                <h4 class="font-semibold text-blue-900 mb-4">
                    📧 Factor 3: Verificación por Email
                </h4>
                <input type="text" name="email_verification_token" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ingresa el token de verificación enviado a tu email">
                <p class="text-xs text-gray-500 mt-1">
                    Token de un solo uso enviado a tu dirección de email administrativa.
                </p>
            </div>

            <div class="flex justify-between">
                <button type="button" @click="previousStep()"
                        class="bg-gray-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-600 transition-colors">
                    ← Anterior
                </button>
                <button type="button" @click="validateAuthentication()"
                        :disabled="!otpGenerated"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-400 transition-colors">
                    Verificar y Continuar →
                </button>
            </div>
        </div>
    </div>

    <!-- Step 4: Final Confirmation -->
    <div x-show="currentStep === 4" x-transition>
        <div class="space-y-6">
            <div class="bg-red-100 border-4 border-red-500 rounded-lg p-6">
                <h3 class="text-xl font-bold text-red-900 mb-3">⚠️ Confirmación Final Irrevocable</h3>
                <p class="text-red-800 font-medium">
                    Esta es la última oportunidad para cancelar. Esta acción es CASI IRREVERSIBLE.
                </p>
            </div>

            <div class="bg-gray-100 border-2 border-gray-300 rounded-lg p-6">
                <h4 class="font-semibold text-gray-900 mb-4">📋 Información Final del Tenant</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Nombre:</span> {{ $tenant->name }}
                    </div>
                    <div>
                        <span class="font-medium">Dominio:</span> {{ $tenant->domain }}
                    </div>
                    <div>
                        <span class="font-medium">ID:</span> {{ $tenant->id }}
                    </div>
                    <div>
                        <span class="font-medium">Creado:</span> {{ $tenant->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ✅ Confirmar Nombre Completo *
                    </label>
                    <input type="text" name="confirm_tenant_name" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="{{ 'Escribe exactamente: ' . $tenant->name }}">
                    <p class="text-xs text-gray-500 mt-1">Debe coincidir exactamente, incluyendo mayúsculas/minúsculas.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🔐 Palabra Clave de Archivado *
                    </label>
                    <input type="text" name="confirm_archive_keyword" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Escribe: ARCHIVE_PERMANENTLY">
                    <p class="text-xs text-gray-500 mt-1">Escribe la palabra clave exacta para confirmar.</p>
                </div>

                <div class="space-y-3">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="understand_irreversibility" required
                               class="mt-1 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <div>
                            <span class="font-medium text-gray-900">Entiendo la Irreversibilidad</span>
                            <p class="text-sm text-gray-600">Acepto que esta acción es casi irreversible y requerirá intervención técnica especializada para deshacer.</p>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="accept_liability" required
                               class="mt-1 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <div>
                            <span class="font-medium text-gray-900">Acepto Responsabilidad Legal</span>
                            <p class="text-sm text-gray-600">Acepto la responsabilidad legal y contractual por las consecuencias de este archivado.</p>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="peer_approval_required"
                               class="mt-1 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <div>
                            <span class="font-medium text-gray-900">Aprobación de Pares (Opcional)</span>
                            <p class="text-sm text-gray-600">Confirmo que se ha obtenido aprobación de otro administrador (si es requerido por política interna).</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="bg-red-200 border-2 border-red-400 rounded-lg p-4">
                <p class="text-center text-red-900 font-bold">
                    ⚠️ ESTA ACCIÓN SERÁ REGISTRADA PERMANENTEMENTE Y MONITOREADA EN TIEMPO REAL
                </p>
            </div>

            <div class="flex justify-between">
                <button type="button" @click="previousStep()"
                        class="bg-gray-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-600 transition-colors">
                    ← Anterior
                </button>
                <button type="submit" form="archive-form"
                        class="bg-red-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-red-700 transition-colors">
                    🚨 ARCHIVAR PERMANENTEMENTE
                </button>
            </div>
        </div>
    </div>

        <!-- Hidden Form Data -->
        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
        <input type="hidden" name="current_step" x-model="currentStep">

    </form>
</div>

<script>
function secureArchiveModal() {
    return {
        currentStep: 1,
        totalSteps: 4,
        securityLevels: 4,
        otpGenerated: false,
        otpGenerating: false,
        otpInput: '',
        contextCode: '',
        timeRemaining: '10:00',
        timeRemainingSeconds: 600,

        steps: [
            { number: 1, title: 'Impacto' },
            { number: 2, title: 'Legal' },
            { number: 3, title: 'Autenticación' },
            { number: 4, title: 'Confirmación' }
        ],

        init() {
            this.startTimer();
        },

        getStepClass(stepNumber) {
            if (stepNumber < this.currentStep) {
                return 'bg-green-500 text-white';
            } else if (stepNumber === this.currentStep) {
                return 'bg-blue-500 text-white ring-4 ring-blue-200';
            } else {
                return 'bg-gray-300 text-gray-600';
            }
        },

        getSecurityLevelText() {
            const levels = ['Básico', 'Medio', 'Alto', 'Máximo'];
            return levels[Math.min(this.currentStep - 1, 3)];
        },

        getTimeRemainingClass() {
            if (this.timeRemainingSeconds < 120) {
                return 'text-red-600 font-bold';
            } else if (this.timeRemainingSeconds < 300) {
                return 'text-yellow-600 font-medium';
            }
            return 'text-green-600';
        },

        startTimer() {
            setInterval(() => {
                if (this.timeRemainingSeconds > 0 && this.otpGenerated) {
                    this.timeRemainingSeconds--;
                    const minutes = Math.floor(this.timeRemainingSeconds / 60);
                    const seconds = this.timeRemainingSeconds % 60;
                    this.timeRemaining = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
        },

        nextStep() {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        async generateOTP() {
            this.otpGenerating = true;

            try {
                const response = await fetch('/admin/tenants/generate-archive-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        tenant_id: '{{ $tenant->id }}'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.otpGenerated = true;
                    this.contextCode = data.context_code;
                    this.timeRemainingSeconds = 600;

                    // Show success notification
                    this.showNotification('Código de verificación enviado a tu email', 'success');
                } else {
                    this.showNotification(data.error || 'Error al generar código', 'error');
                }
            } catch (error) {
                this.showNotification('Error de conexión', 'error');
            } finally {
                this.otpGenerating = false;
            }
        },

        validateAuthentication() {
            // Basic client-side validation
            const password = document.querySelector('input[name="admin_password"]').value;
            const otp = document.querySelector('input[name="otp_code"]').value;
            const emailToken = document.querySelector('input[name="email_verification_token"]').value;

            if (!password || !otp || !emailToken) {
                this.showNotification('Completa todos los campos de autenticación', 'error');
                return;
            }

            if (otp.length !== 6 || !/^\d+$/.test(otp)) {
                this.showNotification('El código OTP debe tener 6 dígitos', 'error');
                return;
            }

            this.nextStep();
        },

        showNotification(message, type) {
            // Simple notification - replace with your preferred notification system
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    };
}
</script>