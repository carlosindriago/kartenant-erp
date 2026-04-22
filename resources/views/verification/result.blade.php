@extends('verification.layout')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    @if($result === 'valid')
        {{-- Documento VÁLIDO --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-4xl text-green-500"></i>
                        </div>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold">Documento Legítimo</h2>
                        <p class="text-green-100">Este documento fue generado por el sistema y es auténtico</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-file-alt text-blue-500"></i>
                            <span class="text-sm font-medium text-gray-600">Tipo de Documento</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">{{ $document_type }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-calendar text-blue-500"></i>
                            <span class="text-sm font-medium text-gray-600">Fecha de Generación</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $verification->generated_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $verification->generated_at->diffForHumans() }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-eye text-blue-500"></i>
                            <span class="text-sm font-medium text-gray-600">Verificaciones</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $verification->verification_count }} {{ $verification->verification_count === 1 ? 'vez' : 'veces' }}
                        </p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-sm font-medium text-gray-600">Estado</span>
                        </div>
                        <p class="text-lg font-semibold text-green-600">Válido</p>
                    </div>
                </div>

                @if(!empty($metadata))
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-blue-500"></i> Información Adicional
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <dl class="space-y-2">
                            @foreach($metadata as $key => $value)
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                    <dd class="text-sm text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
                @endif

                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Código de Verificación:</h3>
                    <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all text-gray-800">
                        {{ $hash }}
                    </div>
                </div>
            </div>
        </div>

    @elseif($result === 'expired')
        {{-- Documento EXPIRADO --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-4xl text-yellow-500"></i>
                        </div>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold">Documento Expirado</h2>
                        <p class="text-yellow-100">Este documento era legítimo pero ha expirado</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-yellow-800">
                        <i class="fas fa-exclamation-triangle"></i> 
                        El documento fue generado correctamente pero ha superado su fecha de vigencia.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-600">Generado:</span>
                        <p class="font-semibold">{{ $verification->generated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Expiró:</span>
                        <p class="font-semibold">{{ $verification->expires_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

    @elseif($result === 'invalid')
        {{-- Documento INVALIDADO --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-ban text-4xl text-orange-500"></i>
                        </div>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold">Documento Invalidado</h2>
                        <p class="text-orange-100">Este documento ha sido invalidado manualmente</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <p class="text-orange-800">
                        <i class="fas fa-info-circle"></i> 
                        El documento fue generado originalmente por el sistema pero ha sido marcado como inválido.
                    </p>
                </div>
            </div>
        </div>

    @else
        {{-- Documento NO ENCONTRADO --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-times-circle text-4xl text-red-500"></i>
                        </div>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold">Documento No Verificable</h2>
                        <p class="text-red-100">{{ $message }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-red-800 font-semibold mb-2">
                        <i class="fas fa-exclamation-triangle"></i> Advertencia de Seguridad
                    </p>
                    <p class="text-red-700 text-sm">
                        Este código de verificación no fue encontrado en nuestro sistema. Posibles razones:
                    </p>
                    <ul class="list-disc list-inside text-red-700 text-sm mt-2 space-y-1">
                        <li>El documento no fue generado por nuestro sistema</li>
                        <li>El código fue escrito incorrectamente</li>
                        <li>El documento puede ser una falsificación</li>
                    </ul>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Código ingresado:</h3>
                    <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all text-gray-800">
                        {{ $hash }}
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('verify.index') }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i>
                        Intentar con otro código
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('verify.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
            <i class="fas fa-redo"></i>
            Verificar Otro Documento
        </a>
    </div>
</div>
@endsection
