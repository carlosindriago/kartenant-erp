<?php

namespace App\Http\Requests\API\V1;

use App\Models\PaymentProof;
use App\Models\PaymentSettings;
use App\Models\TenantSubscription;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Store Payment Proof API Request
 *
 * Validates payment proof submission data for tenant billing API
 * Includes file validation, payment data, and tenant security checks
 */
class StorePaymentProofRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be authenticated as tenant user
        return Auth::guard('tenant')->check() && tenant() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get current payment settings for dynamic validation
        $paymentSettings = PaymentSettings::on('landlord')->first();
        $maxFileSize = $paymentSettings->max_file_size_mb ?? 5; // Default 5MB
        $allowedTypes = $paymentSettings->allowed_file_types ?? ['pdf', 'jpg', 'jpeg', 'png'];

        return [
            // File validation
            'files' => [
                'required',
                'array',
                'min:1',
                'max:5', // Maximum 5 files per submission
            ],
            'files.*' => [
                'required',
                'file',
                'max:'.($maxFileSize * 1024), // Convert MB to KB
                function ($attribute, $value, $fail) use ($allowedTypes) {
                    if (! $value instanceof UploadedFile) {
                        $fail('El archivo no es válido');

                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, $allowedTypes)) {
                        $fail("El tipo de archivo '{$extension}' no está permitido. Tipos permitidos: ".implode(', ', $allowedTypes));
                    }
                },
            ],

            // Payment information
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'bank_transfer',
                    'cash',
                    'mobile_money',
                    'other',
                ]),
            ],
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0.01',
                'max:999999.99', // Reasonable maximum
            ],
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:'.now()->subDays(90)->toDateString(), // Max 90 days old
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_\/\s]+$/', // Alphanumeric with common separators
            ],
            'payer_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-záéíóúÁÉÍÓÚñÑ\s\-\.,]+$/u', // Names with accents
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Debe subir al menos un comprobante de pago',
            'files.array' => 'Los archivos deben ser enviados en formato de arreglo',
            'files.min' => 'Debe subir al menos un comprobante de pago',
            'files.max' => 'No puede subir más de 5 archivos a la vez',
            'files.*.required' => 'Todos los archivos son requeridos',
            'files.*.file' => 'El archivo no es válido',
            'files.*.max' => 'El tamaño máximo de archivo es :max KB',
            'files.*.mimes' => 'Formato de archivo no permitido',

            'payment_method.required' => 'El método de pago es obligatorio',
            'payment_method.in' => 'El método de pago seleccionado no es válido',

            'amount.required' => 'El monto del pago es obligatorio',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.decimal' => 'El monto debe tener máximo 2 decimales',
            'amount.min' => 'El monto debe ser mayor que 0',
            'amount.max' => 'El monto ingresado es demasiado alto',

            'payment_date.required' => 'La fecha de pago es obligatoria',
            'payment_date.date' => 'La fecha de pago no es válida',
            'payment_date.before_or_equal' => 'La fecha de pago no puede ser en el futuro',
            'payment_date.after_or_equal' => 'La fecha de pago no puede tener más de 90 días de antigüedad',

            'reference_number.string' => 'El número de referencia debe ser texto',
            'reference_number.max' => 'El número de referencia no puede superar los 100 caracteres',
            'reference_number.regex' => 'El número de referencia contiene caracteres inválidos',

            'payer_name.string' => 'El nombre del pagador debe ser texto',
            'payer_name.max' => 'El nombre del pagador no puede superar los 255 caracteres',
            'payer_name.regex' => 'El nombre del pagador contiene caracteres inválidos',

            'notes.string' => 'Las notas deben ser texto',
            'notes.max' => 'Las notas no pueden superar los 1000 caracteres',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files' => 'archivos',
            'files.*' => 'archivo',
            'payment_method' => 'método de pago',
            'amount' => 'monto',
            'payment_date' => 'fecha de pago',
            'reference_number' => 'número de referencia',
            'payer_name' => 'nombre del pagador',
            'notes' => 'notas',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateBusinessRules($validator);
        });
    }

    /**
     * Validate business rules and additional constraints
     */
    protected function validateBusinessRules($validator): void
    {
        // Get tenant and subscription
        $tenant = tenant();
        $subscription = null;

        if ($tenant) {
            $subscription = TenantSubscription::on('landlord')
                ->where('tenant_id', $tenant->id)
                ->where(function ($query) {
                    $query->where('status', 'active')
                        ->orWhere('status', 'trial');
                })
                ->first();
        }

        // Validate tenant exists
        if (! $tenant) {
            $validator->errors()->add('tenant', 'Tenant no válido');

            return;
        }

        // Validate subscription exists for payment amount validation
        if (! $subscription) {
            $validator->errors()->add('subscription', 'No se encontró una suscripción activa');

            return;
        }

        // Validate payment amount matches expected subscription price (with small tolerance)
        if ($this->has('amount')) {
            $expectedAmount = (float) $subscription->price ?? 0;
            $actualAmount = (float) $this->input('amount');

            // Allow 1% tolerance for rounding differences
            $tolerance = $expectedAmount * 0.01;
            if ($expectedAmount > 0 && abs($actualAmount - $expectedAmount) > $tolerance) {
                $validator->errors()->add('amount',
                    "El monto pagado ({$actualAmount}) no coincide con el monto esperado ({$expectedAmount}) con una tolerancia del 1%");
            }
        }

        // Check for duplicate payment submissions
        if ($this->has(['amount', 'payment_date', 'payment_method'])) {
            $duplicateExists = PaymentProof::on('landlord')
                ->where('tenant_id', $tenant->id)
                ->where('amount', $this->input('amount'))
                ->where('payment_date', $this->input('payment_date'))
                ->where('payment_method', $this->input('payment_method'))
                ->whereNotIn('status', ['rejected']) // Exclude rejected payments from duplicate check
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('duplicate',
                    'Ya existe un comprobante de pago con las mismas características pendiente de aprobación');
            }
        }

        // Validate total file size doesn't exceed limits
        if ($this->hasFile('files')) {
            $totalSize = 0;
            $maxTotalSize = 20 * 1024; // 20MB total limit

            foreach ($this->file('files') as $file) {
                if ($file && $file->isValid()) {
                    $totalSize += $file->getSize();
                }
            }

            if ($totalSize > $maxTotalSize) {
                $validator->errors()->add('files_total_size',
                    'El tamaño total de los archivos no puede superar los 20MB');
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Los datos proporcionados no son válidos',
                    'details' => $validator->errors()->toArray(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and normalize input data
        $this->merge([
            'amount' => $this->input('amount') ?
                number_format((float) str_replace(',', '.', $this->input('amount')), 2, '.', '') : null,
            'payment_date' => $this->input('payment_date') ?
                date('Y-m-d', strtotime($this->input('payment_date'))) : null,
            'reference_number' => $this->input('reference_number') ?
                trim(strtoupper($this->input('reference_number'))) : null,
            'payer_name' => $this->input('payer_name') ?
                trim(ucwords(strtolower($this->input('payer_name')))) : null,
            'notes' => $this->input('notes') ?
                trim($this->input('notes')) : null,
        ]);
    }

    /**
     * Get the sanitized validated data.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Additional sanitization
        if (isset($data['payer_name'])) {
            $data['payer_name'] = filter_var($data['payer_name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }

        if (isset($data['reference_number'])) {
            $data['reference_number'] = filter_var($data['reference_number'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }

        if (isset($data['notes'])) {
            $data['notes'] = filter_var($data['notes'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }

        return $data;
    }
}
