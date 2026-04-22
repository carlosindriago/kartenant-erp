# Patrones de Implementación: Combined Tabs Mode

**Versión:** 1.0
**Fecha:** 8 de diciembre de 2025
**Autor:** Emporio Documentation Manager
**Propósito:** Guía de patrones y ejemplos para implementar Combined Tabs Mode en múltiples recursos

---

## 🎯 Introducción

Este documento proporciona patrones reutilizables y ejemplos prácticos para implementar Combined Tabs Mode en diferentes recursos del sistema Emporio Digital. Basado en la solución exitosa implementada en `EditTenant.php`.

---

## 📋 Patrones Base

### Patrón 1: Formulario Después de Relaciones (Estándar)

**Casos de uso:** Edición de entidades donde las relaciones proporcionan contexto importante.

```php
<?php

namespace App\Filament\Resources\YourResource\Pages;

use App\Filament\Resources\YourResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Actions;
use Filament\Actions\Action;

class EditYourResource extends EditRecord
{
    protected static string $resource = YourResource::class;

    // ... otros métodos ...

    /**
     * Activa combined tabs mode
     */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    /**
     * Posiciona el formulario después de las relaciones
     */
    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    }

    /**
     * Personaliza la pestaña del formulario
     */
    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-pencil-square'; // Icono de edición
    }

    /**
     * Etiqueta clara para la pestaña
     */
    public function getContentTabLabel(): string
    {
        return 'Datos Principales';
    }

    /**
     * Botones personalizados consistentes
     */
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
                ->color('secondary')
                ->icon('heroicon-o-x-mark'),

            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
```

### Patrón 2: Formulario Antes de Relaciones

**Casos de uso:** Cuando los datos principales son necesarios antes de ver relaciones.

```php
/**
 * Posiciona el formulario antes de las relaciones
 */
public function getContentTabPosition(): ?ContentTabPosition
{
    return ContentTabPosition::Before;
}

/**
 * Icono específico para contexto de "configuración principal"
 */
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-cog-6-tooth';
}

/**
 * Etiqueta apropiada para formulario principal
 */
public function getContentTabLabel(): string
    {
    return 'Configuración Principal';
}
```

---

## 🎨 Patrones de Personalización

### Iconos Contextuales según Tipo de Recurso

```php
// Para Clientes
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-users';
}

// Para Productos
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-shopping-bag';
}

// Para Ventas
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-currency-dollar';
}

// Para Configuración
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-cog-6-tooth';
}

// Para Documentos
public function getContentTabIcon(): ?string
{
    return 'heroicon-o-document-text';
}
```

### Etiquetas en Español para Usuario "Ernesto"

```php
// Para edición general
public function getContentTabLabel(): string
{
    return 'Información Principal';
}

// Para configuración
public function getContentTabLabel(): string
{
    return 'Configuración';
}

// Para datos del cliente
public function getContentTabLabel(): string
{
    return 'Datos del Cliente';
}

// Para detalles del producto
public function getContentTabLabel(): string
{
    return 'Detalles del Producto';
}

// Para información fiscal
public function getContentTabLabel(): string
{
    return 'Información Fiscal';
}
```

---

## 🛠️ Ejemplos Específicos del Sistema Emporio

### Ejemplo 1: Recurso Cliente

```php
<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Actions;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-user';
    }

    public function getContentTabLabel(): string
    {
        return 'Datos del Cliente';
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
                ->color('secondary')
                ->icon('heroicon-o-x-mark'),

            Actions\Action::make('save')
                ->label('Actualizar Cliente')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
```

### Ejemplo 2: Recurso Producto

```php
<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Actions;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-shopping-bag';
    }

    public function getContentTabLabel(): string
    {
        return 'Información del Producto';
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
                ->color('secondary')
                ->icon('heroicon-o-x-mark'),

            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
```

### Ejemplo 3: Recurso con Workflow (Pedidos)

```php
<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Actions;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-shopping-cart';
    }

    public function getContentTabLabel(): string
    {
        return 'Detalles del Pedido';
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
                ->color('secondary')
                ->icon('heroicon-o-x-mark'),

            Actions\Action::make('save')
                ->label('Actualizar Pedido')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),

            // Acción adicional específica para pedidos
            Actions\Action::make('process')
                ->label('Procesar Pedido')
                ->action('processOrder')
                ->color('warning')
                ->icon('heroicon-o-truck')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'pending'),
        ];
    }
}
```

