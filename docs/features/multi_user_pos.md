# Feature: Sistema Multi-Usuario con Cajas Individuales

## 🎯 Objetivo
Implementar un sistema donde cada usuario con permisos POS puede abrir su propia caja y trabajar simultáneamente, con total trazabilidad de todas las acciones por usuario.

## 📋 Requisitos Funcionales

### 1. Apertura de Caja Individual por Usuario
- ✅ Cada usuario abre SU PROPIA caja con su usuario_id
- ✅ Un usuario puede tener solo UNA caja abierta a la vez
- ✅ Múltiples usuarios pueden tener cajas abiertas simultáneamente
- ✅ Número de caja único por apertura: REG-YYYYMMDD-XXXX
- ✅ Registro de monto inicial por usuario

### 2. Trazabilidad Completa
- ✅ Cada venta registra: `created_by_user_id`
- ✅ Cada venta está asociada a: `cash_register_id`
- ✅ Cada cierre de caja registra: `closed_by_user_id`
- ✅ Cada modificación/cancelación registra: `cancelled_by_user_id`
- ✅ Log de auditoría con timestamps

### 3. Control de Acceso
- ✅ Verificar permisos: `pos.access`, `pos.open_register`, `pos.close_register`
- ✅ Un usuario solo puede ver/cerrar SU propia caja
- ✅ Supervisores pueden ver todas las cajas abiertas
- ✅ Gerentes pueden cerrar cualquier caja (emergencias)

### 4. Dashboard Multi-Usuario
- ✅ Widget Estado de Caja muestra TODAS las cajas abiertas
- ✅ Cada fila = 1 usuario con su caja
- ✅ Totales generales consolidados
- ✅ Filtro por usuario (opcional)

### 5. Restricciones de Seguridad
- ❌ No permitir abrir 2 cajas al mismo usuario
- ❌ No permitir usar POS sin caja abierta
- ❌ No permitir cerrar caja de otro usuario (excepto supervisor)
- ❌ No permitir modificar ventas de otros usuarios (excepto supervisor)

## 🗄️ Cambios en Base de Datos

### Tabla: `cash_registers` (ya existe)
```php
✅ opened_by_user_id (ya existe)
✅ closed_by_user_id (ya existe)
✅ initial_amount (ya existe)
✅ register_number (ya existe)
✅ status (ya existe)
```

### Tabla: `sales` (verificar/actualizar)
```php
✅ cash_register_id (debe existir)
+ created_by_user_id (nuevo - usuario que hizo la venta)
+ cancelled_by_user_id (nuevo - usuario que canceló)
+ updated_by_user_id (nuevo - último usuario que modificó)
```

### Migración Necesaria
```php
Schema::table('sales', function (Blueprint $table) {
    $table->foreignId('created_by_user_id')->nullable()->after('customer_id');
    $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at');
    $table->foreignId('updated_by_user_id')->nullable();
    
    $table->foreign('created_by_user_id')->references('id')->on('users');
    $table->foreign('cancelled_by_user_id')->references('id')->on('users');
    $table->foreign('updated_by_user_id')->references('id')->on('users');
});
```

## 🔧 Componentes a Implementar

### 1. Middleware: `EnsureUserHasOpenCashRegister`
```php
// Verifica que el usuario tenga caja abierta antes de usar POS
// Redirige a apertura si no tiene
// Permite múltiples cajas abiertas (diferentes usuarios)
```

### 2. Service: `CashRegisterService`
```php
openRegister($userId, $initialAmount, $notes)
closeRegister($registerId, $actualAmount, $breakdown, $notes)
getUserOpenRegister($userId)
canUserOpenRegister($userId)
canUserCloseRegister($userId, $registerId)
getActiveCashRegisters() // Todas las abiertas
getUserCashRegisterHistory($userId, $period)
```

### 3. Políticas de Acceso: `CashRegisterPolicy`
```php
open(User $user) // Puede abrir caja
close(User $user, CashRegister $register) // Puede cerrar esta caja
viewAny(User $user) // Puede ver todas
viewOwn(User $user, CashRegister $register) // Es su caja
forceClose(User $user, CashRegister $register) // Supervisor fuerza cierre
```

