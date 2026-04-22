<?php

namespace App\Services;

use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\TenantSubscription;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Tenant;
use App\Settings\PaymentSettings;
use App\Settings\BillingSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentProofApproved;
use App\Notifications\PaymentProofRejected;
use App\Notifications\SubscriptionActivated;
use Carbon\Carbon;

class PaymentApprovalService
{
    public function __construct(
        private PaymentSettings $paymentSettings,
        private BillingSettings $billingSettings,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Process pending payment proofs
     */
    public function processPendingApprovals(): array
    {
        $results = [
            'approved' => 0,
            'rejected' => 0,
            'errors' => 0,
            'processed' => 0,
        ];

        PaymentProof::pending()
            ->where('created_at', '<', now()->subHours($this->paymentSettings->approval_timeout_hours))
            ->chunk(50, function ($paymentProofs) use (&$results) {
                foreach ($paymentProofs as $paymentProof) {
                    try {
                        $this->autoApproveOrReject($paymentProof);
                        $results['processed']++;

                        if ($paymentProof->isApproved()) {
                            $results['approved']++;
                        } elseif ($paymentProof->isRejected()) {
                            $results['rejected']++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error processing payment proof', [
                            'payment_proof_id' => $paymentProof->id,
                            'error' => $e->getMessage(),
                        ]);
                        $results['errors']++;
                    }
                }
            });

        Log::info('Payment approvals processed', $results);

        return $results;
    }

    /**
     * Auto-approve or reject based on business rules
     */
    private function autoApproveOrReject(PaymentProof $paymentProof): void
    {
        // Business rule: Auto-reject if timeout exceeded without review
        if ($paymentProof->created_at->lt(now()->subHours($this->paymentSettings->approval_timeout_hours))) {
            $this->rejectPaymentProof(
                $paymentProof,
                User::where('is_super_admin', true)->first(), // System user
                'Tiempo de aprobación excedido',
                'La prueba de pago fue rechazada automáticamente por superar el tiempo límite de aprobación.'
            );
            return;
        }

        // Business rule: Auto-approve small amounts from trusted tenants
        if ($this->shouldAutoApprove($paymentProof)) {
            $systemUser = User::where('is_super_admin', true)->first();
            $this->approvePaymentProof($paymentProof, $systemUser, 'Aprobación automática por monto bajo y tenant confiable');
        }
    }

    /**
     * Check if payment proof should be auto-approved
     */
    private function shouldAutoApprove(PaymentProof $paymentProof): bool
    {
        // Only auto-approve amounts below threshold
        $autoApprovalThreshold = 50.00; // Configurable

        if ($paymentProof->amount > $autoApprovalThreshold) {
            return false;
        }

        // Check tenant payment history
        $tenant = $paymentProof->tenant;
        $approvedPayments = PaymentProof::where('tenant_id', $tenant->id)
            ->approved()
            ->count();

        // Auto-approve if tenant has history of successful payments
        return $approvedPayments >= 3;
    }

