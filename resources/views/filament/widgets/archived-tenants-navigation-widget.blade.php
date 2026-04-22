@php
    use Illuminate\Support\Facades\Auth;
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            📦 Tenants Archivados
        </h3>
        @if(isset($error))
            <span class="text-xs text-red-500 dark:text-red-400">
                Error al cargar datos
            </span>
        @endif
    </div>

    @if(!isset($error))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Total Archived -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                            Total Archivados
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $archived_count }}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recently Archived -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-300">
                            Últimos 7 días
                        </p>
                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-200">
                            {{ $recently_archived }}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Archive with Issues -->
            <div class="@if($archive_with_issues > 0) bg-red-50 dark:bg-red-900/20 @else bg-green-50 dark:bg-green-900/20 @endif rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium @if($archive_with_issues > 0) text-red-600 dark:text-red-300 @else text-green-600 dark:text-green-300 @endif">
                            @if($archive_with_issues > 0)
                                Sin Backups
                            @else
                                Con Backups
                            @endif
                        </p>
                        <p class="text-2xl font-bold @if($archive_with_issues > 0) text-red-700 dark:text-red-200 @else text-green-700 dark:text-green-200 @endif">
                            {{ $archive_with_issues > 0 ? $archive_with_issues : ($archived_count - $archive_with_issues) }}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        @if($archive_with_issues > 0)
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        @else
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        @if($archived_count > 0 && Auth::guard('superadmin')->user()?->is_super_admin)
            <div class="mt-4">
                <a href="{{ $navigation_url }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    Gestionar Tenants Archivados
                </a>
            </div>
        @elseif($archived_count === 0)
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-600 dark:text-blue-300 text-center">
                    No hay tenants archivados actualmente
                </p>
            </div>
        @endif
    @endif
</div>