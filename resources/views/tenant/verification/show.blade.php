<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación: {{ $documentType }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $documentType }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Documento verificado exitosamente</p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Document Info -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Información del Documento</h2>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    @foreach($documentData as $key => $value)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if(is_numeric($value) && strpos($key, 'balance') !== false || strpos($key, 'total') !== false || strpos($key, 'difference') !== false)
                                    ${{ number_format($value, 2) }}
                                @elseif($value instanceof \Carbon\Carbon || $value instanceof \DateTime)
                                    {{ $value->format('d/m/Y H:i:s') }}
                                @else
                                    {{ $value ?? 'N/A' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <!-- Verification Stats -->
            <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Estadísticas de Verificación</h2>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-blue-900">Total Verificaciones</p>
                                <p class="text-2xl font-bold text-blue-600">{{ $verificationCount }}</p>
                            </div>
                        </div>
                    </div>

                    @if($lastVerification)
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-green-900">Última Verificación</p>
                                <p class="text-sm text-green-700">{{ \Carbon\Carbon::parse($lastVerification['verified_at'])->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Verification History -->
            @if($verificationHistory->count() > 0)
            <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Historial de Verificaciones</h2>
                
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($verificationHistory as $index => $verification)
                        <li>
                            <div class="relative pb-8">
                                @if($index < $verificationHistory->count() - 1)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $verification['verified_by_name'] }}</p>
                                            <p class="text-xs text-gray-500">
                                                @if(!empty($verification['user_role']))
                                                    {{ implode(', ', $verification['user_role']) }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-700">
                                            <p>{{ \Carbon\Carbon::parse($verification['verified_at'])->format('d/m/Y H:i:s') }}</p>
                                            @if($verification['ip_address'])
                                            <p class="text-xs text-gray-500 mt-1">IP: {{ $verification['ip_address'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('tenant.internal-verification.pdf', $document->verification_hash) }}" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar PDF
                    </a>
                    
                    <a href="/app" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Sistema
                    </a>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Este documento ha sido verificado por {{ $user->name }}</p>
                <p class="mt-1">Hash de verificación: <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $document->verification_hash }}</code></p>
            </div>
        </div>
    </div>
</body>
</html>