### 4. Scopes en CashRegister
```php
scopeOwnedBy($query, $userId)
scopeOpenByUser($query, $userId)
scopeActiveRegisters($query) // Todas abiertas
```

### 5. Observers/Events
```php
CashRegisterOpened($cashRegister, $user)
CashRegisterClosed($cashRegister, $user)
SaleCreated($sale, $user, $cashRegister)
SaleCancelled($sale, $user)
```

### 6. Recursos Filament
```php
CashRegisterResource // Admin/supervisores
- Lista todas las cajas
- Filtros por usuario, estado, fecha
- Acciones: Ver detalle, Forzar cierre (supervisor)
```

### 7. Páginas POS
```php
OpenCashRegisterPage // Abrir mi caja
CloseCashRegisterPage // Cerrar mi caja
POSMainPage // Requiere caja abierta
```

## 📊 Flujo de Trabajo

### Flujo 1: Usuario abre caja
```
1. Usuario inicia sesión con permisos POS
2. Sistema verifica: ¿Tiene caja abierta?
   - NO → Redirigir a /pos/open-register
   - SÍ → Acceso directo a POS
3. Usuario ingresa monto inicial
4. Sistema crea CashRegister con:
   - opened_by_user_id = auth()->id()
   - status = 'open'
   - register_number = auto-generado
5. Usuario accede al POS
```

### Flujo 2: Usuario hace venta
```
1. Usuario en POS (ya tiene caja abierta)
2. Selecciona productos, cliente, método pago
3. Confirma venta
4. Sistema registra Sale con:
   - cash_register_id = usuario_caja_actual
   - created_by_user_id = auth()->id()
   - created_at = now()
5. Venta guardada y enlazada a su caja
```

### Flujo 3: Usuario cierra caja
```
1. Usuario va a /pos/close-register
2. Sistema muestra resumen de SU turno:
   - Ventas efectivo, tarjeta, transferencia
   - Monto esperado en caja
   - Devoluciones
3. Usuario cuenta el efectivo real
4. Ingresa desglose de billetes/monedas
5. Sistema calcula diferencia
6. Usuario confirma cierre
7. Sistema actualiza CashRegister:
   - closed_by_user_id = auth()->id()
   - closed_at = now()
   - actual_amount, difference
   - status = 'closed'
8. Genera PDF de arqueo
```

### Flujo 4: Supervisor revisa cajas
```
1. Supervisor accede a Dashboard
2. Ve widget "Estado de Caja" con TODAS las cajas:
   ┌──────────────┬─────────┬─────────┬─────────┐
   │ Juan Pérez   │ $4,000  │ $12,500 │ $5,900  │
   │ María López  │ $3,000  │ $8,200  │ $3,800  │
   │ Carlos Ruiz  │ $5,000  │ $15,300 │ $7,100  │
   ├──────────────┴─────────┴─────────┴─────────┤
   │ TOTAL (3 cajas)  $12,000  $36,000  $16,800 │
   └──────────────────────────────────────────────┘
3. Puede filtrar por usuario
4. Puede forzar cierre si es necesario
```

## 🔒 Permisos Requeridos

```php
// Permisos base POS
'pos.access' => 'Acceder al POS',
'pos.create_sale' => 'Crear ventas',
'pos.cancel_sale' => 'Cancelar ventas propias',

// Permisos caja
'pos.open_register' => 'Abrir caja',
'pos.close_register' => 'Cerrar caja propia',
'pos.view_own_register' => 'Ver mi caja',

// Permisos supervisores
'pos.view_all_registers' => 'Ver todas las cajas',
'pos.close_any_register' => 'Cerrar cualquier caja',
'pos.cancel_any_sale' => 'Cancelar cualquier venta',
'pos.view_reports' => 'Ver reportes completos',
```

## 📈 Reportes a Implementar

### 1. Reporte por Usuario
- Ventas totales del día/semana/mes
- Ticket promedio
- Métodos de pago utilizados
- Devoluciones realizadas
- Historial de arqueos

