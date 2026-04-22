/**
 * Billing Dashboard Integration
 * Handles API calls, file uploads, and real-time updates for the tenant billing interface
 * Designed for "Ernesto" - non-technical business owners
 */

class BillingIntegration {
    constructor() {
        this.apiBase = '/api/v1/billing';
        this.uploading = false;
        this.uploadProgress = 0;
        this.currentTenant = null;

        this.init();
    }

    init() {
        // Get current tenant from URL or global state
        this.currentTenant = this.getCurrentTenant();

        // Initialize Livewire listeners
        this.setupLivewireListeners();

        // Setup form validation
        this.setupFormValidation();

        // Setup auto-refresh for pending payments
        this.setupAutoRefresh();
    }

    getCurrentTenant() {
        // Try to get tenant from subdomain or global Filament state
        if (typeof Filament !== 'undefined' && Filament.getTenant) {
            return Filament.getTenant();
        }

        // Fallback: extract from subdomain
        const hostname = window.location.hostname;
        const subdomain = hostname.split('.')[0];
        return subdomain !== 'emporiodigital' ? subdomain : null;
    }

    setupLivewireListeners() {
        if (typeof Livewire === 'undefined') return;

        // Listen for payment submission
        Livewire.on('payment-proof-submitted', (data) => {
            this.handlePaymentSubmission(data);
        });

        // Listen for file upload progress
        Livewire.on('upload-progress', (progress) => {
            this.updateUploadProgress(progress);
        });
    }

