# Sistema de Notas de Crédito - Roadmap de Implementación

## Estado Actual del Sistema

### ✅ Lo que YA funciona:

- **Base de datos**: Tablas `sale_returns` y `sale_return_items`
- **Números únicos**: NCR-YYYYMMDD-XXXX
- **Lógica de negocio**: ReturnService con validaciones y stock management
- **UI**: Filament resource con listado, filtros y PDF
- **Auditoría**: Logging completo de operaciones
- **Soporte inicial**: Campo `refund_method` incluye 'credit_note'
- **Quick cancel**: Para ventas recientes (<5 min)

### ❌ Lo que FALTA para Notas de Crédito con Caja Cerrada:

1. **Detección automática de caja cerrada** - No verifica estado de caja
2. **Sistema de autorizaciones** - No hay workflow de aprobación supervisor
3. **Tabla customer_credits** - No existe gestión de saldo a favor
4. **Aplicación de créditos** - No se puede usar crédito en nuevas ventas
5. **Políticas configurables** - Reglas hardcodeadas
6. **Impacto en caja actual** - No registra movimientos de efectivo correctamente
7. **UI de autorización** - No hay interfaz para supervisores
8. **Validaciones mejoradas** - Falta fraud detection, productos no retornables, plazos

---

## 🎯 Plan de Implementación (3 Sprints)

### **Sprint 1: Infraestructura Core (2-3 horas)**

#### Objetivos:
Implementar detección de caja cerrada y sistema básico de autorizaciones.

#### Tareas:

**1.1 Migración: Campos de Autorización en sale_returns**
```sql
ALTER TABLE sale_returns ADD COLUMN:
- requires_authorization BOOLEAN DEFAULT FALSE
- cash_register_status VARCHAR(20) -- 'open' o 'closed'
- authorized_by_user_id BIGINT NULLABLE
- authorized_at TIMESTAMP NULLABLE
- authorization_notes TEXT NULLABLE
- rejected_by_user_id BIGINT NULLABLE
- rejected_at TIMESTAMP NULLABLE
- rejection_reason TEXT NULLABLE
```

**1.2 Nuevos Estados en sale_returns.status**
- Agregar: 'pending_approval', 'approved', 'rejected'
- Mantener: 'pending', 'completed', 'cancelled'

**1.3 Modificar ReturnService::recordReturn()**
```php
// Detectar estado de caja
$cashRegister = $sale->cashRegister;
$requiresAuthorization = $cashRegister && $cashRegister->status === 'closed';

// Si requiere autorización
if ($requiresAuthorization) {
    $saleReturn->status = 'pending_approval';
    $saleReturn->requires_authorization = true;
    $saleReturn->cash_register_status = 'closed';

    // Notificar supervisores
    $this->notifySupervisors($saleReturn);

    // NO devolver stock ni procesar reembolso hasta aprobación
    return $saleReturn;
}
```

**1.4 Crear Métodos de Autorización**
```php
ReturnService::approve(SaleReturn $return, int $authorizedBy, ?string $notes)
ReturnService::reject(SaleReturn $return, int $rejectedBy, string $reason)
```

**1.5 Permisos**
```php
// En LandlordAdminSeeder o PermissionSeeder
Permission::create(['name' => 'process_returns']);        // Cajero
Permission::create(['name' => 'authorize_returns']);      // Supervisor
Permission::create(['name' => 'configure_return_policies']); // Admin
```

---

### **Sprint 2: Gestión de Créditos de Cliente (2-3 horas)**

#### Objetivos:
Implementar sistema de crédito en cuenta del cliente.

#### Tareas:

**2.1 Migración: Tabla customer_credits**
```sql
CREATE TABLE customer_credits (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL,
    sale_return_id BIGINT NOT NULL,
    original_amount DECIMAL(12,2) NOT NULL,
    used_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, fully_used, expired
    expires_at DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id),
    INDEX idx_customer_active (customer_id, status)
);
```

**2.2 Migración: Tabla customer_credit_applications**
```sql
CREATE TABLE customer_credit_applications (
    id BIGSERIAL PRIMARY KEY,
    customer_credit_id BIGINT NOT NULL,
    sale_id BIGINT NOT NULL,
    amount_applied DECIMAL(12,2) NOT NULL,
    applied_at TIMESTAMP NOT NULL,
    applied_by_user_id BIGINT NOT NULL,

    FOREIGN KEY (customer_credit_id) REFERENCES customer_credits(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);
```

**2.3 Crear Modelo CustomerCredit**
```php
class CustomerCredit extends Model
{
    protected $connection = 'tenant';

    // Relaciones
    public function customer(): BelongsTo;
    public function saleReturn(): BelongsTo;
    public function applications(): HasMany;

    // Scopes
    public function scopeActive($query);
    public function scopeAvailable($query);

    // Métodos
    public function hasAvailableBalance(): bool;
    public function isExpired(): bool;
}
```

