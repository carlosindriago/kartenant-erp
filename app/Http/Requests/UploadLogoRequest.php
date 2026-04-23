<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('tenant')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=800,max_height=800',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Debe seleccionar una imagen para el logo.',
            'logo.file' => 'El archivo seleccionado no es válido.',
            'logo.image' => 'El archivo debe ser una imagen.',
            'logo.mimes' => 'El formato de imagen no es válido. Use: JPG, PNG o WebP.',
            'logo.max' => 'La imagen es demasiado grande. Máximo permitido: 2MB.',
            'logo.dimensions' => 'El logo debe tener dimensiones entre 100x100 y 800x800 píxeles.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'logo' => 'logo de la tienda',
        ];
    }
}
