<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\V1\BaseApiController;
use App\Http\Requests\API\V1\StorePaymentProofRequest;
use App\Http\Requests\API\V1\UpdatePaymentProofRequest;
use App\Models\PaymentProof;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\PaymentProofService;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Tenant Billing API Controller
 *
 * Handles billing operations for authenticated tenants
 * Provides endpoints for payment proof management and subscription status
 *
 * @package App\Http\Controllers\API\V1
 */
class TenantBillingController extends BaseApiController
{
    public function __construct(
        private PaymentProofService $paymentProofService,
        private SubscriptionService $subscriptionService
    ) {
        // Apply tenant authentication middleware
        $this->middleware('auth:tenant');
    }

    /**
     * Get tenant billing dashboard
     *
     * Returns current subscription status, recent payment proofs,
     * and billing summary for the authenticated tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Get current subscription
            $subscription = $this->subscriptionService->getCurrentSubscription($tenant);

            // Get payment settings for validation rules
            $paymentSettings = PaymentSettings::on('landlord')->first();

            // Get recent payment proofs for this tenant
            $recentPayments = PaymentProof::on('landlord')
                ->where('tenant_id', $tenant->id)
                ->with(['invoice'])
                ->latest()
                ->take(10)
                ->get();

            // Calculate billing statistics
            $stats = $this->calculateBillingStats($tenant, $subscription);

            return $this->successResponse([
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'plan_name' => $subscription->plan_name ?? 'Default Plan',
                    'price' => $subscription->price,
                    'currency' => $subscription->currency,
                    'starts_at' => $subscription->starts_at,
                    'ends_at' => $subscription->ends_at,
                    'is_trial' => $subscription->is_trial ?? false,
                    'is_active' => $subscription->is_active ?? false,
                    'days_remaining' => $subscription->ends_at
                        ? now()->diffInDays($subscription->ends_at)
                        : null,
                ] : null,
                'payment_settings' => [
                    'max_file_size_mb' => $paymentSettings->max_file_size_mb ?? 5,
                    'allowed_file_types' => $paymentSettings->allowed_file_types ?? ['pdf', 'jpg', 'jpeg', 'png'],
                    'bank_account_info' => $paymentSettings->bank_account_info ?? null,
                    'payment_instructions' => $paymentSettings->payment_instructions ?? null,
                ],
                'recent_payments' => $recentPayments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'payment_method' => $payment->payment_method,
                        'payment_date' => $payment->payment_date,
                        'status' => $payment->status,
                        'status_display' => $payment->status_color,
                        'payment_method_display' => $payment->payment_method_display,
                        'file_count' => count($payment->file_paths ?? []),
                        'created_at' => $payment->created_at,
                        'invoice_number' => $payment->invoice?->invoice_number,
                    ];
                }),
                'billing_stats' => $stats,
            ], 'Billing dashboard retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Tenant billing dashboard error', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error retrieving billing information',
                code: 'BILLING_DASHBOARD_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Store a new payment proof
     *
     * Handles file uploads and payment data submission
     * Validates against payment settings and enforces tenant isolation
     *
     * @param StorePaymentProofRequest $request
     * @return JsonResponse
     */
    public function store(StorePaymentProofRequest $request): JsonResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Begin database transaction for atomic operation
            return DB::connection('landlord')->transaction(function () use ($request, $tenant, $user) {

                // Get or create active subscription for this tenant
                $subscription = $this->getOrCreateSubscription($tenant);

                // Upload and validate files
                $uploadResult = $this->paymentProofService->uploadPaymentProofFiles(
                    $request->file('files', []),
                    $subscription
                );

                if (!$uploadResult['success']) {
                    return $this->validationErrorResponse(
                        $uploadResult['errors'],
                        'File upload validation failed'
                    );
                }

                // Validate payment data
                $validationResult = $this->paymentProofService->validatePaymentProofData(
                    $request->validated(),
                    $subscription
                );

                if (!$validationResult['valid']) {
                    return $this->validationErrorResponse(
                        $validationResult['errors'],
                        'Payment data validation failed'
                    );
                }

                // Create payment proof record
                $paymentProof = $this->paymentProofService->createPaymentProof(
                    $validationResult['data'],
                    $uploadResult['uploaded_files'],
                    $subscription
                );

                // Enhance response with file URLs
                $paymentProofData = $paymentProof->fresh(['subscription', 'tenant']);
                $fileUrls = $this->paymentProofService->getPaymentProofFileUrls($paymentProof);

                Log::info('Payment proof uploaded via API', [
                    'payment_proof_id' => $paymentProof->id,
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'amount' => $paymentProof->amount,
                    'files_count' => count($fileUrls),
                ]);

                return $this->createdResponse([
                    'payment_proof' => [
                        'id' => $paymentProof->id,
                        'amount' => $paymentProof->amount,
                        'currency' => $paymentProof->currency,
                        'payment_method' => $paymentProof->payment_method,
                        'payment_date' => $paymentProof->payment_date,
                        'reference_number' => $paymentProof->reference_number,
                        'status' => $paymentProof->status,
                        'status_display' => $paymentProof->status_color,
                        'payment_method_display' => $paymentProof->payment_method_display,
                        'notes' => $paymentProof->notes,
                        'files' => $fileUrls,
                        'total_file_size_mb' => $paymentProof->total_file_size_mb,
                        'created_at' => $paymentProof->created_at,
                    ],
                    'subscription' => [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'ends_at' => $subscription->ends_at,
                    ],
                ], 'Payment proof submitted successfully');

            }, 3); // Retry deadlock attempts

        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );
        } catch (\Exception $e) {
            Log::error('Payment proof submission error via API', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error submitting payment proof',
                code: 'PAYMENT_PROOF_SUBMISSION_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Show specific payment proof details
     *
     * Returns detailed information about a payment proof
     * Includes file URLs and download links
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Find payment proof with tenant isolation
            $paymentProof = PaymentProof::on('landlord')
                ->where('id', $id)
                ->where('tenant_id', $tenant->id)
                ->with(['subscription', 'invoice', 'reviewer'])
                ->first();

            if (!$paymentProof) {
                return $this->notFoundResponse('Payment proof not found');
            }

            // Get file URLs
            $fileUrls = $this->paymentProofService->getPaymentProofFileUrls($paymentProof);

            return $this->successResponse([
                'payment_proof' => [
                    'id' => $paymentProof->id,
                    'amount' => $paymentProof->amount,
                    'currency' => $paymentProof->currency,
                    'payment_method' => $paymentProof->payment_method,
                    'payment_date' => $paymentProof->payment_date,
                    'reference_number' => $paymentProof->reference_number,
                    'payer_name' => $paymentProof->payer_name,
                    'status' => $paymentProof->status,
                    'status_display' => $paymentProof->status_color,
                    'payment_method_display' => $paymentProof->payment_method_display,
                    'notes' => $paymentProof->notes,
                    'rejection_reason' => $paymentProof->rejection_reason,
                    'review_notes' => $paymentProof->review_notes,
                    'reviewed_by' => $paymentProof->reviewer?->name,
                    'reviewed_at' => $paymentProof->reviewed_at,
                    'files' => $fileUrls,
                    'total_file_size_mb' => $paymentProof->total_file_size_mb,
                    'created_at' => $paymentProof->created_at,
                    'updated_at' => $paymentProof->updated_at,
                ],
                'subscription' => $paymentProof->subscription ? [
                    'id' => $paymentProof->subscription->id,
                    'status' => $paymentProof->subscription->status,
                    'plan_name' => $paymentProof->subscription->plan_name ?? 'Default Plan',
                ] : null,
                'invoice' => $paymentProof->invoice ? [
                    'id' => $paymentProof->invoice->id,
                    'invoice_number' => $paymentProof->invoice->invoice_number,
                    'amount' => $paymentProof->invoice->amount,
                    'status' => $paymentProof->invoice->status,
                ] : null,
            ], 'Payment proof details retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Payment proof retrieval error via API', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'payment_proof_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error retrieving payment proof details',
                code: 'PAYMENT_PROOF_RETRIEVAL_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Delete (soft delete) a payment proof
     *
     * Allows tenants to delete their own payment proofs
     * Only allows deletion of pending payment proofs
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Find payment proof with tenant isolation
            $paymentProof = PaymentProof::on('landlord')
                ->where('id', $id)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$paymentProof) {
                return $this->notFoundResponse('Payment proof not found');
            }

            // Business rule: Only allow deletion of pending payment proofs
            if ($paymentProof->status !== PaymentProof::STATUS_PENDING) {
                return $this->forbiddenResponse(
                    'Only pending payment proofs can be deleted'
                );
            }

            return DB::connection('landlord')->transaction(function () use ($paymentProof, $user) {

                // Delete associated files
                $filesDeleted = $this->paymentProofService->deletePaymentProofFiles($paymentProof);

                if (!$filesDeleted) {
                    Log::warning('Some files could not be deleted during payment proof removal', [
                        'payment_proof_id' => $paymentProof->id,
                        'tenant_id' => $paymentProof->tenant_id,
                    ]);
                }

                // Soft delete payment proof
                $deleted = $paymentProof->delete();

                if (!$deleted) {
                    return $this->errorResponse(
                        message: 'Failed to delete payment proof',
                        code: 'PAYMENT_PROOF_DELETION_FAILED',
                        statusCode: 500
                    );
                }

                Log::info('Payment proof deleted via API', [
                    'payment_proof_id' => $paymentProof->id,
                    'tenant_id' => $paymentProof->tenant_id,
                    'user_id' => $user->id,
                    'amount' => $paymentProof->amount,
                    'files_deleted' => $filesDeleted,
                ]);

                return $this->noContentResponse();

            }, 3); // Retry deadlock attempts

        } catch (\Exception $e) {
            Log::error('Payment proof deletion error via API', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'payment_proof_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error deleting payment proof',
                code: 'PAYMENT_PROOF_DELETION_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Get payment history for current tenant
     *
     * Returns paginated list of all payment proofs for the tenant
     * Includes filtering and sorting options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Build query with filters
            $query = PaymentProof::on('landlord')
                ->where('tenant_id', $tenant->id)
                ->with(['subscription', 'invoice']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->input('payment_method'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('payment_date', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('payment_date', '<=', $request->input('date_to'));
            }

            // Sort by latest first
            $query->latest();

            // Paginate results
            $payments = $query->paginate(
                perPage: min($request->input('per_page', 20), 100),
                page: $request->input('page', 1)
            );

            // Transform data
            $transformedPayments = $payments->getCollection()->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'reference_number' => $payment->reference_number,
                    'status' => $payment->status,
                    'status_display' => $payment->status_color,
                    'payment_method_display' => $payment->payment_method_display,
                    'notes' => $payment->notes,
                    'file_count' => count($payment->file_paths ?? []),
                    'total_file_size_mb' => $payment->total_file_size_mb,
                    'created_at' => $payment->created_at,
                    'invoice_number' => $payment->invoice?->invoice_number,
                    'subscription_id' => $payment->subscription_id,
                ];
            });

            // Create a new collection with transformed data
            $resource = new \Illuminate\Http\Resources\Json\ResourceCollection($transformedPayments);
            $resource->resource = $payments;

            return $this->successResponseWithPagination(
                $resource,
                'Payment history retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Payment history retrieval error via API', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error retrieving payment history',
                code: 'PAYMENT_HISTORY_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Calculate billing statistics for the tenant
     */
    private function calculateBillingStats(Tenant $tenant, ?TenantSubscription $subscription): array
    {
        try {
            // Get payment proof statistics
            $paymentStats = PaymentProof::on('landlord')
                ->where('tenant_id', $tenant->id)
                ->selectRaw('
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_payments,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected_payments,
                    SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_amount_paid,
                    MAX(payment_date) as last_payment_date
                ', [
                    PaymentProof::STATUS_PENDING,
                    PaymentProof::STATUS_APPROVED,
                    PaymentProof::STATUS_REJECTED,
                    PaymentProof::STATUS_APPROVED
                ])
                ->first();

            return [
                'total_payments' => (int) $paymentStats->total_payments,
                'pending_payments' => (int) $paymentStats->pending_payments,
                'approved_payments' => (int) $paymentStats->approved_payments,
                'rejected_payments' => (int) $paymentStats->rejected_payments,
                'total_amount_paid' => (float) $paymentStats->total_amount_paid,
                'last_payment_date' => $paymentStats->last_payment_date,
                'subscription_status' => $subscription?->status,
                'subscription_ends_at' => $subscription?->ends_at,
                'days_until_expiry' => $subscription?->ends_at
                    ? max(0, now()->diffInDays($subscription->ends_at))
                    : null,
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating billing stats', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_payments' => 0,
                'pending_payments' => 0,
                'approved_payments' => 0,
                'rejected_payments' => 0,
                'total_amount_paid' => 0,
                'last_payment_date' => null,
                'subscription_status' => $subscription?->status,
                'subscription_ends_at' => $subscription?->ends_at,
                'days_until_expiry' => null,
            ];
        }
    }

    /**
     * Download a payment proof file
     *
     * Allows authenticated tenants to download their own payment proof files
     * Uses tenant isolation and file access validation
     *
     * @param Request $request
     * @param int $id
     * @param string $file_path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function downloadFile(Request $request, int $id, string $file_path): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            /** @var Tenant $tenant */
            $tenant = tenant();
            /** @var User $user */
            $user = Auth::guard('tenant')->user();

            if (!$tenant || !$user) {
                return $this->unauthorizedResponse('Tenant authentication required');
            }

            // Find payment proof with tenant isolation
            $paymentProof = PaymentProof::on('landlord')
                ->where('id', $id)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$paymentProof) {
                return $this->notFoundResponse('Payment proof not found');
            }

            // Decode file path (URL encoded in route)
            $filePath = urldecode($file_path);

            // Validate file path against payment proof's file paths
            if (!in_array($filePath, $paymentProof->file_paths ?? [])) {
                Log::warning('Unauthorized file access attempt via API', [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'payment_proof_id' => $id,
                    'requested_file_path' => $filePath,
                    'allowed_file_paths' => $paymentProof->file_paths ?? [],
                    'ip' => $request->ip(),
                ]);

                return $this->forbiddenResponse('Access to this file is not authorized');
            }

            // Attempt to download file
            $response = $this->paymentProofService->downloadPaymentProofFile(
                $paymentProof,
                $filePath
            );

            if (!$response) {
                return $this->notFoundResponse('File not found');
            }

            Log::info('Payment proof file downloaded via API', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'payment_proof_id' => $id,
                'file_path' => $filePath,
                'ip' => $request->ip(),
            ]);

            // Set proper headers for file download
            $filename = basename($filePath);
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;

        } catch (\Exception $e) {
            Log::error('Payment proof file download error via API', [
                'tenant_id' => tenant()?->id,
                'user_id' => Auth::guard('tenant')->id(),
                'payment_proof_id' => $id,
                'file_path' => $file_path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                message: 'Error downloading file',
                code: 'FILE_DOWNLOAD_ERROR',
                statusCode: 500
            );
        }
    }

    /**
     * Get or create active subscription for tenant
     */
    private function getOrCreateSubscription(Tenant $tenant): TenantSubscription
    {
        // Look for active subscription
        $subscription = TenantSubscription::on('landlord')
            ->where('tenant_id', $tenant->id)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 'trial');
            })
            ->first();

        if (!$subscription) {
            // Create new trial subscription
            $subscription = TenantSubscription::on('landlord')->create([
                'tenant_id' => $tenant->id,
                'plan_id' => 1, // Default plan
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'currency' => 'ARS',
                'monthly_price' => 2990.00,
                'is_trial' => true,
                'is_active' => true,
            ]);

            Log::info('New trial subscription created via API', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        return $subscription;
    }
}