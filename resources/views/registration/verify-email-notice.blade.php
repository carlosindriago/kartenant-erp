@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-3">Verifica tu Email</h1>
            <p class="text-lg text-gray-600">Te hemos enviado un email de verificación</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">
                    <p class="font-semibold">¡Registro exitoso!</p>
                    <p class="text-sm mt-1">{{ session('success') }}</p>
                </div>
            @endif

            <div class="space-y-4 text-gray-600">
                <p>Hemos enviado un email de verificación a:</p>
                <p class="text-center text-lg font-semibold text-purple-600">{{ session('email') }}</p>
                
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm">Por favor revisa tu bandeja de entrada y haz clic en el enlace de verificación para activar tu cuenta.</p>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>💡 Tip:</strong> Si no ves el email, revisa tu carpeta de spam o correo no deseado.
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('landing') }}" class="block text-center text-purple-600 font-semibold hover:text-purple-700">
                    ← Volver al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