### 2. Reporte Comparativo
- Ranking de vendedores
- Productividad por hora
- Diferencias en arqueos
- Eficiencia por usuario

### 3. Reporte de Auditoría
- Todas las acciones por usuario
- Timestamp de cada operación
- Cambios realizados
- Cancelaciones y motivos

## 🎨 UI/UX Mejoras

### Widget Estado de Caja (ya implementado parcialmente)
- ✅ Tabla con fila por usuario
- ✅ Totales generales
- + Botón "Ver mi caja" (rápido)
- + Botón "Cerrar mi caja" (si está abierta)
- + Indicador visual de tiempo abierto (alerta si >12h)

### Página POS
- + Header con info de caja actual:
  ```
  🟢 Caja: REG-20251013-0001 | Juan Pérez | Abierta: 2h 30m | Inicial: $4,000
  ```
- + Resumen rápido en sidebar
- + Botón "Cerrar Caja" en toolbar

### Notificaciones
- 🔔 "Tu caja lleva abierta 8 horas, considera cerrar"
- 🔔 "Supervisor ha cerrado tu caja [motivo]"
- 🔔 "Arqueo completado: diferencia de $X"

## 🧪 Testing

### Unit Tests
- `CashRegisterService` → Lógica de apertura/cierre
- `CashRegisterPolicy` → Políticas de acceso
- `CashRegister` model → Scopes y helpers

### Feature Tests
- Usuario puede abrir caja
- Usuario NO puede abrir 2 cajas
- Usuario solo ve su caja
- Supervisor ve todas las cajas
- Ventas se asocian correctamente
- Cierre calcula diferencias correctamente

### Integration Tests
- Flujo completo: Abrir → Vender → Cerrar
- Multi-usuario simultáneo
- Permisos y restricciones

## 📝 Documentación

### Manual de Usuario
1. Cómo abrir mi caja
2. Cómo hacer ventas
3. Cómo cerrar mi caja
4. Qué hacer si hay diferencia en arqueo

### Manual Supervisor
1. Monitorear cajas activas
2. Revisar reportes por usuario
3. Forzar cierre de caja
4. Resolver discrepancias

## 🚀 Plan de Implementación

### Fase 1: Base de Datos y Modelos (ACTUAL)
- [x] Revisar estructura actual de CashRegister
- [ ] Crear migración para agregar campos en Sales
- [ ] Actualizar modelo Sale con relaciones
- [ ] Crear scopes en CashRegister

### Fase 2: Lógica de Negocio
- [ ] Implementar CashRegisterService
- [ ] Crear CashRegisterPolicy
- [ ] Implementar Middleware
- [ ] Crear Observers/Events

### Fase 3: UI/UX
- [ ] Página OpenCashRegister
- [ ] Página CloseCashRegister
- [ ] Actualizar widget Estado de Caja
- [ ] Header en POS con info de caja

### Fase 4: Reportes y Admin
- [ ] CashRegisterResource (Filament)
- [ ] Reportes por usuario
- [ ] Auditoría completa
- [ ] Exportación a Excel/PDF

### Fase 5: Testing y Documentación
- [ ] Tests unitarios
- [ ] Tests de integración
- [ ] Documentación usuario
- [ ] Documentación técnica

## 🎯 Criterios de Éxito

✅ Múltiples usuarios pueden tener cajas abiertas simultáneamente
✅ Cada venta está asociada al usuario y su caja
✅ No se puede usar POS sin caja abierta
✅ Un usuario no puede abrir 2 cajas a la vez
✅ Dashboard muestra todas las cajas activas en tiempo real
✅ Cierre de caja calcula correctamente diferencias
✅ Auditoría completa de todas las acciones
✅ Permisos y políticas funcionan correctamente
✅ Tests pasando al 100%

## 📊 Métricas de Rendimiento

- Tiempo de apertura de caja: < 5 segundos
- Tiempo de cierre de caja: < 10 segundos
- Dashboard con 10 cajas activas: < 2 segundos
- Consulta de historial: < 3 segundos
- Generación de PDF de arqueo: < 5 segundos