    /**
     * Approve payment proof with full workflow
     */
    public function approvePaymentProof(
        PaymentProof $paymentProof,
        User $approver,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($paymentProof, $approver, $notes) {
            // Mark as under review
            $paymentProof->startReview($approver);

            // Validate payment proof data
            $validation = $this->validatePaymentProof($paymentProof);
            if (!$validation['valid']) {
                return $this->rejectPaymentProof(
                    $paymentProof,
                    $approver,
                    $validation['reason'],
                    $validation['details']
                );
            }

            // Approve the payment proof
            $paymentProof->approve($approver, $notes);

            // Process subscription activation
            $this->processSubscriptionActivation($paymentProof);

            // Send notifications
            $this->sendApprovalNotifications($paymentProof, $approver);

            Log::info('Payment proof approved manually', [
                'payment_proof_id' => $paymentProof->id,
                'approved_by' => $approver->id,
                'amount' => $paymentProof->amount,
            ]);

            return true;
        });
    }

    /**
     * Reject payment proof with full workflow
     */
    public function rejectPaymentProof(
        PaymentProof $paymentProof,
        User $rejector,
        string $reason,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($paymentProof, $rejector, $reason, $notes) {
            // Mark as under review if not already
            if ($paymentProof->isPending()) {
                $paymentProof->startReview($rejector);
            }

            // Reject the payment proof
            $paymentProof->reject($rejector, $reason, $notes);

            // Update subscription status
            $this->processSubscriptionRejection($paymentProof);

            // Send notifications
            $this->sendRejectionNotifications($paymentProof, $rejector, $reason);

            Log::info('Payment proof rejected manually', [
                'payment_proof_id' => $paymentProof->id,
                'rejected_by' => $rejector->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Validate payment proof before approval
     */
    private function validatePaymentProof(PaymentProof $paymentProof): array
    {
        $subscription = $paymentProof->subscription;
        if (!$subscription) {
            return [
                'valid' => false,
                'reason' => 'Suscripción no encontrada',
                'details' => 'La prueba de pago no está asociada a una suscripción válida',
            ];
        }

        // Check amount matches expected
        if (abs($paymentProof->amount - $subscription->price) > 0.01) {
            return [
                'valid' => false,
                'reason' => 'Monto incorrecto',
                'details' => "El monto pagado ({$paymentProof->amount}) no coincide con el monto esperado ({$subscription->price})",
            ];
        }

        // Check payment date is reasonable
        if ($paymentProof->payment_date->gt(now())) {
            return [
                'valid' => false,
                'reason' => 'Fecha de pago inválida',
                'details' => 'La fecha de pago no puede ser en el futuro',
            ];
        }

        // Check if payment is too old
        if ($paymentProof->payment_date->lt(now()->subDays(30))) {
            return [
                'valid' => false,
                'reason' => 'Pago demasiado antiguo',
                'details' => 'El pago no puede tener más de 30 días de antigüedad',
            ];
        }

        // Check for duplicate payments
        $duplicatePayment = PaymentProof::where('tenant_id', $paymentProof->tenant_id)
            ->where('subscription_id', $paymentProof->subscription_id)
            ->where('amount', $paymentProof->amount)
            ->where('payment_date', $paymentProof->payment_date)
            ->where('status', PaymentProof::STATUS_APPROVED)
            ->where('id', '!=', $paymentProof->id)
            ->first();

        if ($duplicatePayment) {
            return [
                'valid' => false,
                'reason' => 'Pago duplicado',
                'details' => 'Ya existe un pago aprobado con las mismas características',
            ];
        }

        // Validate files if present
        if ($paymentProof->file_paths) {
            $fileValidation = $this->validatePaymentFiles($paymentProof);
            if (!$fileValidation['valid']) {
                return $fileValidation;
            }
        }

        return ['valid' => true];
    }

    /**
     * Validate uploaded payment files
     */
    private function validatePaymentFiles(PaymentProof $paymentProof): array
    {
        $allowedTypes = $this->paymentSettings->allowed_file_types;
        $maxSizeBytes = $this->paymentSettings->getMaxFileSizeBytesAttribute();

        foreach ($paymentProof->file_paths as $filePath) {
            $fullPath = storage_path('app/' . $filePath);

            if (!file_exists($fullPath)) {
                return [
                    'valid' => false,
                    'reason' => 'Archivo no encontrado',
                    'details' => "El archivo {$filePath} no existe en el servidor",
                ];
            }

            $fileSize = filesize($fullPath);
            if ($fileSize > $maxSizeBytes) {
                return [
                    'valid' => false,
                    'reason' => 'Archivo demasiado grande',
                    'details' => 'Uno o más archivos exceden el tamaño máximo permitido',
                ];
            }

            $fileExtension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                return [
                    'valid' => false,
                    'reason' => 'Tipo de archivo no permitido',
                    'details' => "El tipo de archivo {$fileExtension} no está permitido",
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Process subscription activation after approval
     */
    private function processSubscriptionActivation(PaymentProof $paymentProof): void
    {
        $subscription = $paymentProof->subscription;
        $tenant = $subscription->tenant;

        // Update subscription
        $subscription->update([
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        // Update tenant
        $tenant->update(['status' => 'active']);

        // Update payment transaction
        $transaction = $paymentProof->paymentTransaction;
        if ($transaction) {
            $systemUser = User::where('is_super_admin', true)->first();
            $transaction->approve($systemUser);
        }

        // Mark invoice as paid
        $invoice = Invoice::where('subscription_id', $subscription->id)
            ->unpaid()
            ->first();

        if ($invoice) {
            $invoice->markAsPaid(
                'manual',
                $transaction->transaction_id ?? null,
                'Pago aprobado mediante prueba de comprobante'
            );
        }
    }

    /**
     * Process subscription rejection
     */
    private function processSubscriptionRejection(PaymentProof $paymentProof): void
    {
        $subscription = $paymentProof->subscription;
        if (!$subscription) {
            return;
        }

        // Update subscription payment status
        $subscription->update(['payment_status' => 'failed']);

        // Check if tenant should be suspended
        $tenant = $subscription->tenant;
        $failedPayments = PaymentProof::where('tenant_id', $tenant->id)
            ->where('subscription_id', $subscription->id)
            ->rejected()
            ->count();

        // Suspend tenant after 3 failed payments
        if ($failedPayments >= 3) {
            $tenant->update(['status' => 'suspended']);
            $subscription->update(['status' => 'suspended']);
        }

        // Update payment transaction
        $transaction = $paymentProof->paymentTransaction;
        if ($transaction) {
            $systemUser = User::where('is_super_admin', true)->first();
            $transaction->reject($systemUser, 'Prueba de pago rechazada');
        }
    }

    /**
     * Send approval notifications
     */
    private function sendApprovalNotifications(PaymentProof $paymentProof, User $approver): void
    {
        try {
            $tenant = $paymentProof->tenant;

            // Notify tenant
            if ($tenant->contact_email) {
                Notification::route('mail', $tenant->contact_email)
                    ->notify(new PaymentProofApproved($paymentProof, $tenant));
            }

            // Notify subscription activated
            $tenant->users()->each(function ($user) use ($paymentProof) {
                $user->notify(new SubscriptionActivated($paymentProof->subscription));
            });

            // Log notification
            Log::info('Approval notifications sent', [
                'payment_proof_id' => $paymentProof->id,
                'tenant_id' => $tenant->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending approval notifications', [
                'payment_proof_id' => $paymentProof->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send rejection notifications
     */
    private function sendRejectionNotifications(PaymentProof $paymentProof, User $rejector, string $reason): void
    {
        try {
            $tenant = $paymentProof->tenant;

            // Notify tenant
            if ($tenant->contact_email) {
                Notification::route('mail', $tenant->contact_email)
                    ->notify(new PaymentProofRejected($paymentProof, $tenant, $reason));
            }

            // Log notification
            Log::info('Rejection notifications sent', [
                'payment_proof_id' => $paymentProof->id,
                'tenant_id' => $tenant->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending rejection notifications', [
                'payment_proof_id' => $paymentProof->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get approval statistics
     */
    public function getApprovalStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = PaymentProof::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $approved = $query->approved()->count();
        $rejected = $query->rejected()->count();
        $pending = $query->pending()->count();

        return [
            'total' => $total,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0,
            'rejection_rate' => $total > 0 ? ($rejected / $total) * 100 : 0,
            'average_processing_time_hours' => $this->getAverageProcessingTime($query),
        ];
    }

    /**
     * Get average processing time
     */
    private function getAverageProcessingTime($query): float
    {
        $processedQuery = clone $query;

        $processedPayments = $processedQuery->whereIn('status', [
            PaymentProof::STATUS_APPROVED,
            PaymentProof::STATUS_REJECTED,
        ])
        ->whereNotNull('reviewed_at')
        ->get();

        if ($processedPayments->isEmpty()) {
            return 0;
        }

        $totalHours = $processedPayments->sum(function ($payment) {
            return $payment->created_at->diffInHours($payment->reviewed_at);
        });

        return $totalHours / $processedPayments->count();
    }
}