@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="inline-block mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </a>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Verifica tu Correo</h1>
            <p class="text-gray-600">Hemos enviado un código de 6 dígitos a<br><span class="font-semibold text-gray-900">{{ $email }}</span></p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('tenant.register.confirm') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-center">Código de Verificación</label>
                    <input type="text" name="code" required autofocus
                        class="w-full px-4 py-4 text-center text-3xl tracking-[0.5em] font-mono border-2 border-gray-200 rounded-xl focus:border-purple-600 focus:ring-0 transition uppercase"
                        placeholder="123456" maxlength="6">
                </div>

                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600">
                    <p class="mb-2"><span class="font-semibold">Empresa:</span> {{ $company_name }}</p>
                    <p><span class="font-semibold">Dominio:</span> {{ $domain }}.emporiodigital.test</p>
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-4 rounded-xl hover:from-purple-700 hover:to-indigo-700 transform hover:scale-[1.02] transition shadow-lg flex items-center justify-center gap-2">
                    <span>Verificar y Crear Cuenta</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    ¿No recibiste el código? 
                    <a href="{{ route('tenant.register.form') }}" class="text-purple-600 font-semibold hover:underline">Intentar de nuevo</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
