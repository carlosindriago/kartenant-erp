<?php

namespace App\Services;

use App\Models\PaymentProof;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentProofService
{
    public function __construct(
        private PaymentSettings $paymentSettings
    ) {}

    /**
     * Upload and validate payment proof files
     */
    public function uploadPaymentProofFiles(
        array $files,
        TenantSubscription $subscription
    ): array {
        $uploadedFiles = [];
        $totalSize = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                $errors[$index] = 'Archivo inválido';

                continue;
            }

            // Validate individual file
            $validation = $this->validateFile($file);
            if (! $validation['valid']) {
                $errors[$index] = $validation['error'];

                continue;
            }

            try {
                // Generate unique filename
                $filename = $this->generateUniqueFilename($file, $subscription);
                $path = $file->storeAs(
                    "payment-proofs/{$subscription->tenant_id}",
                    $filename,
                    'public'
                );

                $uploadedFiles[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];

                $totalSize += $file->getSize();

            } catch (\Exception $e) {
                $errors[$index] = 'Error al subir archivo: '.$e->getMessage();
                Log::error('Payment proof file upload error', [
                    'subscription_id' => $subscription->id,
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'uploaded_files' => $uploadedFiles,
            'total_size_mb' => $totalSize / 1024 / 1024,
            'errors' => $errors,
            'success' => empty($errors),
        ];
    }

    /**
     * Validate individual file
     */
    private function validateFile(UploadedFile $file): array
    {
        // Check file size
        $maxSizeBytes = $this->paymentSettings->getMaxFileSizeBytesAttribute();
        if ($file->getSize() > $maxSizeBytes) {
            return [
                'valid' => false,
                'error' => "El archivo excede el tamaño máximo de {$this->paymentSettings->max_file_size_mb}MB",
            ];
        }

        // Check file type
        $allowedTypes = $this->paymentSettings->allowed_file_types ?? ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => "Tipo de archivo '{$extension}' no permitido. Tipos permitidos: ".implode(', ', $allowedTypes),
            ];
        }

        // Additional validation for images
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            if (! $this->validateImageFile($file)) {
                return [
                    'valid' => false,
                    'error' => 'La imagen no parece ser válida o está corrupta',
                ];
            }
        }

        // Validation for PDF
        if ($extension === 'pdf') {
            if (! $this->validatePDFFile($file)) {
                return [
                    'valid' => false,
                    'error' => 'El archivo PDF no parece ser válido',
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Validate image file
     */
    private function validateImageFile(UploadedFile $file): bool
    {
        try {
            // Check if it's actually an image
            $imageInfo = @getimagesize($file->getPathname());

            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate PDF file
     */
    private function validatePDFFile(UploadedFile $file): bool
    {
        try {
            // Check PDF header
            $handle = fopen($file->getPathname(), 'rb');
            if (! $handle) {
                return false;
            }

            $header = fread($handle, 4);
            fclose($handle);

            return $header === '%PDF';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $file, TenantSubscription $subscription): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid()), 0, 8);

        // Sanitize base name
        $baseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 50); // Limit length

        return "{$baseName}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Validate payment proof data
     */
    public function validatePaymentProofData(array $data, TenantSubscription $subscription): array
    {
        $rules = [
            'payment_method' => ['required', 'string', 'in:bank_transfer,cash,mobile_money,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(30)->toDateString()],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'payment_method.required' => 'El método de pago es obligatorio',
            'payment_method.in' => 'El método de pago seleccionado no es válido',
            'amount.required' => 'El monto es obligatorio',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto debe ser mayor que 0',
            'payment_date.required' => 'La fecha de pago es obligatoria',
            'payment_date.date' => 'La fecha de pago no es válida',
            'payment_date.before_or_equal' => 'La fecha de pago no puede ser en el futuro',
            'payment_date.after_or_equal' => 'La fecha de pago no puede tener más de 30 días de antigüedad',
            'reference_number.max' => 'El número de referencia no puede superar los 100 caracteres',
            'payer_name.max' => 'El nombre del pagador no puede superar los 255 caracteres',
            'notes.max' => 'Las notas no pueden superar los 1000 caracteres',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Business validation
        $validatedData = $validator->validated();

        // Check if amount matches expected subscription amount
        $expectedAmount = (float) $subscription->price;
        $actualAmount = (float) $validatedData['amount'];

        if (abs($actualAmount - $expectedAmount) > 0.01) {
            return [
                'valid' => false,
                'errors' => [
                    'amount' => [
                        "El monto pagado ({$actualAmount}) no coincide con el monto esperado ({$expectedAmount})",
                    ],
                ],
            ];
        }

        // Check for duplicate payment proofs
        $duplicateExists = PaymentProof::where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->where('amount', $actualAmount)
            ->where('payment_date', $validatedData['payment_date'])
            ->where('payment_method', $validatedData['payment_method'])
            ->whereNotIn('status', [PaymentProof::STATUS_REJECTED])
            ->exists();

        if ($duplicateExists) {
            return [
                'valid' => false,
                'errors' => [
                    'duplicate' => [
                        'Ya existe una prueba de pago con las mismas características pendiente de aprobación',
                    ],
                ],
            ];
        }

        return [
            'valid' => true,
            'data' => $validatedData,
        ];
    }

    /**
     * Create payment proof
     */
    public function createPaymentProof(
        array $validatedData,
        array $uploadedFiles,
        TenantSubscription $subscription
    ): PaymentProof {
        $filePaths = array_column($uploadedFiles, 'path');
        $totalSizeMb = array_sum(array_column($uploadedFiles, 'size')) / 1024 / 1024;
        $mainFileType = ! empty($uploadedFiles) ? strtolower(pathinfo($filePaths[0], PATHINFO_EXTENSION)) : null;

        $paymentProof = PaymentProof::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'payment_method' => $validatedData['payment_method'],
            'amount' => $validatedData['amount'],
            'currency' => $subscription->currency,
            'payment_date' => $validatedData['payment_date'],
            'reference_number' => $validatedData['reference_number'] ?? null,
            'payer_name' => $validatedData['payer_name'] ?? null,
            'notes' => $validatedData['notes'] ?? null,
            'file_paths' => $filePaths,
            'file_type' => $mainFileType,
            'total_file_size_mb' => $totalSizeMb,
            'status' => PaymentProof::STATUS_PENDING,
            'metadata' => [
                'uploaded_files' => $uploadedFiles,
                'submission_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'browser_info' => [
                    'platform' => request()->header('User-Agent'),
                    'language' => request()->getPreferredLanguage(),
                ],
            ],
        ]);

        Log::info('Payment proof created', [
            'payment_proof_id' => $paymentProof->id,
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'amount' => $paymentProof->amount,
            'files_count' => count($filePaths),
            'total_size_mb' => $totalSizeMb,
        ]);

        return $paymentProof;
    }

    /**
     * Delete payment proof files
     */
    public function deletePaymentProofFiles(PaymentProof $paymentProof): bool
    {
        $deleted = true;
        $errors = [];

        foreach ($paymentProof->file_paths ?? [] as $filePath) {
            try {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            } catch (\Exception $e) {
                $deleted = false;
                $errors[] = "Error deleting file {$filePath}: ".$e->getMessage();
                Log::error('Error deleting payment proof file', [
                    'payment_proof_id' => $paymentProof->id,
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $deleted) {
            Log::warning('Some files could not be deleted', [
                'payment_proof_id' => $paymentProof->id,
                'errors' => $errors,
            ]);
        }

        return $deleted;
    }

    /**
     * Get payment proof file URLs
     */
    public function getPaymentProofFileUrls(PaymentProof $paymentProof): array
    {
        $urls = [];

        foreach ($paymentProof->file_paths ?? [] as $filePath) {
            try {
                if (Storage::disk('public')->exists($filePath)) {
                    $urls[] = [
                        'path' => $filePath,
                        'url' => Storage::disk('public')->url($filePath),
                        'filename' => basename($filePath),
                        'size' => Storage::disk('public')->size($filePath),
                        'last_modified' => Storage::disk('public')->lastModified($filePath),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error getting file URL', [
                    'payment_proof_id' => $paymentProof->id,
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $urls;
    }

    /**
     * Download payment proof file
     */
    public function downloadPaymentProofFile(PaymentProof $paymentProof, string $filePath): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            // Check if file belongs to this payment proof
            if (! in_array($filePath, $paymentProof->file_paths ?? [])) {
                return null;
            }

            if (Storage::disk('public')->exists($filePath)) {
                return Storage::disk('public')->download($filePath);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error downloading payment proof file', [
                'payment_proof_id' => $paymentProof->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStatistics(): array
    {
        $totalFiles = 0;
        $totalSize = 0;
        $byType = [];

        PaymentProof::chunk(100, function ($paymentProofs) use (&$totalFiles, &$totalSize, &$byType) {
            foreach ($paymentProofs as $paymentProof) {
                $totalFiles += count($paymentProof->file_paths ?? []);

                foreach ($paymentProof->file_paths ?? [] as $filePath) {
                    try {
                        $size = Storage::disk('public')->size($filePath);
                        $totalSize += $size;

                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $byType[$extension] = ($byType[$extension] ?? 0) + $size;
                    } catch (\Exception $e) {
                        // Ignore files that don't exist
                    }
                }
            }
        });

        return [
            'total_files' => $totalFiles,
            'total_size_mb' => $totalSize / 1024 / 1024,
            'total_size_gb' => $totalSize / 1024 / 1024 / 1024,
            'by_type' => array_map(function ($size) {
                return [
                    'size_mb' => $size / 1024 / 1024,
                    'percentage' => 0, // Will be calculated below
                ];
            }, $byType),
        ];
    }

    /**
     * Clean up orphaned files
     */
    public function cleanupOrphanedFiles(): array
    {
        $orphanedFiles = [];
        $deletedFiles = 0;
        $freedSpace = 0;

        // Get all files in payment-proofs directory
        $storagePath = storage_path('app/public/payment-proofs');
        if (is_dir($storagePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = 'payment-proofs/'.$file->getFilename();
                    $isOrphaned = true;

                    // Check if file exists in any payment proof
                    PaymentProof::chunk(100, function ($paymentProofs) use ($relativePath, &$isOrphaned) {
                        foreach ($paymentProofs as $paymentProof) {
                            if (in_array($relativePath, $paymentProof->file_paths ?? [])) {
                                $isOrphaned = false;

                                return false; // Stop checking
                            }
                        }
                    });

                    if ($isOrphaned) {
                        $orphanedFiles[] = [
                            'path' => $relativePath,
                            'size' => $file->getSize(),
                        ];

                        try {
                            if (Storage::disk('public')->exists($relativePath)) {
                                Storage::disk('public')->delete($relativePath);
                                $deletedFiles++;
                                $freedSpace += $file->getSize();
                            }
                        } catch (\Exception $e) {
                            Log::error('Error deleting orphaned file', [
                                'file_path' => $relativePath,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        Log::info('Orphaned files cleanup completed', [
            'found_orphaned' => count($orphanedFiles),
            'deleted_files' => $deletedFiles,
            'freed_space_mb' => $freedSpace / 1024 / 1024,
        ]);

        return [
            'found_orphaned' => count($orphanedFiles),
            'deleted_files' => $deletedFiles,
            'freed_space_mb' => $freedSpace / 1024 / 1024,
            'orphaned_files' => $orphanedFiles,
        ];
    }

    /**
     * Simple store method for BillingController integration
     */
    public function storePaymentProof(
        UploadedFile $file,
        array $data,
        Tenant $tenant,
        User $user
    ): PaymentProof {
        // Get or create subscription
        $subscription = TenantSubscription::on('landlord')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            $subscription = TenantSubscription::on('landlord')->create([
                'tenant_id' => $tenant->id,
                'plan_id' => 1, // Default plan
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'currency' => 'ARS',
                'monthly_price' => 2990.00,
            ]);
        }

        // Upload file
        $uploadResult = $this->uploadPaymentProofFiles([$file], $subscription);

        if (! $uploadResult['success']) {
            throw new \Exception('Error uploading file: '.implode(', ', $uploadResult['errors']));
        }

        // Validate payment data
        $validationResult = $this->validatePaymentProofData([
            'payment_method' => 'bank_transfer',
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'notes' => $data['notes'] ?? null,
        ], $subscription);

        if (! $validationResult['valid']) {
            throw new \Exception('Validation error: '.implode(', ', array_flatten($validationResult['errors'])));
        }

        // Create payment proof
        return $this->createPaymentProof(
            $validationResult['data'],
            $uploadResult['uploaded_files'],
            $subscription
        );
    }

    /**
     * Validate upload limits
     */
    public function validateUploadLimits(int $fileCount, float $totalSizeMb): array
    {
        $maxFiles = 5; // Maximum files per upload
        $maxTotalSizeMb = $this->paymentSettings->max_file_size_mb;

        if ($fileCount > $maxFiles) {
            return [
                'valid' => false,
                'error' => "No se pueden subir más de {$maxFiles} archivos a la vez",
            ];
        }

        if ($totalSizeMb > $maxTotalSizeMb) {
            return [
                'valid' => false,
                'error' => "El tamaño total de los archivos no puede superar {$maxTotalSizeMb}MB",
            ];
        }

        return ['valid' => true];
    }
}
