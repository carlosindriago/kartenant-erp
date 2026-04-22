<x-filament-widgets::widget>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    🚀 Acciones Rápidas
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Accesos directos a tus tareas más comunes
                </p>
            </div>

            <!-- Tips -->
            <div class="hidden sm:block">
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    💡 Tip: Puedes personalizar estos accesos
                </div>
            </div>
        </div>

        <!-- Actions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->getActions() as $action)
                <a href="{{ $action['url'] }}"
                   @if($action['target'] === '_blank') target="_blank" @endif
                   class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-all duration-300 hover:shadow-xl hover:border-{{ $action['color'] }}-500 hover:scale-105 hover:-translate-y-1">

                    <!-- Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $action['color'] }}-50/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <!-- Badge -->
                    @if($action['badge'])
                        <span class="absolute -top-2 -right-2 z-10 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-{{ $action['color'] }}-500 text-white shadow-lg">
                            {{ $action['badge'] }}
                        </span>
                    @endif

                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-start space-x-3">
                            <!-- Icon Container -->
                            <div class="flex-shrink-0">
                                <div class="h-12 w-12 rounded-xl bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/30 flex items-center justify-center ring-4 ring-{{ $action['color'] }}-100/50 dark:ring-{{ $action['color'] }}-900/20 group-hover:scale-110 transition-transform duration-300">
                                    <x-filament::icon
                                        :icon="$action['icon']"
                                        class="h-6 w-6 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400"
                                    />
                                </div>
                            </div>

                            <!-- Text Content -->
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-{{ $action['color'] }}-600 dark:group-hover:text-{{ $action['color'] }}-400 transition-colors duration-200">
                                    {{ $action['title'] }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                    {{ $action['description'] }}
                                </p>

                                <!-- Arrow Icon -->
                                <div class="flex items-center mt-2 text-{{ $action['color'] }}-500 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                    <span class="text-xs font-medium">Acceder</span>
                                    <x-filament::icon icon="heroicon-o-arrow-right" class="h-3 w-3 ml-1 group-hover:translate-x-1 transition-transform duration-300" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hover Effect Border -->
                    <div class="absolute bottom-0 left-0 h-1 bg-{{ $action['color'] }}-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                </a>
            @endforeach
        </div>

        <!-- Footer Tip -->
        <div class="mt-6 p-4 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <x-filament::icon icon="heroicon-o-lightbulb" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                        ¿Sabías que...?
                    </p>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                        Puedes arrastrar y soltar los widgets del dashboard para organizarlos según tus preferencias.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>