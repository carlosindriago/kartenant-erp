<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBackgroundRequest extends FormRequest
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
            'background' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB
                'dimensions:min_width=1200,min_height=800'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'background.required' => 'Debe seleccionar una imagen para el fondo.',
            'background.file' => 'El archivo seleccionado no es válido.',
            'background.image' => 'El archivo debe ser una imagen.',
            'background.mimes' => 'El formato de imagen no es válido. Use: JPG, PNG o WebP.',
            'background.max' => 'La imagen es demasiado grande. Máximo permitido: 5MB.',
            'background.dimensions' => 'La imagen de fondo debe tener al menos 1200x800 píxeles.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'background' => 'imagen de fondo',
        ];
    }
}