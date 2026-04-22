@extends('tenant.layouts.app')

@section('title', 'Factura #' . $invoice->id)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Factura #{{ $invoice->id }}</h1>
            <p class="text-gray-600">Detalles completos de tu factura</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('tenant.billing.history') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                ← Volver al Historial
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Imprimir Factura
            </button>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden print:shadow-none print:border-0">
        <!-- Invoice Header -->
        <div class="px-8 py-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Company Info -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Emporio Digital S.A.</h2>
                    <div class="space-y-1 text-sm text-gray-600">
                        <p>{{ $invoice->business_address ?? 'Dirección de la empresa' }}</p>
                        <p>Teléfono: {{ $invoice->business_phone ?? '+54 11 XXXX-XXXX' }}</p>
                        <p>Email: {{ $invoice->business_email ?? 'facturacion@emporiodigital.com' }}</p>
                        <p>CUIT: {{ $invoice->business_tax_id ?? '30-12345678-9' }}</p>
                    </div>
                </div>

                <!-- Invoice Info -->
                <div class="text-right">
                    <div class="inline-block text-left">
                        <div class="mb-2">
                            <span class="text-sm text-gray-600">Número de Factura:</span>
                            <p class="text-xl font-semibold text-gray-900">{{ $invoice->number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div class="mb-2">
                            <span class="text-sm text-gray-600">Fecha de Emisión:</span>
                            <p class="text-gray-900">{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') : $invoice->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div class="mb-2">
                            <span class="text-sm text-gray-600">Vencimiento:</span>
                            <p class="text-gray-900">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'Pago contra entrega' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Estado:</span>
                            <p class="text-gray-900">
                                @if($invoice->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Pagada
                                    </span>
                                @elseif($invoice->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pendiente de Pago
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Vencida
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing To -->
        <div class="px-8 py-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Facturar a:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-1 text-sm text-gray-700">
                    <p class="font-semibold text-gray-900">{{ $invoice->tenant_name ?? tenant()->name }}</p>
                    <p>{{ $invoice->tenant_address ?? 'Dirección del cliente' }}</p>
                    <p>Email: {{ $invoice->tenant_email ?? tenant()->email }}</p>
                    @if($invoice->tenant_tax_id)
                        <p>CUIT/DNI: {{ $invoice->tenant_tax_id }}</p>
                    @endif
                </div>

                <!-- Payment Method -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Método de Pago:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                        <p><strong>Banco:</strong> {{ $invoice->bank_name ?? 'Banco Galicia' }}</p>
                        <p><strong>Titular:</strong> {{ $invoice->account_holder ?? 'Emporio Digital S.A.' }}</p>
                        <p><strong>CBU:</strong> {{ $invoice->cbu ?? '0070067430000000123456' }}</p>
                        <p><strong>Alias:</strong> {{ $invoice->alias ?? 'EMPORIO.DIGITAL.PAGO' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="px-8 py-6">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 text-sm font-semibold text-gray-900">Descripción</th>
                        <th class="text-center py-3 text-sm font-semibold text-gray-900">Cantidad</th>
                        <th class="text-right py-3 text-sm font-semibold text-gray-900">Precio Unitario</th>
                        <th class="text-right py-3 text-sm font-semibold text-gray-900">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                    @endphp
                    @if($invoice->items && $invoice->items->isNotEmpty())
                        @foreach($invoice->items as $item)
                            @php
                                $itemSubtotal = $item->quantity * $item->unit_price;
                                $subtotal += $itemSubtotal;
                            @endphp
                            <tr class="border-b border-gray-100">
                                <td class="py-3 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="py-3 text-sm text-gray-900 text-center">{{ $item->quantity }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">${{ number_format($itemSubtotal, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @else
                        @php
                            // Default subscription item
                            $planName = $invoice->plan_name ?? 'Suscripción Mensual';
                            $planPrice = $invoice->amount ?? 0;
                            $subtotal = $planPrice;
                        @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm text-gray-900">
                                <div>
                                    <p class="font-medium">{{ $planName }}</p>
                                    <p class="text-xs text-gray-500">Período: {{ $invoice->period_start ? \Carbon\Carbon::parse($invoice->period_start)->format('d/m/Y') : 'N/A' }} - {{ $invoice->period_end ? \Carbon\Carbon::parse($invoice->period_end)->format('d/m/Y') : 'N/A' }}</p>
                                </div>
                            </td>
                            <td class="py-3 text-sm text-gray-900 text-center">1</td>
                            <td class="py-3 text-sm text-gray-900 text-right">${{ number_format($planPrice, 2, ',', '.') }}</td>
                            <td class="py-3 text-sm text-gray-900 text-right">${{ number_format($planPrice, 2, ',', '.') }}</td>
                        </tr>
                    @endif

                    <!-- Totals -->
                    <tr>
                        <td colspan="3" class="py-3 text-right text-sm font-semibold text-gray-900">Subtotal:</td>
                        <td class="py-3 text-right text-sm text-gray-900">${{ number_format($subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                        <tr>
                            <td colspan="3" class="py-3 text-right text-sm font-semibold text-gray-900">
                                IVA ({{ $invoice->tax_rate ?? 21 }}%):
                            </td>
                            <td class="py-3 text-right text-sm text-gray-900">${{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="font-bold text-lg border-t-2 border-gray-200">
                        <td colspan="3" class="py-4 text-right text-gray-900">Total:</td>
                        <td class="py-4 text-right text-gray-900">${{ number_format($invoice->total ?? $subtotal + ($invoice->tax_amount ?? 0), 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Notes & Footer -->
        @if($invoice->notes || $invoice->footer_text)
            <div class="px-8 py-6 border-t border-gray-200">
                @if($invoice->notes)
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Notas:</h4>
                        <p class="text-sm text-gray-700">{{ $invoice->notes }}</p>
                    </div>
                @endif
                @if($invoice->footer_text)
                    <div class="text-center text-sm text-gray-600 pt-4 border-t border-gray-100">
                        {{ $invoice->footer_text }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Payment Status Actions -->
    @if($invoice->status !== 'paid')
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Realizar Pago</h3>
            <p class="text-blue-700 mb-4">Para completar tu pago, realiza una transferencia bancaria y sube el comprobante.</p>
            <div class="flex space-x-4">
                <a href="{{ route('tenant.billing.index') }}" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Subir Comprobante de Pago
                </a>
                <a href="mailto:facturacion@emporiodigital.com" class="bg-white text-blue-600 border border-blue-300 px-6 py-2 rounded-md hover:bg-blue-50 transition-colors">
                    Consultar por Factura
                </a>
            </div>
        </div>
    @endif

    <!-- Legal Information -->
    <div class="mt-6 text-center text-xs text-gray-500">
        <p>Esta factura es un documento válido emitido por Emporio Digital S.A. Todos los derechos reservados.</p>
        <p class="mt-1">En caso de dudas, contacta a facturacion@emporiodigital.com o llama al {{ $invoice->business_phone ?? '+54 11 XXXX-XXXX' }}</p>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        font-size: 12px;
        line-height: 1.4;
    }

    .bg-white {
        box-shadow: none;
        border: none;
    }
}
</style>
@endsection