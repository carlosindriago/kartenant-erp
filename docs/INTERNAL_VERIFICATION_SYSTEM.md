# Sistema de Verificación Interna de Documentos

## 📋 Descripción General

El sistema de verificación interna permite a las empresas validar y auditar documentos sensibles internos como aperturas y cierres de caja, movimientos de inventario, y registros de empleados. A diferencia de la verificación pública (para facturas y notas de crédito), estos documentos requieren autenticación y permisos específicos.

## 🎯 Características Principales

### Seguridad Multicapa
- ✅ Autenticación obligatoria de usuarios
- ✅ Permisos granulares por tipo de documento
- ✅ Hash único de verificación por documento
- ✅ Middleware de seguridad dedicado
- ✅ Auditoría completa de todos los accesos

### Gestión de Caja Registradora
- ✅ Apertura de caja con saldo inicial
- ✅ Cierre de caja con totales y métodos de pago
- ✅ Detección automática de discrepancias
- ✅ Aprobación/rechazo por supervisores
- ✅ Numeración automática correlativa

### PDFs Profesionales
- ✅ Formato térmico 80mm para tickets
- ✅ Formato A4 para archivo y supervisión
- ✅ QR codes de verificación integrados
- ✅ Diseño profesional con branding
- ✅ Información detallada de totales y discrepancias

### Auditoría Completa
- ✅ Registro de todas las verificaciones
- ✅ Trail de auditoría por documento
- ✅ Integración con Activity Log
- ✅ Visualización de historial de accesos

## 🏗️ Arquitectura

### Trait HasInternalVerification

```php
use App\Models\Traits\HasInternalVerification;

class MiDocumentoInterno extends Model
{
    use HasInternalVerification;
    
    // Definir permiso requerido
    public function getVerificationPermission(): string
    {
        return 'verify_mi_documento';
    }
}
```

### Modelos Implementados

#### CashRegisterOpening
```php
- opening_number: Número correlativo
- opened_by: Usuario que abre
- opened_at: Fecha/hora de apertura
- opening_balance: Saldo inicial
- status: open | closed
- notes: Observaciones
- verification_hash: Hash de verificación
```

#### CashRegisterClosing
```php
- closing_number: Número correlativo
- opening_id: Relación con apertura
- closed_by: Usuario que cierra
- closed_at: Fecha/hora de cierre
- opening_balance: Saldo inicial
- total_sales: Total de ventas
- total_cash: Total efectivo
- total_card: Total tarjeta
- total_other: Otros métodos
- expected_balance: Saldo esperado
- closing_balance: Saldo real contado
- difference: Diferencia (+ sobrante, - faltante)
- status: pending_review | approved | rejected
- notes: Observaciones
- discrepancy_notes: Notas sobre discrepancias
```

## 🔐 Permisos

### Permisos Generales
- `view_internal_verifications` - Ver verificaciones internas
- `verify_any_internal_document` - Verificar cualquier documento (Admin)
- `view_verification_audit_trail` - Ver auditoría de verificaciones

### Permisos de Caja
- `verify_cash_register_opening` - Verificar aperturas de caja
- `verify_cash_register_closing` - Verificar cierres de caja
- `manage_cash_register` - Gestionar caja (abrir/cerrar)
- `view_cash_register_reports` - Ver reportes de caja

### Permisos de Inventario (Futura implementación)
- `verify_stock_movements` - Verificar movimientos de inventario
- `verify_stock_adjustments` - Verificar ajustes de inventario

### Permisos de Auditoría (Futura implementación)
- `verify_product_creation` - Verificar creación de productos
- `verify_customer_creation` - Verificar creación de clientes
- `verify_employee_creation` - Verificar creación de empleados

### Asignación por Rol

| Permiso | Admin | Gerente | Supervisor | Cajero |
|---------|-------|---------|------------|--------|
| view_internal_verifications | ✅ | ✅ | ✅ | ❌ |
| verify_any_internal_document | ✅ | ❌ | ❌ | ❌ |
| verify_cash_register_opening | ✅ | ✅ | ✅ | ❌ |
| verify_cash_register_closing | ✅ | ✅ | ✅ | ❌ |
| manage_cash_register | ✅ | ❌ | ✅ | ✅ |
| view_cash_register_reports | ✅ | ✅ | ✅ | ❌ |

## 🚀 Uso

### Crear Apertura de Caja

1. Acceder a `/app/cash-register-openings`
2. Clic en "Nueva Apertura"
3. Completar:
   - Cajero (auto-completado con usuario actual)
   - Saldo inicial
   - Formato de PDF (Térmico/A4)
   - Observaciones (opcional)
4. Guardar
5. Descargar PDF con QR de verificación

### Crear Cierre de Caja

1. Acceder a `/app/cash-register-closings`
2. Clic en "Nuevo Cierre"
3. Seleccionar apertura a cerrar
4. Completar:
   - Total de ventas del día
   - Total por método de pago (efectivo, tarjeta, otros)
   - Total de transacciones
   - Ticket promedio
   - Saldo real contado en caja
5. El sistema calcula automáticamente:
   - Saldo esperado
   - Diferencia (sobrante/faltante)
6. Si hay discrepancia, agregar notas explicativas
7. Guardar - Estado: "Pendiente de Revisión"
8. Supervisor/Gerente puede Aprobar o Rechazar

### Verificar Documento

