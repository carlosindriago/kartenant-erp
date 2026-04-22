<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-warning-800">
                        Cambio de Contraseña Obligatorio
                    </h3>
                    <div class="mt-2 text-sm text-warning-700">
                        <p>Por seguridad, debes cambiar tu contraseña temporal antes de continuar. Después de ingresar tu nueva contraseña, recibirás un código de verificación por email para confirmar el cambio.</p>
                    </div>
                </div>
            </div>
        </div>

        <x-filament-panels::form wire:submit="{{ $codeGenerated ? 'submit' : 'generateCode' }}">
            {{ $this->form }}

            <div class="fi-form-actions">
                @if($codeGenerated)
                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full"
                    >
                        Verificar y Cambiar Contraseña
                    </x-filament::button>
                @else
                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full"
                    >
                        Generar Código de Verificación
                    </x-filament::button>
                @endif
            </div>
        </x-filament-panels::form>
        
        @if($codeGenerated)
            <div class="text-center">
                <button 
                    wire:click="generateCode" 
                    type="button"
                    class="text-sm text-primary-600 hover:text-primary-500"
                >
                    ¿No recibiste el código? Reenviar
                </button>
            </div>
        @endif
        
        <div class="text-center">
            <form method="POST" action="{{ route('filament.app.auth.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-gray-500">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>
</x-filament-panels::page>
