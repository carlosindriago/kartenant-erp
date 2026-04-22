<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */
namespace App\Livewire\POS;
use App\Modules\POS\Services\POSService;
use App\Modules\POS\Services\CashRegisterService;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\Customer;
use App\Modules\POS\Models\CashRegister;
use App\Modules\Inventory\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

#[Layout('layouts.kiosk')]
class PointOfSale extends Component
{
    // Cart state
    public $cart = [];
    public $search = '';
    public $total = 0;
    public $subtotal = 0;
    public $taxAmount = 0;
    public $discountAmount = 0;
    public $discountType = 'fixed'; // 'fixed' or 'percentage'
    public $discountValue = 0;
    
    // Payment modal state
    public $showPaymentModal = false;
    public $paymentMethod = 'cash';
    public $amountReceived = 0;
    public $changeAmount = 0;
    public $transactionReference = '';
    
    // Customer selection
    public $customerId = null;
    public $customerSearch = '';
    public $customerSearchResults = [];
    public $showQuickCustomerForm = false;
    public $quickCustomerName = '';
    public $quickCustomerDocumentType = 'DNI';
    public $quickCustomerDocumentNumber = '';
    public $quickCustomerPhone = '';
    public $quickCustomerEmail = '';
    
    // UI state
    public $showHistoryModal = false;
    public $todaySales = [];
    public $todayCashRegisters = [];
    public $historyTab = 'sales'; // 'sales' o 'cash_registers'
    public $showKeyboardHelp = false;
    public $showReceiptModal = false;
    public $lastSaleId = null;
    
    // Quick cancel
    public $showCancelConfirmationModal = false;
    public $saleToCancel = null;
    public $cancelPassword = '';
    public $cancelPasswordError = '';
    public $cancelObservations = '';
    
    // Cash Register
    public $currentCashRegister = null;
    public $showOpenRegisterModal = false;
    public $showCloseRegisterModal = false;
    public $showConfirmCloseModal = false;
    public $openingAmount = 0;
    public $openingNotes = '';
    public $confirmZeroOpening = false; // Confirmación para apertura con $0
    public $closingActualAmount = 0;
    public $closingNotes = '';
    public $cashBreakdown = [];
    public $showDailyReportModal = false;
    public $dailyReport = [];
    public $reportCashRegisterId = null; // ID de la caja del reporte actual
    
    // Totales de ventas para el modal de cierre
    public $cashSalesTotal = 0;
    public $cancelledSalesTotal = 0;
    
    // Barcode scanner
    public $barcodeBuffer = '';
    public $lastKeyTime = 0;
    
    protected POSService $posService;
    protected CashRegisterService $cashRegisterService;
    
    public function boot(POSService $posService, CashRegisterService $cashRegisterService)
    {
        $this->posService = $posService;
        $this->cashRegisterService = $cashRegisterService;
    }
    
    public function mount()
    {
        $this->loadCurrentCashRegister();
    }
    
    public function loadCurrentCashRegister()
    {
        try {
            $userId = auth('tenant')->id();
            $this->currentCashRegister = CashRegister::getUserOpenRegister($userId);
            
            \Log::info('loadCurrentCashRegister', [
                'user_id' => $userId,
                'has_register' => $this->currentCashRegister !== null,
                'register_id' => $this->currentCashRegister?->id,
            ]);
        } catch (\Exception $e) {
            // Si hay error (tabla no existe, etc.), simplemente no hay caja abierta
            \Log::warning('Error loading cash register: ' . $e->getMessage());
            $this->currentCashRegister = null;
        }
    }
    
    /**
     * Helper para mostrar notificaciones usando eventos de browser
     * que Alpine.js puede capturar
     */
    protected function notify($type, $message)
    {
        $message = addslashes($message);
        $this->js("window.dispatchEvent(new CustomEvent('notify', { detail: { type: '{$type}', message: '{$message}' } }))");
    }
    
    /**
     * Persistir carrito en localStorage
     */
    public function persistCartToLocalStorage()
    {
        $cartData = collect($this->cart)->map(function($item) {
            return [
                'product_id' => $item['product']->id,
                'qty' => $item['qty']
            ];
        })->toArray();
        
        $dataToStore = [
            'cart' => $cartData,
            'discountType' => $this->discountType,
            'discountValue' => $this->discountValue,
            'customerId' => $this->customerId,
            'timestamp' => time()
        ];
        
        $this->js("localStorage.setItem('pos_cart', '" . json_encode($dataToStore) . "')");
    }
    
    /**
     * Cargar carrito desde localStorage
     */
    public function loadCartFromLocalStorage()
    {
        // Este método será llamado desde el frontend
        // JavaScript enviará los datos a través de un método Livewire
    }
    
