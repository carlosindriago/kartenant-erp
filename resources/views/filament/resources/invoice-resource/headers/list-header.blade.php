<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- TOTAL AMOUNT - Blue -->
    <div class="bg-blue-500 dark:bg-blue-600 text-white rounded-lg shadow p-4 border border-blue-200 dark:border-blue-700">
        <div class="flex items-center">
            <div class="p-2 bg-blue-600 dark:bg-blue-700 rounded-full">
                <svg class="w-6 h-6 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 9.96l4.24 4.24M20.46 14.04l-4.24-4.24M7.78 18.22l-4.24-4.24"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Total</h3>
                <p class="text-2xl font-bold text-white">${{ number_format($totalAmount, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- PAID AMOUNT - Green -->
    <div class="bg-green-500 dark:bg-green-600 text-white rounded-lg shadow p-4 border border-green-200 dark:border-green-700">
        <div class="flex items-center">
            <div class="p-2 bg-green-600 dark:bg-green-700 rounded-full">
                <svg class="w-6 h-6 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Pagado</h3>
                <p class="text-2xl font-bold text-white">${{ number_format($paidAmount, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- PENDING AMOUNT - Amber/Orange -->
    <div class="bg-amber-500 dark:bg-amber-600 text-white rounded-lg shadow p-4 border border-amber-200 dark:border-amber-700">
        <div class="flex items-center">
            <div class="p-2 bg-amber-600 dark:bg-amber-700 rounded-full">
                <svg class="w-6 h-6 text-amber-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Pendiente</h3>
                <p class="text-2xl font-bold text-white">${{ number_format($pendingAmount, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- OVERDUE AMOUNT - Red -->
    <div class="bg-red-500 dark:bg-red-600 text-white rounded-lg shadow p-4 border border-red-200 dark:border-red-700">
        <div class="flex items-center">
            <div class="p-2 bg-red-600 dark:bg-red-700 rounded-full">
                <svg class="w-6 h-6 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">Vencido</h3>
                <p class="text-2xl font-bold text-white">${{ number_format($overdueAmount, 2) }}</p>
            </div>
        </div>
    </div>
</div>