**2.4 Crear CustomerCreditService**
```php
class CustomerCreditService
{
    public function createCredit(SaleReturn $return): CustomerCredit;
    public function applyCredit(Customer $customer, Sale $sale, float $amount): void;
    public function getAvailableCredits(Customer $customer): Collection;
    public function getTotalAvailable(Customer $customer): float;
}
```

**2.5 Integrar con ReturnService::approve()**
```php
public function approve(SaleReturn $return, int $authorizedBy, ?string $notes)
{
    DB::transaction(function () use ($return, $authorizedBy, $notes) {
        // Actualizar estado
        $return->update([
            'status' => 'approved',
            'authorized_by_user_id' => $authorizedBy,
            'authorized_at' => now(),
            'authorization_notes' => $notes,
        ]);

        // Devolver stock
        $this->returnStock($return);

        // Procesar reembolso según tipo
        if ($return->refund_method === 'credit_note') {
            $this->customerCreditService->createCredit($return);
        } elseif ($return->refund_method === 'cash') {
            $this->processCashRefund($return); // Crea CashMovement
        }

        // Notificar cajero y cliente
        $this->notifyApproval($return);
    });
}
```

---

### **Sprint 3: UI y Experiencia de Usuario (3-4 horas)**

#### Objetivos:
Crear interfaces para autorización y visualización de créditos.

#### Tareas:

**3.1 Widget: Pending Returns Dashboard (Supervisor)**
```php
// app/Filament/App/Widgets/PendingReturnsWidget.php
class PendingReturnsWidget extends Widget
{
    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        return [
            'pending_count' => SaleReturn::where('status', 'pending_approval')->count(),
            'pending_returns' => SaleReturn::with(['originalSale', 'processedBy'])
                ->where('status', 'pending_approval')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}
```

**3.2 Modal de Autorización en SaleReturnResource**
```php
Tables\Actions\Action::make('authorize')
    ->label('Autorizar')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->visible(fn (SaleReturn $record) =>
        $record->status === 'pending_approval' &&
        auth()->user()->can('authorize_returns')
    )
    ->form([
        Forms\Components\Textarea::make('authorization_notes')
            ->label('Notas de Autorización')
            ->rows(3),
    ])
    ->action(function (SaleReturn $record, array $data) {
        app(ReturnService::class)->approve(
            $record,
            auth()->id(),
            $data['authorization_notes'] ?? null
        );

        Notification::make()
            ->success()
            ->title('Devolución Autorizada')
            ->send();
    }),

Tables\Actions\Action::make('reject')
    ->label('Rechazar')
    ->icon('heroicon-o-x-circle')
    ->color('danger')
    ->visible(fn (SaleReturn $record) =>
        $record->status === 'pending_approval' &&
        auth()->user()->can('authorize_returns')
    )
    ->requiresConfirmation()
    ->form([
        Forms\Components\Textarea::make('rejection_reason')
            ->label('Motivo del Rechazo')
            ->required()
            ->rows(3),
    ])
    ->action(function (SaleReturn $record, array $data) {
        app(ReturnService::class)->reject(
            $record,
            auth()->id(),
            $data['rejection_reason']
        );

        Notification::make()
            ->warning()
            ->title('Devolución Rechazada')
            ->send();
    }),
```

**3.3 CustomerCreditResource (Filament)**
```php
class CustomerCreditResource extends Resource
{
    protected static ?string $model = CustomerCredit::class;
    protected static ?string $navigationLabel = 'Créditos de Clientes';
    protected static ?string $navigationGroup = 'Punto de Venta';

    // Table con columnas: Cliente, Monto Original, Usado, Disponible, Estado, Vence
    // Filtros: Estado, Cliente, Rango de fechas
    // Acciones: Ver historial de uso, Ajustar crédito (admin)
}
```

**3.4 Widget en SaleResource: Créditos Disponibles**
```php
// Mostrar en CreateSale / EditSale
Forms\Components\Placeholder::make('customer_credits')
    ->label('Crédito Disponible')
    ->visible(fn (Get $get) => $get('customer_id') !== null)
    ->content(function (Get $get) {
        $customerId = $get('customer_id');
        if (!$customerId) return 'N/A';

        $total = app(CustomerCreditService::class)
            ->getTotalAvailable(Customer::find($customerId));

        return $total > 0
            ? "💰 \$" . number_format($total, 2) . " disponibles"
            : "Sin crédito disponible";
    })
    ->columnSpanFull(),
```

**3.5 Badge de Estado en SaleReturnResource**
```php
Tables\Columns\BadgeColumn::make('status')
    ->label('Estado')
    ->formatStateUsing(fn (string $state): string => match ($state) {
        'pending_approval' => '🕐 Pendiente Autorización',
        'approved' => '✅ Aprobada',
        'rejected' => '❌ Rechazada',
        'completed' => '✓ Completada',
        'pending' => '⏳ Pendiente',
        'cancelled' => '🚫 Cancelada',
        default => $state,
    })
    ->color(fn (string $state): string => match ($state) {
        'pending_approval' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'success',
        'pending' => 'gray',
        'cancelled' => 'danger',
        default => 'gray',
    }),
```