    /**
     * Recibir datos del carrito desde localStorage (llamado por JS)
     */
    public function restoreCart($cartData = [])
    {
        if (empty($cartData)) {
            return;
        }
        
        try {
            // Verificar timestamp (no cargar carritos de más de 24 horas)
            if (isset($cartData['timestamp'])) {
                $age = time() - $cartData['timestamp'];
                if ($age > 86400) { // 24 horas
                    $this->js("localStorage.removeItem('pos_cart')");
                    return;
                }
            }
            
            // Restaurar carrito
            if (isset($cartData['cart']) && is_array($cartData['cart'])) {
                $this->cart = [];
                foreach ($cartData['cart'] as $item) {
                    if (isset($item['product_id']) && isset($item['qty'])) {
                        $product = Product::with('tax')->find($item['product_id']);
                        if ($product && $product->stock > 0) {
                            $this->cart[] = [
                                'product' => $product,
                                'qty' => min($item['qty'], $product->stock)
                            ];
                        }
                    }
                }
            }
            
            // Restaurar descuento
            if (isset($cartData['discountType'])) {
                $this->discountType = $cartData['discountType'];
            }
            if (isset($cartData['discountValue'])) {
                $this->discountValue = $cartData['discountValue'];
            }
            
            // Restaurar cliente
            if (isset($cartData['customerId'])) {
                $this->customerId = $cartData['customerId'];
            }
            
            $this->calculateTotal();
            
            if (!empty($this->cart)) {
                $this->notify('info', 'Carrito restaurado (' . count($this->cart) . ' productos)');
            }
        } catch (\Exception $e) {
            \Log::error('Error restaurando carrito: ' . $e->getMessage());
        }
    }
    
    /**
     * Limpiar carrito de localStorage
     */
    public function clearCartFromLocalStorage()
    {
        $this->js("localStorage.removeItem('pos_cart')");
    }
    
