<?php

namespace App\Livewire;

use App\Models\PaymentProof;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;

class BillingManager extends Component
{
    use WithFileUploads;

    public $payment_proof;

    public $notes;

    public $uploadProgress = 0;

    public $isUploading = false;

    public $billingData = [];

    public $payments = [];

    protected $rules = [
        'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        'notes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'payment_proof.required' => 'Por favor selecciona un archivo de comprobante',
        'payment_proof.mimes' => 'Solo se aceptan archivos PDF, JPG y PNG',
        'payment_proof.max' => 'El tamaño máximo permitido es 5MB',
        'notes.max' => 'Las notas no pueden exceder los 500 caracteres',
    ];

    public function mount()
    {
        $this->loadBillingData();
        $this->loadPaymentHistory();
    }

    public function loadBillingData()
    {
        try {
            $tenant = Tenant::current();
            if (! $tenant) {
                $this->billingData = $this->getDefaultBillingData();

                return;
            }

            $response = Http::get('/api/v1/billing', [
                'tenant_id' => $tenant->id,
            ]);

            if ($response->successful()) {
                $this->billingData = $response->json();
            } else {
                $this->billingData = $this->getDefaultBillingData();
            }

        } catch (\Exception $e) {
            $this->billingData = $this->getDefaultBillingData();
        }
    }

    public function loadPaymentHistory()
    {
        try {
            $tenant = Tenant::current();
            if (! $tenant) {
                $this->payments = [];

                return;
            }

            $response = Http::get('/api/v1/billing/history', [
                'tenant_id' => $tenant->id,
            ]);

            if ($response->successful()) {
                $this->payments = $response->json();
            } else {
                $this->payments = [];
            }

        } catch (\Exception $e) {
            $this->payments = [];
        }
    }

    public function updatedPaymentProof()
    {
        $this->validateOnly('payment_proof');
    }

    public function updatedNotes()
    {
        $this->validateOnly('notes');
    }

    public function submitPaymentProof()
    {
        $this->validate();

        $this->isUploading = true;
        $this->uploadProgress = 0;

        try {
            $tenant = Tenant::current();
            if (! $tenant) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('No se pudo identificar tu cuenta de tenant')
                    ->send();

                return;
            }

            // Simulate upload progress
            $this->streamUploadProgress();

            // Prepare data for API call
            $paymentData = [
                'tenant_id' => $tenant->id,
                'payment_proof' => $this->payment_proof,
                'notes' => $this->notes,
            ];

            // Call API endpoint
            $response = Http::asMultipart()->post('/api/v1/billing', $paymentData);

            $this->uploadProgress = 100;

            if ($response->successful()) {
                Notification::make()
                    ->success()
                    ->title('¡Comprobante Enviado!')
                    ->body('Tu comprobante ha sido recibido y será procesado en las próximas horas. Te enviaremos un correo electrónico cuando sea aprobado.')
                    ->send();

                // Reset form
                $this->reset(['payment_proof', 'notes']);
                $this->loadPaymentHistory();

                // Dispatch event for frontend
                $this->dispatch('payment-proof-submitted', [
                    'success' => true,
                    'message' => 'Payment proof submitted successfully',
                ]);

            } else {
                $errorData = $response->json();
                Notification::make()
                    ->danger()
                    ->title('Error al Subir Comprobante')
                    ->body($errorData['message'] ?? 'No se pudo procesar el comprobante. Intenta nuevamente.')
                    ->send();

                $this->dispatch('payment-proof-submitted', [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Upload failed',
                ]);
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error de Conexión')
                ->body('No se pudo conectar con el servidor. Verifica tu conexión a internet e intenta en unos minutos.')
                ->send();

            $this->dispatch('payment-proof-submitted', [
                'success' => false,
                'message' => 'Connection error: '.$e->getMessage(),
            ]);

        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    private function streamUploadProgress()
    {
        // Simulate progress updates
        for ($i = 10; $i <= 90; $i += 10) {
            $this->uploadProgress = $i;
            $this->dispatch('upload-progress', $i);
            usleep(100000); // 100ms delay
        }
    }

    public function refreshData()
    {
        $this->loadBillingData();
        $this->loadPaymentHistory();

        Notification::make()
            ->info()
            ->title('Datos Actualizados')
            ->body('La información de facturación ha sido actualizada.')
            ->send();
    }

    public function downloadPaymentProof($paymentId)
    {
        try {
            $tenant = Tenant::current();
            if (! $tenant) {
                return response()->json(['error' => 'Tenant not found'], 404);
            }

            $paymentProof = PaymentProof::where('tenant_id', $tenant->id)
                ->where('id', $paymentId)
                ->first();

            if (! $paymentProof || ! $paymentProof->file_path) {
                return response()->json(['error' => 'Payment proof not found'], 404);
            }

            $filePath = storage_path('app/'.$paymentProof->file_path);
            if (! file_exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->download($filePath, basename($paymentProof->file_path));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Download failed'], 500);
        }
    }

    private function getDefaultBillingData(): array
    {
        return [
            'subscription' => [
                'plan_name' => 'Básico',
                'price' => 29.99,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'days_until_expiration' => 15,
                'on_trial' => false,
            ],
            'next_payment' => [
                'amount' => 29.99,
                'due_date' => now()->addDays(15)->format('Y-m-d'),
                'days_until_due' => 15,
            ],
            'payment_methods' => [
                'bank_transfer' => [
                    'name' => 'Transferencia Bancaria',
                    'details' => 'Banco: XXXXXX - Cuenta: XXXXXX - Titular: Emporio Digital',
                    'enabled' => true,
                ],
                'deposit' => [
                    'name' => 'Depósito Bancario',
                    'details' => 'Banco: XXXXXX - Cuenta: XXXXXX - Titular: Emporio Digital',
                    'enabled' => true,
                ],
            ],
        ];
    }

    public function getSubscriptionStatusProperty()
    {
        return $this->billingData['subscription'] ?? [];
    }

    public function getNextPaymentProperty()
    {
        return $this->billingData['next_payment'] ?? [];
    }

    public function getPaymentMethodsProperty()
    {
        return $this->billingData['payment_methods'] ?? [];
    }

    public function getIsOnTrialProperty()
    {
        return $this->billingData['subscription']['on_trial'] ?? false;
    }

    public function getDaysUntilExpirationProperty()
    {
        return $this->billingData['subscription']['days_until_expiration'] ?? 30;
    }

    public function getSubscriptionStatusColorProperty()
    {
        $status = $this->billingData['subscription']['status'] ?? 'unknown';
        $days = $this->days_until_expiration;

        if ($days <= 3) {
            return 'danger';
        } elseif ($days <= 7) {
            return 'warning';
        } elseif ($status === 'active') {
            return 'success';
        } elseif ($status === 'expired') {
            return 'danger';
        } else {
            return 'warning';
        }
    }

    public function render()
    {
        return view('livewire.billing-manager');
    }
}