**3.6 Notificaciones**
```php
// Cuando se crea devolución que requiere autorización
Notification::make()
    ->title('Devolución Requiere Autorización')
    ->body("La devolución {$return->return_number} fue creada y requiere autorización de un supervisor.")
    ->info()
    ->actions([
        Action::make('view')
            ->label('Ver Devolución')
            ->url(SaleReturnResource::getUrl('view', ['record' => $return->id]))
    ])
    ->sendToDatabase(User::permission('authorize_returns')->get());
```

---

## 📊 Fase 4 (Futuro): Mejoras Avanzadas

**No incluido en MVP, para implementar después:**

1. **Políticas Configurables**
   - Tabla `return_policies` o integrar en `system_settings`
   - UI para configurar: plazos, montos, autorizaciones requeridas

2. **Fraud Detection**
   - Alertar si cliente tiene >3 devoluciones en 30 días
   - Marcar productos con alta tasa de devolución
   - Bloquear devoluciones de ciertos clientes (lista negra)

3. **Productos No Retornables**
   - Campo `is_returnable` en tabla `products`
   - Categorías excluidas de devolución
   - Validación al intentar devolver

4. **Reportes Avanzados**
   - Dashboard de devoluciones por motivo/producto/cajero
   - Gráficas de tendencias
   - Productos más devueltos
   - Ratio de devoluciones por cliente

5. **Integración con Pagos**
   - Reversa automática de pagos con tarjeta (API Stripe/MercadoPago)
   - Tracking de reembolsos procesados
   - Reconciliación bancaria

6. **Workflow Complejo**
   - Múltiples niveles de autorización (supervisor → gerente → admin)
   - Devoluciones programadas (cliente tiene 7 días para traer producto)
   - Cambios de producto en lugar de reembolso

---

## 🎯 Resultado Final del MVP (Sprints 1-3)

### Funcionalidades Implementadas:

✅ **Detección automática**: Sistema detecta si caja está abierta o cerrada
✅ **Workflow de autorización**: Supervisor aprueba/rechaza devoluciones
✅ **Gestión de créditos**: Cliente acumula saldo a favor usable en compras
✅ **UI completa**: Modal de autorización, widget de pendientes, badges de estado
✅ **Trazabilidad**: Quién autorizó, cuándo, con qué notas
✅ **Notificaciones**: Alertas en tiempo real para supervisores
✅ **Permisos**: Control basado en roles (cajero / supervisor / admin)
✅ **Auditoría**: Logging completo de todas las operaciones

### Flujo Completo:

```
1. Cajero intenta devolución
   ├─ Sistema verifica estado de caja original
   ├─ Si caja abierta → Procesa directamente (como ahora)
   └─ Si caja cerrada → Status 'pending_approval' + notifica supervisor

2. Supervisor recibe notificación
   ├─ Ve detalle de devolución en dashboard widget
   ├─ Revisa: monto, productos, razón, cliente
   └─ Decide: Aprobar o Rechazar

3a. Si aprueba:
   ├─ Stock se devuelve automáticamente
   ├─ Si refund_method = 'credit_note' → Crea CustomerCredit
   ├─ Si refund_method = 'cash' → Registra salida en caja actual
   └─ Notifica cajero y cliente

3b. Si rechaza:
   ├─ Status → 'rejected'
   ├─ Registra motivo de rechazo
   └─ Notifica cajero

4. Cliente usa crédito (si aplica):
   ├─ En próxima venta, cajero ve crédito disponible
   ├─ Aplica total o parcialmente el crédito
   └─ Sistema registra uso en customer_credit_applications
```

---

## 📝 Notas de Implementación

### Consideraciones Técnicas:

- **Conexión de BD**: Todas las tablas usan `connection = 'tenant'`
- **Permisos**: Usar Spatie Permissions con guards correctos (`tenant` o `web`)
- **Transacciones**: Todas las operaciones críticas en `DB::transaction()`
- **Observers**: Evitar triggers innecesarios con `saveQuietly()` al actualizar stock
- **Testing**: Crear tests para cada método de ReturnService y CustomerCreditService

### Orden de Implementación Recomendado:

1. **Migraciones** primero (Sprint 1 y 2)
2. **Modelos y relaciones**
3. **Servicios (lógica de negocio)**
4. **Permisos y políticas**
5. **UI (Filament resources)**
6. **Notificaciones**
7. **Testing**
8. **Documentación de usuario**

### Casos Edge a Considerar:

- ¿Qué pasa si se aprueba devolución pero ya no hay espacio en stock?
- ¿Se puede revocar una autorización ya aprobada?
- ¿Cliente puede transferir crédito a otro cliente?
- ¿Créditos expiran? ¿Qué pasa con los vencidos?
- ¿Se puede devolver un producto comprado con crédito?
- ¿Múltiples créditos activos del mismo cliente se suman?

---

## 🚀 Listo para Implementar

Este roadmap está diseñado para ser implementado de forma incremental. Cada sprint es independiente y agrega valor inmediato al sistema.

**Última actualización**: 2025-10-25
**Estado**: Documentado - Pendiente de implementación
**Prioridad**: Media (implementar después de resolver errores críticos)