    public function addToCart($productId, $triggerSound = true)
    {
        $product = Product::with('tax')->find($productId);
        
        if (!$product || $product->stock <= 0) {
            $this->notify('error', 'Producto no disponible o sin stock');
            return;
        }
        
        // Verificar si el producto ya está en el carrito
        $existingIndex = null;
        foreach ($this->cart as $index => $item) {
            if ($item['product']->id === $productId) {
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingIndex !== null) {
            // Verificar si hay suficiente stock
            if ($this->cart[$existingIndex]['qty'] >= $product->stock) {
                $this->notify('error', 'Stock insuficiente para ' . $product->name);
                return;
            }
            $this->cart[$existingIndex]['qty']++;
        } else {
            $this->cart[] = ['product' => $product, 'qty' => 1];
        }
        
        $this->calculateTotal();
        $this->persistCartToLocalStorage();
        
        if ($triggerSound) {
            $this->dispatch('playBeep');
            $this->dispatch('flashSuccess');
        }
    }
    
    public function calculateTotal()
    {
        $this->subtotal = collect($this->cart)->sum(fn($item) => $item['product']->price * $item['qty']);
        
        // Calcular impuestos por producto
        $this->taxAmount = collect($this->cart)->sum(function($item) {
            $product = $item['product'];
            $lineSubtotal = $product->price * $item['qty'];
            
            // Si el producto tiene impuesto asociado
            if ($product->tax && $product->tax->rate > 0) {
                return $lineSubtotal * ($product->tax->rate / 100);
            }
            
            return 0;
        });
        
        // Calcular descuento
        $this->calculateDiscount();
        
        // Total = Subtotal + Impuestos - Descuento
        $this->total = max(0, $this->subtotal + $this->taxAmount - $this->discountAmount);
    }
    
    public function calculateDiscount()
    {
        if ($this->discountValue <= 0) {
            $this->discountAmount = 0;
            return;
        }
        
        if ($this->discountType === 'percentage') {
            // Descuento porcentual sobre subtotal
            $this->discountAmount = ($this->subtotal * min($this->discountValue, 100)) / 100;
        } else {
            // Descuento fijo
            $this->discountAmount = min($this->discountValue, $this->subtotal);
        }
    }
    
    public function applyDiscount()
    {
        $this->calculateTotal();
        $this->persistCartToLocalStorage();
        $this->notify('success', 'Descuento aplicado: $' . number_format($this->discountAmount, 2));
    }
    
    public function removeDiscount()
    {
        $this->discountValue = 0;
        $this->discountAmount = 0;
        $this->calculateTotal();
        $this->persistCartToLocalStorage();
        $this->notify('info', 'Descuento removido');
    }
    
    public function incrementQty($index)
    {
        if (isset($this->cart[$index])) {
            $product = $this->cart[$index]['product'];
            $currentQty = $this->cart[$index]['qty'];
            
            // Validar stock disponible
            if ($currentQty >= $product->stock) {
                $this->notify('error', 'Stock insuficiente para ' . $product->name . ' (máx: ' . $product->stock . ')');
                return;
            }
            
            $this->cart[$index]['qty']++;
            $this->calculateTotal();
            $this->persistCartToLocalStorage();
        }
    }
    
    public function decrementQty($index)
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['qty'] > 1) {
                $this->cart[$index]['qty']--;
            } else {
                unset($this->cart[$index]);
                $this->cart = array_values($this->cart); // Reindex
            }
            $this->calculateTotal();
            $this->persistCartToLocalStorage();
        }
    }
    
    public function removeFromCart($index)
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Reindex
            $this->calculateTotal();
            $this->persistCartToLocalStorage();
        }
    }
    
    public function clearCart()
    {
        $this->cart = [];
        $this->total = 0;
        $this->discountValue = 0;
        $this->discountAmount = 0;
        $this->clearCartFromLocalStorage();
    }
    
    public function openPaymentModal()
    {
        if (empty($this->cart)) {
            $this->notify('error', 'El carrito está vacío');
            return;
        }
        
        $this->showPaymentModal = true;
        $this->amountReceived = $this->total;
        $this->calculateChange();
    }
    
    public function calculateChange()
    {
        $this->changeAmount = max(0, $this->amountReceived - $this->total);
    }
    
    public function completeSale()
    {
        if (empty($this->cart)) {
            $this->notify('error', 'El carrito está vacío');
            return;
        }
        
        // CRÍTICO: Validar que haya caja abierta
        if (!$this->currentCashRegister) {
            $this->notify('error', 'Debe abrir una caja antes de realizar ventas');
            $this->showOpenRegisterModal = true;
            return;
        }
        
        if ($this->paymentMethod === 'cash' && $this->amountReceived < $this->total) {
            $this->notify('error', 'Monto recibido insuficiente');
            return;
        }
        
        try {
            $items = collect($this->cart)->map(fn($i) => [
                'product_id' => $i['product']->id,
                'quantity' => $i['qty'],
                'unit_price' => $i['product']->price
            ])->toArray();
            
            $sale = $this->posService->processSale([
                'cash_register_id' => $this->currentCashRegister->id, // Asociar con caja activa
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->taxAmount,
                'discount_amount' => $this->discountAmount,
                'total' => $this->total,
                'amount_paid' => $this->amountReceived,
                'change_amount' => $this->changeAmount,
                'payment_method' => $this->paymentMethod,
                'transaction_reference' => $this->transactionReference ?: null,
                'customer_id' => $this->customerId,
            ], $items);
            
            // Guardar solo el ID de la venta
            $this->lastSaleId = $sale->id;
            
            // Limpiar carrito
            $this->cart = [];
            $this->total = 0;
            $this->subtotal = 0;
            $this->taxAmount = 0;
            $this->discountAmount = 0;
            $this->discountValue = 0;
            $this->clearCartFromLocalStorage();
            
            // Cerrar modal de pago
            $this->showPaymentModal = false;
            
            // IMPORTANTE: Mostrar modal de recibo DESPUÉS de cerrar pago
            $this->showReceiptModal = true;
            
            // Log para debug
            \Log::info('Sale completed', [
                'lastSaleId' => $this->lastSaleId,
                'showReceiptModal' => $this->showReceiptModal
            ]);
            
            $this->dispatch('playBeep');
            
        } catch (\Exception $e) {
            $this->notify('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }
    
    public function resetSale()
    {
        $this->cart = [];
        $this->total = 0;
        $this->subtotal = 0;
        $this->taxAmount = 0;
        $this->discountAmount = 0;
        $this->discountValue = 0;
        $this->showPaymentModal = false;
        $this->paymentMethod = 'cash';
        $this->amountReceived = 0;
        $this->changeAmount = 0;
        $this->transactionReference = '';
        $this->customerId = null;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
        $this->showQuickCustomerForm = false;
        $this->search = '';
        $this->clearCartFromLocalStorage();
    }
    
    public function loadTodaySales()
    {
        // Cargar ventas del día
        $this->todaySales = Sale::whereDate('created_at', today())
            ->with(['items', 'user', 'customer'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
        
        // Cargar aperturas y cierres de caja del día
        $this->todayCashRegisters = CashRegister::whereDate('opened_at', today())
            ->with(['openedBy', 'closedBy'])
            ->orderBy('opened_at', 'desc')
            ->get();
            
        $this->showHistoryModal = true;
    }
    
    public function addByBarcode($barcode)
    {
        $product = Product::where('barcode', $barcode)
            ->orWhere('sku', $barcode)
            ->first();
            
        if ($product) {
            $this->addToCart($product->id);
            $this->notify('success', "{$product->name} agregado");
        } else {
            $this->notify('error', 'Producto no encontrado');
            $this->dispatch('playErrorBeep');
        }
    }
    
    #[On('barcodeScanned')]
    public function handleBarcodeScanned($barcode)
    {
        $this->addByBarcode($barcode);
    }
    
    public function setQuickAmount($amount)
    {
        $this->amountReceived = $amount;
        $this->calculateChange();
    }
    
    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->lastSaleId = null;
        $this->resetSale();
    }
    
    /**
     * Ver detalles de una venta desde el historial
     */
    public function viewSaleDetails($saleId)
    {
        try {
            $sale = Sale::find($saleId);
            
            if (!$sale) {
                $this->notify('error', 'Venta no encontrada');
                return;
            }
            
            // Establecer la venta a mostrar
            $this->lastSaleId = $sale->id;
            
            // Cerrar modal de historial y abrir modal de recibo
            $this->showHistoryModal = false;
            $this->showReceiptModal = true;
            
        } catch (\Exception $e) {
            $this->notify('error', 'Error al cargar la venta: ' . $e->getMessage());
        }
    }
    
    /**
     * Ver detalles de una caja desde el historial
     */
    public function viewCashRegisterDetails($cashRegisterId)
    {
        try {
            $cashRegister = CashRegister::find($cashRegisterId);
            
            if (!$cashRegister) {
                $this->notify('error', 'Caja no encontrada');
                return;
            }
            
            // Guardar ID para poder exportar PDF
            $this->reportCashRegisterId = $cashRegister->id;
            
            // Cargar reporte de la caja
            $this->dailyReport = $this->cashRegisterService->getDailyReport($cashRegister);
            
            // Agregar transacciones al reporte
            $this->dailyReport['transactions'] = $cashRegister->sales()
                ->with(['customer', 'user'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'time' => $sale->created_at->format('H:i:s'),
                        'date' => $sale->created_at->format('d/m/Y'),
                        'customer' => $sale->customer ? $sale->customer->name : 'Público General',
                        'user' => $sale->user ? $sale->user->name : 'N/A',
                        'payment_method' => $sale->payment_method,
                        'status' => $sale->status,
                        'total' => $sale->total,
                        'is_cancelled' => $sale->status === 'cancelled',
                    ];
                })->toArray();
            
            // Cerrar modal de historial y abrir modal de reporte
            $this->showHistoryModal = false;
            $this->showDailyReportModal = true;
            
        } catch (\Exception $e) {
            \Log::error('Error al cargar detalles de caja', [
                'error' => $e->getMessage(),
                'register_id' => $cashRegisterId,
            ]);
            $this->notify('error', 'Error al cargar detalles de caja');
        }
    }
    
    /**
     * 🚨 BOTÓN DE PÁNICO: Abrir modal de confirmación para anular última venta
     * 
     * Este método muestra un resumen detallado de la venta y solicita
     * confirmación con contraseña antes de proceder
     */
    public function openCancelConfirmation()
    {
        try {
            // Registrar intento de apertura
            \Log::info('🔍 Intento de anular venta - Modal abierto', [
                'user_id' => auth('tenant')->id(),
                'user_name' => auth('tenant')->user()?->name ?? 'Unknown',
                'ip' => request()->ip(),
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            // Obtener la última venta del día
            $lastSale = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->latest()
                ->first();
            
            if (!$lastSale) {
                $this->notify('error', 'No hay ventas para anular hoy');
                return;
            }
            
            // Verificar si es elegible para anulación rápida (5 minutos)
            $returnService = app(\App\Modules\POS\Services\ReturnService::class);
            
            if (!$returnService->isEligibleForQuickCancel($lastSale)) {
                $minutes = $lastSale->created_at->diffInMinutes(now());
                $this->notify('error', "Solo se pueden anular ventas de los últimos 5 minutos. Esta venta tiene {$minutes} minutos.");
                
                \Log::warning('⏰ Intento de anular venta fuera de tiempo permitido', [
                    'sale_id' => $lastSale->id,
                    'invoice_number' => $lastSale->invoice_number,
                    'minutes_elapsed' => $minutes,
                    'user' => auth('tenant')->user()?->name ?? 'Unknown',
                ]);
                
                return;
            }
            
            // Cargar relaciones para el resumen
            $lastSale->load(['items.product', 'customer', 'user']);
            
            // Establecer la venta a cancelar
            $this->saleToCancel = $lastSale->id;
            $this->cancelPassword = '';
            $this->cancelPasswordError = '';
            
            // Abrir modal
            $this->showCancelConfirmationModal = true;
            
        } catch (\Exception $e) {
            $this->notify('error', 'Error al preparar anulación: ' . $e->getMessage());
            \Log::error('❌ Error al abrir modal de anulación', [
                'error' => $e->getMessage(),
                'user' => auth('tenant')->user()?->name ?? 'Unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * 🔐 Confirmar anulación con verificación de contraseña
     * CÁMARA DE SEGURIDAD DIGITAL: Todo queda registrado
     */
    public function confirmCancelSale()
    {
        // 🔍 DEBUG: Estado completo de autenticación
        \Log::info('🔍 DEBUG - Estado de autenticación', [
            'all_guards' => [
                'web_check' => auth('web')->check(),
                'web_id' => auth('web')->id(),
                'tenant_check' => auth('tenant')->check(),
                'tenant_id' => auth('tenant')->id(),
                'default_check' => auth()->check(),
                'default_id' => auth()->id(),
            ],
            'session_id' => session()->getId(),
            'has_session_data' => session()->has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'),
        ]);
        
        // Obtener usuario con datos frescos de la DB usando el guard 'tenant'
        $user = auth('tenant')->user();
        
        // Si no hay usuario en 'tenant', intentar con 'web'
        if (!$user) {
            $user = auth('web')->user();
            \Log::warning('⚠️ Usuario no encontrado en guard tenant, usando web', [
                'found_in_web' => $user !== null,
            ]);
        }
        
        if ($user) {
            $user = $user->fresh();
        }
        $saleId = $this->saleToCancel;
        
        // Validar que hay una venta seleccionada
        if (!$saleId) {
            $this->notify('error', 'No hay venta seleccionada para anular');
            return;
        }
        
        // Validar que se ingresaron observaciones
        if (empty(trim($this->cancelObservations))) {
            $this->notify('error', 'Debe indicar el motivo de la anulación');
            return;
        }
        
        // Validar que se ingresó contraseña
        if (empty($this->cancelPassword)) {
            $this->cancelPasswordError = 'Debe ingresar su contraseña para confirmar';
            
            \Log::warning('⚠️ Intento de anulación sin contraseña', [
                'sale_id' => $saleId,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Unknown',
                'ip' => request()->ip(),
            ]);
            
            return;
        }
        
        // 🔐 VERIFICAR CONTRASEÑA
        // Debug logging
        \Log::info('🔍 DEBUG - Verificación de contraseña', [
            'user_exists' => $user !== null,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'password_field_exists' => isset($user->password),
            'password_field_empty' => empty($user->password),
            'password_length' => $user?->password ? strlen($user->password) : 0,
            'input_password_length' => strlen($this->cancelPassword),
        ]);
        
        if (!$user) {
            $this->cancelPasswordError = '❌ Usuario no autenticado';
            \Log::error('❌ Usuario no encontrado en ningún guard', [
                'tenant_check' => auth('tenant')->check(),
                'web_check' => auth('web')->check(),
                'session_id' => session()->getId(),
            ]);
            return;
        }
        
        if (!\Hash::check($this->cancelPassword, $user->password)) {
            $this->cancelPasswordError = '❌ Contraseña incorrecta';
            $this->cancelPassword = '';
            
            // 📹 CÁMARA DE SEGURIDAD: Registrar intento fallido
            \Log::warning('🚨 INTENTO FALLIDO de anulación - Contraseña incorrecta', [
                'sale_id' => $saleId,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Unknown',
                'user_email' => $user?->email,
                'ip' => request()->ip(),
                'timestamp' => now()->toDateTimeString(),
                'severity' => 'HIGH',
                'has_password_hash' => !empty($user->password),
            ]);
            
            return;
        }
        
        try {
            // Obtener la venta
            $sale = Sale::with(['items.product', 'customer', 'user'])->find($saleId);
            
            if (!$sale) {
                $this->notify('error', 'Venta no encontrada');
                $this->closeCancelModal();
                return;
            }
            
            // 📹 CÁMARA DE SEGURIDAD: Registrar inicio de anulación
            \Log::info('🎬 INICIANDO ANULACIÓN DE VENTA', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'items_count' => $sale->items->count(),
                'authorized_by_user_id' => $user->id,
                'authorized_by_user_name' => $user->name,
                'authorized_by_user_email' => $user->email,
                'cancellation_reason' => trim($this->cancelObservations),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toDateTimeString(),
                'customer' => $sale->customer?->name ?? 'General',
                'original_cashier' => $sale->user?->name ?? 'Unknown',
            ]);
            
            // Procesar anulación completa
            $returnService = app(\App\Modules\POS\Services\ReturnService::class);
            $saleReturn = $returnService->quickCancelLastSale(
                $sale,
                trim($this->cancelObservations) . " (Autorizado por: {$user->name})"
            );
            
            // 📹 CÁMARA DE SEGURIDAD: Registrar anulación exitosa
            \Log::info('✅ ANULACIÓN COMPLETADA EXITOSAMENTE', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'return_id' => $saleReturn->id,
                'return_number' => $saleReturn->return_number,
                'refund_amount' => $saleReturn->total,
                'products_returned' => $saleReturn->items->map(fn($item) => [
                    'product' => $item->product_name,
                    'quantity' => $item->quantity,
                    'value' => $item->line_total,
                ])->toArray(),
                'authorized_by' => $user->name,
                'timestamp' => now()->toDateTimeString(),
                'status' => 'SUCCESS',
            ]);
            
            // Recargar ventas del día
            $this->loadTodaySales();
            
            // Refrescar propiedad computada para ocultar botón
            unset($this->canCancelLastSale);
            
            // Cerrar modal y limpiar
            $this->closeCancelModal();
            
            $this->notify('success', "✅ Venta {$sale->invoice_number} anulada. NCR: {$saleReturn->return_number}");
            
        } catch (\Exception $e) {
            // 📹 CÁMARA DE SEGURIDAD: Registrar error
            \Log::error('❌ ERROR EN ANULACIÓN DE VENTA', [
                'sale_id' => $saleId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user' => $user?->name ?? 'Unknown',
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString(),
                'severity' => 'CRITICAL',
            ]);
            
            $this->notify('error', 'Error al anular venta: ' . $e->getMessage());
        }
    }
    
    /**
     * Cerrar modal de confirmación
     */
    public function closeCancelModal()
    {
        $this->showCancelConfirmationModal = false;
        $this->saleToCancel = null;
        $this->cancelPassword = '';
        $this->cancelPasswordError = '';
        $this->cancelObservations = '';
    }
    
    /**
     * Verificar si hay una venta elegible para anulación rápida
     * Implementa lógica de escalación basada en permisos y configuración
     */
    #[Computed]
    public function canCancelLastSale(): bool
    {
        $lastSale = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->latest()
            ->first();
        
        if (!$lastSale) {
            return false;
        }
        
        $user = auth('tenant')->user() ?? auth('web')->user();
        
        if (!$user) {
            return false;
        }
        
        // 🎯 ESCALACIÓN DE PERMISOS:
        // Admin/Gerente/Supervisor con permiso 'process_returns' SIEMPRE pueden anular
        if ($user->can('process_returns')) {
            return true;
        }
        
        // 🎯 POLÍTICAS PARA CAJEROS:
        // Solo continúa si el usuario es cajero (o similar) sin el permiso superior
        $settings = \App\Models\TenantSetting::getForCurrentTenant();
        
        // Si las políticas no permiten a cajeros anular, se detiene aquí
        if (!$settings->allow_cashier_void_last_sale) {
            return false;
        }
        
        // Verificar si la venta fue hecha por el cajero actual (si la política lo requiere)
        if ($settings->cashier_void_requires_own_sale && $lastSale->user_id !== $user->id) {
            return false;
        }
        
        // Verificar si es del mismo día (si la política lo requiere)
        if ($settings->cashier_void_requires_same_day && !$lastSale->created_at->isToday()) {
            return false;
        }
        
        // Verificar límite de tiempo configurado (en minutos)
        $minutesElapsed = $lastSale->created_at->diffInMinutes(now());
        $timeLimit = $settings->cashier_void_time_limit_minutes;
        
        if ($minutesElapsed > $timeLimit) {
            return false;
        }
        
        return true;
    }
    
    #[Computed]
    public function lastSale()
    {
        if (!$this->lastSaleId) {
            return null;
        }
        
        return Sale::with(['items.product', 'customer'])->find($this->lastSaleId);
    }
    
    #[Computed]
    public function saleToBeCancel()
    {
        if (!$this->saleToCancel) {
            return null;
        }
        
        return Sale::with(['items.product', 'customer', 'user'])->find($this->saleToCancel);
    }
    
    public function emailReceipt($saleId)
    {
        $sale = Sale::with(['items', 'customer'])->findOrFail($saleId);
        
        if (!$sale->customer || !$sale->customer->email) {
            $this->notify('error', 'El cliente no tiene email registrado');
            return;
        }
        
        try {
            $tenant = \Spatie\Multitenancy\Models\Tenant::current();
            
            $pdf = Pdf::loadView('pdf.receipt', [
                'sale' => $sale,
                'tenant' => $tenant
            ]);
            
            Mail::send('emails.receipt', [
                'sale' => $sale,
                'tenant' => $tenant
            ], function ($message) use ($sale, $pdf) {
                $message->to($sale->customer->email, $sale->customer->name)
                        ->subject("Comprobante de Venta #{$sale->invoice_number}")
                        ->attachData($pdf->output(), "comprobante-{$sale->invoice_number}.pdf");
            });
            
            $this->notify('success', 'Comprobante enviado a ' . $sale->customer->email);
        } catch (\Exception $e) {
            $this->notify('error', 'Error al enviar email: ' . $e->getMessage());
        }
    }
    
    public function updatedCustomerSearch($value)
    {
        // Limpiar resultados si la búsqueda es muy corta
        if (strlen($value) < 2) {
            $this->customerSearchResults = [];
            return;
        }
        
        // Solo buscar si no hay cliente seleccionado
        if ($this->customerId) {
            return;
        }
        
        try {
            // Búsqueda case-insensitive usando whereRaw con LOWER()
            $searchTerm = strtolower($value);
            
            $this->customerSearchResults = Customer::where('is_active', true)
                ->where(function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('LOWER(document_number) LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ['%' . $searchTerm . '%']);
                })
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->toArray(); // Convertir a array para evitar problemas de serialización
        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de clientes: ' . $e->getMessage());
            $this->customerSearchResults = [];
        }
    }
    
    public function selectCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customerSearch = $customer->name;
            $this->customerSearchResults = [];
        }
    }
    
    public function clearCustomer()
    {
        $this->customerId = null;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
    }
    
    public function toggleQuickCustomerForm()
    {
        $this->showQuickCustomerForm = !$this->showQuickCustomerForm;
        
        if (!$this->showQuickCustomerForm) {
            $this->quickCustomerName = '';
            $this->quickCustomerDocumentType = 'DNI';
            $this->quickCustomerDocumentNumber = '';
            $this->quickCustomerPhone = '';
            $this->quickCustomerEmail = '';
        }
    }
    
    public function createQuickCustomer()
    {
        $this->validate([
            'quickCustomerName' => 'required|min:3',
            'quickCustomerDocumentType' => 'nullable|in:DNI,CUIL,CUIT',
            'quickCustomerDocumentNumber' => 'nullable|min:7|max:20',
            'quickCustomerPhone' => 'nullable|min:7',
            'quickCustomerEmail' => 'nullable|email',
        ]);
        
        try {
            $customer = Customer::create([
                'name' => $this->quickCustomerName,
                'document_type' => $this->quickCustomerDocumentNumber ? $this->quickCustomerDocumentType : null,
                'document_number' => $this->quickCustomerDocumentNumber ?: null,
                'phone' => $this->quickCustomerPhone,
                'email' => $this->quickCustomerEmail,
                'is_active' => true,
            ]);
            
            $this->customerId = $customer->id;
            $this->customerSearch = $customer->name;
            $this->showQuickCustomerForm = false;
            $this->quickCustomerName = '';
            $this->quickCustomerDocumentType = 'DNI';
            $this->quickCustomerDocumentNumber = '';
            $this->quickCustomerPhone = '';
            $this->quickCustomerEmail = '';
            
            $this->notify('success', 'Cliente creado exitosamente');
        } catch (\Exception $e) {
            $this->notify('error', 'Error al crear cliente: ' . $e->getMessage());
        }
    }
    
    // ==================== CASH REGISTER METHODS ====================
    
    public function openCashRegister()
    {
        $this->validate([
            'openingAmount' => 'required|numeric|min:0',
        ], [
            'openingAmount.required' => 'El fondo inicial es obligatorio',
            'openingAmount.numeric' => 'El fondo inicial debe ser un número',
            'openingAmount.min' => 'El fondo inicial no puede ser negativo',
        ]);
        
        // Si el monto es 0 y no ha confirmado, solicitar confirmación
        if ($this->openingAmount == 0 && !$this->confirmZeroOpening) {
            $this->confirmZeroOpening = true;
            return; // No continuar hasta que confirme
        }
        
        try {
            $userId = auth('tenant')->id();
            
            \Log::info('Intentando abrir caja', [
                'amount' => $this->openingAmount,
                'notes' => $this->openingNotes,
                'user_id' => $userId,
                'guard' => 'tenant',
            ]);
            
            $this->currentCashRegister = $this->cashRegisterService->openRegister(
                $this->openingAmount,
                $this->openingNotes
            );
            
            $this->showOpenRegisterModal = false;
            $this->openingAmount = 0;
            $this->openingNotes = '';
            $this->confirmZeroOpening = false;
            
            $message = $this->currentCashRegister->initial_amount == 0 
                ? 'Caja abierta sin fondo inicial - ' . $this->currentCashRegister->register_number
                : 'Caja abierta exitosamente - ' . $this->currentCashRegister->register_number;
            
            $this->notify('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error al abrir caja', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notify('error', 'Error al abrir caja: ' . $e->getMessage());
        }
    }
    
    public function cancelOpenCashRegister()
    {
        $this->showOpenRegisterModal = false;
        $this->openingAmount = 0;
        $this->openingNotes = '';
        $this->confirmZeroOpening = false;
    }
    
    public function prepareCloseCashRegister()
    {
        if (!$this->currentCashRegister) {
            $this->notify('error', 'No hay caja abierta');
            return;
        }
        
        // Pre-calcular totales de Ventas utilizando la conexión correcta
        $this->cashSalesTotal = Sale::where('cash_register_id', $this->currentCashRegister->id)
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->sum('total');
            
        $this->cancelledSalesTotal = Sale::where('cash_register_id', $this->currentCashRegister->id)
            ->where('payment_method', 'cash')
            ->where('status', 'cancelled')
            ->sum('total');
        
        // Pre-calcular el monto esperado
        $this->closingActualAmount = $this->currentCashRegister->calculateExpectedAmount();
        $this->showCloseRegisterModal = true;
    }
    
    public function closeCashRegister()
    {
        // Validar antes de mostrar confirmación
        $this->validate([
            'closingActualAmount' => 'required|numeric|min:0',
        ], [
            'closingActualAmount.required' => 'El monto contado es obligatorio',
            'closingActualAmount.numeric' => 'El monto debe ser un número',
            'closingActualAmount.min' => 'El monto no puede ser negativo',
        ]);
        
        // Mostrar modal de confirmación con resumen
        $this->showCloseRegisterModal = false;
        $this->showConfirmCloseModal = true;
    }
    
    public function confirmAndCloseCashRegister()
    {
        try {
            $this->currentCashRegister = $this->cashRegisterService->closeRegister(
                $this->currentCashRegister,
                $this->closingActualAmount,
                $this->cashBreakdown,
                $this->closingNotes
            );
            
            $this->showConfirmCloseModal = false;
            
            // Mostrar reporte
            $this->showDailyReport();
            
            // Limpiar estado
            $this->currentCashRegister = null;
            $this->closingActualAmount = 0;
            $this->closingNotes = '';
            $this->cashBreakdown = [];
            
        } catch (\Exception $e) {
            $this->showConfirmCloseModal = false;
            $this->notify('error', $e->getMessage());
        }
    }
    
    public function backToCloseModal()
    {
        $this->showConfirmCloseModal = false;
        $this->showCloseRegisterModal = true;
    }
    
    public function showDailyReport()
    {
        if (!$this->currentCashRegister) {
            $this->notify('error', 'No hay caja para mostrar reporte');
            return;
        }
        
        // Guardar ID de la caja para poder exportar PDF después
        $this->reportCashRegisterId = $this->currentCashRegister->id;
        
        $this->dailyReport = $this->cashRegisterService->getDailyReport($this->currentCashRegister);
        
        // Agregar transacciones al reporte
        $this->dailyReport['transactions'] = $this->currentCashRegister->sales()
            ->with(['customer', 'user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'time' => $sale->created_at->format('H:i:s'),
                    'date' => $sale->created_at->format('d/m/Y'),
                    'customer' => $sale->customer ? $sale->customer->name : 'Público General',
                    'user' => $sale->user ? $sale->user->name : 'N/A',
                    'payment_method' => $sale->payment_method,
                    'status' => $sale->status,
                    'total' => $sale->total,
                    'is_cancelled' => $sale->status === 'cancelled',
                ];
            })->toArray();
        
        $this->showDailyReportModal = true;
    }
    
    public function exportDailyReportPdf($format = 'thermal')
    {
        try {
            // Validar que hay un ID de caja del reporte
            if (!$this->reportCashRegisterId) {
                $this->notify('error', 'No hay reporte de caja disponible para exportar');
                return;
            }
            
            // Recuperar la caja (puede estar abierta o cerrada)
            $cashRegister = CashRegister::find($this->reportCashRegisterId);
            
            if (!$cashRegister) {
                $this->notify('error', 'No se encontró la caja registradora');
                return;
            }
            
            // Establecer el formato solicitado
            $cashRegister->pdf_format = $format;
            
            // Asegurar que tiene hash de verificación
            $cashRegister->ensureVerificationHash();
            
            // Usar el método del modelo para generar el PDF
            $pdf = $cashRegister->generatePdf();
            
            $formatSuffix = $format === 'a4' ? 'A4' : '80mm';
            $filename = 'reporte-caja-' . $cashRegister->register_number . '-' . $formatSuffix . '-' . now()->format('Y-m-d') . '.pdf';
            
            // Registrar actividad de generación de PDF
            activity()
                ->causedBy(auth('tenant')->user())
                ->performedOn($cashRegister)
                ->log("PDF de caja descargado desde POS (formato: {$format})");
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
            
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de reporte', [
                'error' => $e->getMessage(),
                'register_id' => $this->reportCashRegisterId ?? null,
                'format' => $format ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->notify('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }
    
    public function cancelCloseCashRegister()
    {
        $this->showCloseRegisterModal = false;
        $this->showConfirmCloseModal = false;
        $this->closingActualAmount = 0;
        $this->closingNotes = '';
        $this->cashBreakdown = [];
        $this->cashSalesTotal = 0;
        $this->cancelledSalesTotal = 0;
    }
    
    // ==================== END CASH REGISTER METHODS ====================
    
    public function render()
    {
        // Filtrar productos activos y con stock
        $query = Product::with('tax')->where('status', true);
        
        if (strlen($this->search) > 0) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('barcode', 'like', '%' . $this->search . '%');
            });
        }
        
        $products = $query->take(24)->get();
            
        return view('livewire.p-o-s.point-of-sale', [
            'products' => $products,
        ]);
    }
}