1. Escanear QR code del PDF o acceder a URL
2. Sistema valida autenticación y permisos
3. Se muestra:
   - Información completa del documento
   - Estado actual
   - Trail de auditoría (quién verificó y cuándo)
4. Opciones:
   - Descargar PDF
   - Ver en pantalla
   - Registrar verificación (se loguea en auditoría)

## 🔗 Rutas

### Rutas de Verificación Interna
```php
// Todas requieren autenticación + permiso
GET  /app/internal-verify/{hash}         - Ver documento
GET  /app/internal-verify/{hash}/pdf     - Descargar PDF
POST /app/internal-verify/{hash}/verify  - Registrar verificación
```

### Recursos Filament
```php
/app/cash-register-openings   - Gestión de aperturas
/app/cash-register-closings   - Gestión de cierres
```

## 📊 Auditoría

Todas las acciones son registradas automáticamente:

```php
// Creación de apertura
activity()
    ->causedBy($user)
    ->performedOn($opening)
    ->log('Apertura de caja registrada');

// Verificación de documento
activity()
    ->causedBy($user)
    ->performedOn($document)
    ->log('Documento verificado');

// Detección de discrepancia
activity()
    ->causedBy($user)
    ->performedOn($closing)
    ->withProperties(['difference' => $closing->difference])
    ->log('Discrepancia detectada en cierre de caja');
```

## 🧪 Testing

### Verificar Instalación

```bash
# Ejecutar migraciones
./vendor/bin/sail artisan tenants:artisan "migrate"

# Ejecutar seeder de permisos
./vendor/bin/sail artisan tenants:artisan "db:seed --class=InternalVerificationPermissionsSeeder"

# Verificar permisos creados
./vendor/bin/sail artisan tenants:artisan "tinker"
>>> \Spatie\Permission\Models\Permission::where('guard_name', 'web')->pluck('name');
```

### Flujo de Prueba Completo

1. **Crear Apertura**
   - Login como cajero
   - Crear apertura con $1000 de saldo inicial
   - Descargar PDF térmico
   - Verificar QR code funciona

2. **Registrar Ventas** (manual/simulado)
   - Registrar ventas del día
   - Ejemplo: $5000 en efectivo, $3000 en tarjeta

3. **Crear Cierre**
   - Login como cajero
   - Crear cierre con totales del día
   - Simular faltante: Contar $8900 (falta $100)
   - Agregar nota de discrepancia
   - Verificar estado: "Pendiente de Revisión"

4. **Aprobar/Rechazar**
   - Login como supervisor
   - Ver cierre pendiente
   - Aprobar o rechazar con motivo

5. **Verificar Auditoría**
   - Acceder a `/app/activities`
   - Verificar logs de todas las acciones

## 🔮 Próximos Pasos

### Documentos a Implementar

1. **Movimientos de Inventario**
   - Entradas de mercancía
   - Salidas de inventario
   - Transferencias entre sucursales
   - Ajustes de stock

2. **Registros de Empleados**
   - Alta de empleado
   - Modificación de datos sensibles
   - Baja de empleado

3. **Registros de Clientes**
   - Alta de cliente
   - Modificación de crédito
   - Cambios en límites de crédito

4. **Registros de Productos**
   - Alta de producto
   - Cambios de precio masivos
   - Descontinuación de productos

### Mejoras Técnicas

- [ ] Dashboard de discrepancias por período
- [ ] Alertas automáticas por discrepancias > umbral
- [ ] Reportes de auditoría exportables
- [ ] Integración con sistema de notificaciones
- [ ] API para verificación desde apps móviles
- [ ] Firma digital de documentos

## 📝 Notas Técnicas

### Extensibilidad

Para agregar un nuevo tipo de documento interno:

1. Crear modelo que use `HasInternalVerification`
2. Implementar `getVerificationPermission()`
3. Crear migración con campo `verification_hash`
4. Crear vistas PDF (thermal y a4)
5. Agregar permisos al seeder
6. Registrar modelo en `InternalVerificationController::$verifiableModels`
7. Crear recurso Filament para gestión

### Diferencias con Verificación Pública

| Aspecto | Pública | Interna |
|---------|---------|---------|
| Autenticación | No requerida | Obligatoria |
| Permisos | No aplica | Granulares por documento |
| Tipos de documentos | Facturas, NC | Caja, inventario, registros |
| Auditoría | Básica | Completa con trail |
| Formato PDF | Solo A4 | Térmico + A4 |
| URL | /verify/{hash} | /app/internal-verify/{hash} |

## 🛡️ Seguridad

### Buenas Prácticas Implementadas

- ✅ Hash único e irrepetible por documento
- ✅ Middleware de validación de permisos
- ✅ Logging completo de accesos
- ✅ Separación de contextos (público vs interno)
- ✅ Rate limiting en rutas de verificación
- ✅ Validación de tenant context
- ✅ Soft deletes para no perder histórico

### Recomendaciones

- Revisar logs de auditoría semanalmente
- Establecer umbrales de discrepancia aceptables
- Rotar cajeros periódicamente
- Realizar auditorías sorpresa
- Mantener PDFs archivados por período fiscal
- Backup regular de base de datos

## 📚 Referencias

- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)
- [FilamentPHP Resources](https://filamentphp.com/docs/3.x/panels/resources)
- [DomPDF Documentation](https://github.com/dompdf/dompdf)

---

**Fecha de Implementación:** 15 de Octubre, 2025  
**Versión:** 1.0.0  
**Estado:** ✅ Producción
