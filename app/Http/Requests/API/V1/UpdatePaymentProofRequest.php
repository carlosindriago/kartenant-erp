<?php

namespace App\Http\Requests\API\V1;

use App\Models\PaymentProof;
use App\Models\TenantSubscription;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * Update Payment Proof API Request
 *
 * Validates payment proof updates for tenant billing API
 * Typically used for editing payment details before approval
 */
class UpdatePaymentProofRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be authenticated as tenant user
        if (! Auth::guard('tenant')->check() || ! tenant()) {
            return false;
        }

        // Get the payment proof to verify ownership and status
        $paymentProof = $this->route('payment_proof');

        if (! $paymentProof) {
            return false;
        }

        // Only allow updating of pending payment proofs
        return $paymentProof->tenant_id === tenant()->id &&
               $paymentProof->status === PaymentProof::STATUS_PENDING;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Payment information (all optional for updates)
            'payment_method' => [
                'sometimes',
                'required',
                'string',
                'in:bank_transfer,cash,mobile_money,other',
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'decimal:0,2',
                'min:0.01',
                'max:999999.99',
            ],
            'payment_date' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:'.now()->subDays(90)->toDateString(),
            ],
            'reference_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_\/\s]+$/',
            ],
            'payer_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-záéíóúÁÉÍÓÚñÑ\s\-\.,]+$/u',
            ],
            'notes' => [
                'sometimes',
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
        $paymentProof = $this->route('payment_proof');

        if (! $paymentProof) {
            return;
        }

        // Validate payment amount matches expected subscription price (if amount is being updated)
        if ($this->has('amount')) {
            $subscription = TenantSubscription::on('landlord')
                ->where('id', $paymentProof->subscription_id)
                ->first();

            if ($subscription) {
                $expectedAmount = (float) $subscription->price ?? 0;
                $actualAmount = (float) $this->input('amount');

                // Allow 1% tolerance for rounding differences
                $tolerance = $expectedAmount * 0.01;
                if ($expectedAmount > 0 && abs($actualAmount - $expectedAmount) > $tolerance) {
                    $validator->errors()->add('amount',
                        "El monto pagado ({$actualAmount}) no coincide con el monto esperado ({$expectedAmount}) con una tolerancia del 1%");
                }
            }
        }

        // Check for duplicate payment submissions (if key fields are being updated)
        $hasKeyFields = $this->has(['amount', 'payment_date', 'payment_method']);
        if ($hasKeyFields) {
            $duplicateExists = PaymentProof::on('landlord')
                ->where('tenant_id', tenant()->id)
                ->where('amount', $this->input('amount', $paymentProof->amount))
                ->where('payment_date', $this->input('payment_date', $paymentProof->payment_date))
                ->where('payment_method', $this->input('payment_method', $paymentProof->payment_method))
                ->where('id', '!=', $paymentProof->id) // Exclude current payment proof
                ->whereNotIn('status', ['rejected'])
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('duplicate',
                    'Ya existe un comprobante de pago con las mismas características pendiente de aprobación');
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
        $input = $this->all();

        if (isset($input['amount']) && $input['amount']) {
            $input['amount'] = number_format((float) str_replace(',', '.', $input['amount']), 2, '.', '');
        }

        if (isset($input['payment_date']) && $input['payment_date']) {
            $input['payment_date'] = date('Y-m-d', strtotime($input['payment_date']));
        }

        if (isset($input['reference_number']) && $input['reference_number']) {
            $input['reference_number'] = trim(strtoupper($input['reference_number']));
        }

        if (isset($input['payer_name']) && $input['payer_name']) {
            $input['payer_name'] = trim(ucwords(strtolower($input['payer_name'])));
        }

        if (isset($input['notes']) && $input['notes']) {
            $input['notes'] = trim($input['notes']);
        }

        $this->replace($input);
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