---

## 📋 Checklist de Implementación

### Pre-Implementación

- [ ] Verificar que el recurso hereda de `EditRecord`
- [ ] Confirmar versión de Filament v3.x
- [ ] Identificar RelationManagers existentes
- [ ] Determinar posición deseada (Before/After)

### Implementación

- [ ] Agregar `use Filament\Resources\Pages\ContentTabPosition;`
- [ ] Implementar `hasCombinedRelationManagerTabsWithContent()`
- [ ] Configurar `getContentTabPosition()`
- [ ] Personalizar `getContentTabIcon()`
- [ ] Establecer `getContentTabLabel()`
- [ ] Mantener o adaptar `getFormActions()`

### Post-Implementación

- [ ] Probar navegación entre pestañas
- [ ] Validar funcionamiento de botones
- [ ] Verificar diseño responsive
- [ ] Probar validaciones del formulario
- [ ] Comprobar permisos y autorizaciones

---

## 🔍 Casos Especiales

### Recursos Sin RelationManagers

```php
// No activar combined tabs si no hay relaciones
public function hasCombinedRelationManagerTabsWithContent(): bool
{
    return false; // Usar formulario tradicional
}
```

### Conditional Activation según Rol

```php
public function hasCombinedRelationManagerTabsWithContent(): bool
{
    // Solo para roles avanzados
    return auth()->user()->hasRole(['admin', 'manager']);
}
```

### Dinamic Icon según Estado

```php
public function getContentTabIcon(): ?string
{
    return match($this->record->status) {
        'active' => 'heroicon-m-check-circle',
        'inactive' => 'heroicon-m-x-circle',
        'pending' => 'heroicon-m-clock',
        default => 'heroicon-o-document-text'
    };
}
```

---

## 🚀 Buenas Prácticas

### 1. Consistencia Visual

- Mismo patrón en recursos similares
- Iconos apropiados al tipo de recurso
- Etiquetas claras en español

### 2. Performance

- Sin impacto en queries existentes
- Mantener validaciones eficientes
- No sobrecargar con lógica compleja

### 3. Accesibilidad

- Navegación por teclado funcional
- Descripciones claras para lectores de pantalla
- Contraste de colores adecuado

### 4. Testing

```php
// Test example para verificar comportamiento
public function test_combined_tabs_mode_works()
{
    $user = User::factory()->create();
    $record = YourModel::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(static::getResource()::getUrl('edit', ['record' => $record]));

    $response->assertStatus(200);
    $response->assertSee('Datos Principales'); // Verificar etiqueta
}
```

---

## 📚 Referencias Rápidas

### Valores Posibles para ContentTabPosition

```php
ContentTabPosition::Before  // Formulario antes de relaciones
ContentTabPosition::After   // Formulario después de relaciones
```

### Iconos Heroicons Comunes

```php
'heroicon-o-user'           // Usuario
'heroicon-o-users'          // Múltiples usuarios
'heroicon-o-shopping-bag'   // Producto
'heroicon-o-currency-dollar' // Dinero/Ventas
'heroicon-o-cog-6-tooth'    // Configuración
'heroicon-o-document-text'   // Documento
'heroicon-o-check'          // Confirmar
'heroicon-o-x-mark'         // Cancelar
'heroicon-o-pencil'         // Editar
```

### Namespace Imports Requeridos

```php
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Actions;
use Filament\Actions\Action;
```

---

## 🔄 Migración desde Formularios Tradicionales

### Paso 1: Identificar Recursos Candidatos

- Recursos con múltiples RelationManagers
- Formularios donde el contexto de relaciones es importante
- Reportes de UX sobre flujo de navegación

### Paso 2: Aplicar Patrón Base

Copiar el patrón estándar y adaptar según el recurso específico.

### Paso 3: Personalizar

Ajustar iconos, etiquetas y acciones según el contexto del negocio.

### Paso 4: Validar

Probar exhaustivamente el flujo completo de navegación.

---

**Conclusión:** Estos patrones proporcionan una base sólida para implementar Combined Tabs Mode consistentemente en todo el sistema Emporio Digital, mejorando la experiencia del usuario y manteniendo la coherencia en la interfaz.