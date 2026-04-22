<x-filament-panels::page>
    {{-- Hero Section --}}
    <div class="mb-6">
        <div class="bg-gradient-to-r from-success-500 to-success-600 rounded-xl p-8 text-white shadow-lg">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <x-heroicon-o-check-circle class="w-10 h-10" />
                    </div>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-1">¡Empleado Registrado Exitosamente!</h2>
                    <p class="text-white/90">{{ $user->name }} ha sido dado de alta en el sistema</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Información del Empleado --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Datos Personales --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5" />
                    <span>Datos del Empleado</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-user-circle class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Nombre Completo</div>
                        <div class="font-semibold">{{ $user->name }}</div>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <x-heroicon-o-envelope class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Email</div>
                        <div class="font-semibold font-mono text-sm">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <x-heroicon-o-check-badge class="w-5 h-5 text-success-500 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Estado</div>
                        <div class="font-semibold text-success-600">Activo</div>
                    </div>
                </div>

                @if($user->roles->count() > 0)
                <div class="flex items-start gap-3">
                    <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Roles Asignados</div>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($user->roles as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Información del Comprobante --}}
        @if($event)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-check class="w-5 h-5" />
                    <span>Comprobante Verificable</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-hashtag class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Número de Documento</div>
                        <div class="font-semibold font-mono">{{ $event->document_number }}</div>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <x-heroicon-o-calendar class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Fecha de Registro</div>
                        <div class="font-semibold">{{ $event->changed_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                </div>

                @if($event->verification_hash)
                <div class="flex items-start gap-3">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                        <div class="text-sm text-gray-500">Hash de Verificación</div>
                        <div class="font-mono text-xs text-gray-600 break-all">{{ substr($event->verification_hash, 0, 32) }}...</div>
                    </div>
                </div>
                @endif

                <div class="mt-4 p-3 bg-success-50 border border-success-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-600 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-success-800">
                            El comprobante ha sido generado exitosamente y contiene un código QR verificable.
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
        @endif
    </div>

    {{-- Email Status --}}
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-envelope class="w-5 h-5" />
                <span>Email de Bienvenida</span>
            </div>
        </x-slot>

        @if($registrationData && $registrationData['email_sent'])
            <div class="bg-info-50 border border-info-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-paper-airplane class="w-6 h-6 text-info-600 flex-shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-semibold text-info-900 mb-1">Email Enviado Exitosamente</h3>
                        <p class="text-sm text-info-700 mb-2">
                            Se ha enviado un email a <strong class="font-mono">{{ $user->email }}</strong> con las credenciales de acceso.
                        </p>
                        <div class="text-sm text-info-600 space-y-1">
                            <p>✉️ El email incluye:</p>
                            <ul class="list-disc list-inside ml-4 space-y-1">
                                <li>Contraseña temporal</li>
                                <li>Enlace para iniciar sesión</li>
                                <li>Instrucciones de seguridad</li>
                                <li>Roles asignados</li>
                                @if($event)
                                <li>Número de comprobante: {{ $event->document_number }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-600 flex-shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-semibold text-warning-900 mb-1">Email No Enviado</h3>
                        <p class="text-sm text-warning-700">
                            No se pudo enviar el email automáticamente. Puedes reenviarlo usando el botón "Reenviar Email" en la parte superior.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Próximos Pasos --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-light-bulb class="w-5 h-5" />
                <span>Próximos Pasos</span>
            </div>
        </x-slot>

        <div class="grid gap-3">
            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                    1
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">Descargar Comprobante</h4>
                    <p class="text-sm text-gray-600">Descarga el comprobante verificable para el expediente del empleado.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                    2
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">Verificar Email</h4>
                    <p class="text-sm text-gray-600">Confirma que el empleado recibió el email con sus credenciales.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                    3
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">Primer Inicio de Sesión</h4>
                    <p class="text-sm text-gray-600">El empleado debe cambiar su contraseña en el primer acceso.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                    4
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900">Capacitación</h4>
                    <p class="text-sm text-gray-600">Asegúrate de que el empleado conozca sus responsabilidades y el uso del sistema.</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