    setupFormValidation() {
        // File upload validation
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.accept) {
                this.validateFile(e.target);
            }
        });

        // Form submission enhancement
        document.addEventListener('submit', (e) => {
            if (e.target.closest('[wire\\:submit="submitPaymentProof"]')) {
                this.enhanceFormSubmission(e);
            }
        });
    }

    setupAutoRefresh() {
        // Auto-refresh pending payments every 30 seconds
        setInterval(() => {
            this.refreshPaymentHistory();
        }, 30000);
    }

    validateFile(input) {
        const file = input.files[0];
        if (!file) return true;

        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        const allowedExtensions = ['.pdf', '.jpg', '.jpeg', '.png'];

        // Check file size
        if (file.size > maxSize) {
            this.showNotification('error', 'Archivo muy grande', 'El tamaño máximo permitido es 5MB');
            input.value = '';
            return false;
        }

        // Check file type
        if (!allowedTypes.includes(file.type)) {
            this.showNotification('error', 'Tipo de archivo no permitido', 'Solo se aceptan PDF, JPG y PNG');
            input.value = '';
            return false;
        }

        // Check file extension
        const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
        if (!allowedExtensions.includes(fileExtension)) {
            this.showNotification('error', 'Extensión de archivo no válida', 'Solo se aceptan archivos .pdf, .jpg, .jpeg, .png');
            input.value = '';
            return false;
        }

        return true;
    }

    async enhanceFormSubmission(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const fileInput = form.querySelector('input[type="file"]');

        if (!this.validateFile(fileInput)) {
            return;
        }

        try {
            // Show uploading state
            this.setSubmittingState(submitButton, true);
            this.uploading = true;
            this.uploadProgress = 0;

            // Create a simulated upload progress
            const progressInterval = this.simulateUploadProgress();

            // Submit the form via Livewire
            const livewireComponent = this.findParentLivewireComponent(form);
            if (livewireComponent) {
                await livewireComponent.submitPaymentProof();
            }

            clearInterval(progressInterval);
            this.uploadProgress = 100;

            // Success notification
            this.showNotification(
                'success',
                '¡Comprobante Enviado!',
                'Tu comprobante ha sido recibido y será procesado en las próximas horas. Te enviaremos un correo electrónico cuando sea aprobado.'
            );

            // Reset form
            form.reset();

            // Update payment history
            setTimeout(() => {
                this.refreshPaymentHistory();
            }, 2000);

        } catch (error) {
            console.error('Payment submission error:', error);
            this.showNotification(
                'error',
                'Error al Subir Comprobante',
                'No se pudo subir el comprobante. Por favor, verifica tu conexión a internet e intenta nuevamente.'
            );
        } finally {
            this.setSubmittingState(submitButton, false);
            this.uploading = false;
            this.uploadProgress = 0;
        }
    }

    simulateUploadProgress() {
        const interval = setInterval(() => {
            if (this.uploadProgress < 90) {
                this.uploadProgress += Math.random() * 15;
                this.updateUploadProgressUI(this.uploadProgress);
            }
        }, 300);
        return interval;
    }

    updateUploadProgress(progress) {
        this.uploadProgress = Math.min(progress, 100);
        this.updateUploadProgressUI(this.uploadProgress);
    }

    updateUploadProgressUI(progress) {
        // Update Alpine.js data if available
        if (typeof Alpine !== 'undefined' && window.Alpine) {
            window.Alpine.store('billing', {
                uploadProgress: Math.round(progress),
                uploading: progress < 100
            });
        }

        // Update custom progress elements
        const progressElements = document.querySelectorAll('[data-upload-progress]');
        progressElements.forEach(element => {
            element.textContent = Math.round(progress) + '%';
        });

        const progressBar = document.querySelector('[data-upload-progress-bar]');
        if (progressBar) {
            progressBar.style.width = Math.round(progress) + '%';
        }
    }

    setSubmittingState(button, isSubmitting) {
        if (!button) return;

        button.disabled = isSubmitting;

        if (isSubmitting) {
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Subiendo...
            `;
        } else {
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.innerHTML = `
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Subir Comprobante
            `;
        }
    }

    async refreshPaymentHistory() {
        try {
            const response = await fetch(`${this.apiBase}/history?tenant_id=${this.currentTenant}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.updatePaymentHistoryTable(data);

                // Check for new approved payments
                this.checkForApprovedPayments(data);
            }
        } catch (error) {
            console.warn('Could not refresh payment history:', error);
        }
    }

    updatePaymentHistoryTable(payments) {
        // Update the Filament table if possible
        const livewireComponent = document.querySelector('[wire\\:id*="billing-dashboard"]');
        if (livewireComponent && livewireComponent.__livewire) {
            livewireComponent.__livewire.set('payments', payments);
        }
    }

    checkForApprovedPayments(payments) {
        const newlyApproved = payments.filter(payment =>
            payment.status === 'approved' &&
            payment.was_recently_approved
        );

        if (newlyApproved.length > 0) {
            this.showNotification(
                'success',
                '¡Pago Aprobado! 🎉',
                `Tu pago de $${newlyApproved[0].amount} USD ha sido aprobado. Tu suscripción está activa.`
            );
        }
    }

    handlePaymentSubmission(data) {
        if (data.success) {
            this.showNotification('success', '¡Éxito!', data.message);
            this.refreshPaymentHistory();
        } else {
            this.showNotification('error', 'Error', data.message);
        }
    }

    showNotification(type, title, message) {
        // Use Filament notification system if available
        if (typeof Filament !== 'undefined' && Filament.notification) {
            const notification = Filament.notification()
                .title(title)
                .body(message);

            const method = type === 'error' ? 'danger' : type;
            notification[method]();
            notification.send();
        } else {
            // Fallback to browser notification
            this.showBrowserNotification(type, title, message);
        }
    }

    showBrowserNotification(type, title, message) {
        // Create custom notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${type === 'success' ?
                        '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
                        '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="ml-3">
                    <h4 class="font-semibold">${title}</h4>
                    <p class="text-sm mt-1">${message}</p>
                </div>
                <button class="ml-4 -mx-1.5 -my-1.5 p-1.5 rounded-lg hover:bg-black/10 transition" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    findParentLivewireComponent(element) {
        // Find the closest Livewire component
        let parent = element.closest('[wire\\:id]');
        if (parent && parent.__livewire) {
            return parent.__livewire;
        }
        return null;
    }

    // Public methods for external use
    scrollToUpload() {
        const uploadSection = document.getElementById('upload-section');
        if (uploadSection) {
            uploadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Highlight the section briefly
            uploadSection.classList.add('ring-2', 'ring-amber-500', 'ring-opacity-50');
            setTimeout(() => {
                uploadSection.classList.remove('ring-2', 'ring-amber-500', 'ring-opacity-50');
            }, 2000);
        }
    }

    // Initialize when DOM is ready
    static init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => new BillingIntegration());
        } else {
            new BillingIntegration();
        }
    }
}

// Auto-initialize
BillingIntegration.init();

// Export for potential use in other modules
window.BillingIntegration = BillingIntegration;