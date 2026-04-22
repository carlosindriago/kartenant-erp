# Recomendaciones y Mejores Prácticas - Manejo de Iconos SVG
## Guía para el Equipo de Desarrollo - Emporio Digital

---

## 🎯 Objetivo

Este documento establece las mejores prácticas y procedimientos para el manejo de iconos SVG en el proyecto Emporio Digital, con el fin de prevenir errores, mantener consistencia y optimizar el desarrollo.

---

## 📋 Tabla de Contenidos

1. [Validación Pre-Commit](#validación-pre-commit)
2. [Herramientas de Verificación](#herramientas-de-verificación)
3. [Proceso de Reporte de Errores](#proceso-de-reporte-de-errores)
4. [Mejores Prácticas de Desarrollo](#mejores-prácticas-de-desarrollo)
5. [Checklist de Revisión](#checklist-de-revisión)
6. [Recursos y Referencias](#recursos-y-referencias)

---

## 🔍 Validación Pre-Commit

### Antes de hacer commit de cambios que incluyen iconos:

#### 1. Verificación Automática (Obligatoria)
```bash
# Ejecutar siempre antes de commit
./vendor/bin/sail artisan icons:cache
./vendor/bin/sail artisan view:clear
```

#### 2. Validación de Sintaxis
```bash
# Buscar referencias de iconos en archivos modificados
git diff --name-only HEAD~1 | grep "\.php$" | xargs grep -l "icon(" | xargs grep "heroicon-"

# Verificar que todos los iconos usen el prefijo correcto
grep -r "->icon('" app/ --include="*.php" | grep -v "heroicon-" && echo "❌ ERROR: Iconos sin prefijo" || echo "✅ OK: Todos los iconos tienen prefijo"
```

#### 3. Validación de Existencia
```bash
# En tinker, verificar iconos nuevos usados
./vendor/bin/sail artisan tinker
>>> collect(['heroicon-o-nuevo-icono', 'heroicon-s-otro-icono'])->each(fn($icon) =>
>>>     \BladeUI\Icons\IconsManifest::get('heroicon')->has($icon) ?
>>>         "✅ $icon existe" :
>>>         "❌ $icon NO existe"
>>> );
```

---

## 🛠️ Herramientas de Verificación

### 1. Script de Validación Rápida
```bash
#!/bin/bash
# validate-icons.sh - Script de validación de iconos

echo "🔍 Validando iconos SVG en el proyecto..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar iconos sin prefijo
echo -n "Verificando prefijos... "
invalid_icons=$(grep -r "->icon('" app/ --include="*.php" | grep -v "heroicon-" | wc -l)
if [ $invalid_icons -eq 0 ]; then
    echo -e "${GREEN}✅ Todos los iconos tienen prefijo correcto${NC}"
else
    echo -e "${RED}❌ Se encontraron $invalid_icons iconos sin prefijo${NC}"
    grep -r "->icon('" app/ --include="*.php" | grep -v "heroicon-" | head -5
fi

# Verificar archivos modificados recientemente
echo -n "Verificando archivos modificados... "
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    modified_files=$(git diff --name-only HEAD~1 | grep "\.php$" | xargs grep -l "icon(" 2>/dev/null)
    if [ -n "$modified_files" ]; then
        echo -e "${YELLOW}⚠️  Archivos con iconos modificados:${NC}"
        echo "$modified_files"
    else
        echo -e "${GREEN}✅ No hay modificaciones en iconos${NC}"
    fi
else
    echo -e "${GREEN}✅ Sin cambios pendientes${NC}"
fi

# Limpiar caché si hay cambios
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo -n "Actualizando caché de iconos... "
    ./vendor/bin/sail artisan icons:cache > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Caché actualizado${NC}"
    else
        echo -e "${RED}❌ Error actualizando caché${NC}"
    fi
fi

echo "🏁 Validación completada"
```

### 2. Hook de Git Pre-Commit
```bash
# .git/hooks/pre-commit
#!/bin/bash

# Ejecutar validación de iconos
if [ -f "./validate-icons.sh" ]; then
    ./validate-icons.sh
    if [ $? -ne 0 ]; then
        echo "❌ La validación de iconos falló. Corrige los problemas antes de continuar."
        exit 1
    fi
fi

exit 0
```

### 3. Comando de Tinker para Debug
```php
// debugger-icons.php - Para usar en artisan tinker

function checkIcon($iconName) {
    $exists = \BladeUI\Icons\IconsManifest::get('heroicon')->has($iconName);
    $svg = $exists ? \BladeUI\Icons\IconsManifest::get('heroicon')->get($iconName) : null;

    echo "Icon: {$iconName}\n";
    echo "Exists: " . ($exists ? "✅" : "❌") . "\n";

    if ($svg) {
        echo "SVG Preview (first 100 chars): " . substr($svg, 0, 100) . "...\n";
    }

    return $exists;
}

// Uso:
// >>> checkIcon('heroicon-o-home')
// >>> checkIcon('heroicon-o-icono-inexistente')
```

---

## 🚨 Proceso de Reporte de Errores

### 1. Al Detectar un Error de Icono

#### Paso 1: Documentación Inmediata
```markdown
## Error de Icono - [Fecha]
**Archivo**: /ruta/al/archivo.php:123
**Icono problemático**: 'heroicon-o-nombre-incorrecto'
**Mensaje de error**: [Descripción completa del error]
**Contexto**: [Qué se estaba haciendo cuando ocurrió]
**Browser/Device**: [Información del entorno]
```

#### Paso 2: Captura de Evidencia
- **Screenshot** del error visible
- **HTML** del elemento (inspeccionar elemento → copiar HTML externo)
- **Consola** de JavaScript (si hay errores relacionados)

#### Paso 3: Diagnóstico Rápido
```bash
# Verificar si el icono existe
./vendor/bin/sail artisan icons:list | grep nombre-del-icono

# Probar con un icono conocido
# Reemplazar temporalmente el icono problemático con 'heroicon-o-home'
```

### 2. Plantilla de Reporte para Slack/GitHub
```markdown
🐛 **Error de Icono SVG**
**Archivo**: `app/.../Archivo.php:123`
**Icono**: `heroicon-o-nombre-problematico`
**Severidad**: [Alta/Media/Baja]
**Pasos para reproducir**:
1.
2.
3.
**Comportamiento esperado**: [Descripción]
**Comportamiento actual**: [Descripción]
**Screenshot**: [Adjuntar]
```

### 3. Flujo de Solución
1. **Asignar** al desarrollador responsable
2. **Validar** el problema localmente
3. **Corregir** la referencia del icono
4. **Probar** con diferentes navegadores
5. **Limpiar caché** y verificar
6. **Documentar** si es un nuevo patrón de error
7. **Hacer commit** con mensaje descriptivo

---

## ✅ Mejores Prácticas de Desarrollo

### 1. Durante el Desarrollo

#### Selección de Iconos
```php
// ✅ BUENA PRÁCTICA
->icon('heroicon-o-user')          // Siempre verificar primero
->icon('heroicon-s-lock-closed')  // Usar consistencia en estilos
->icon('heroicon-m-x-mark')        // Mini para espacios reducidos

// ❌ EVITAR
->icon('user')                     // Sin prefijo
->icon('heroicon-user')            // Prefijo incompleto
->icon('o-user')                   // Prefijo incorrecto
```

#### Consistencia Visual
```php
// ✅ Mantener consistencia en acciones similares
Action::make('edit')->icon('heroicon-o-pencil')
Action::make('update')->icon('heroicon-o-pencil')
Action::make('modify')->icon('heroicon-o-pencil')

// ✅ Usar estilos apropiados para el contexto
// Outline para acciones secundarias
->icon('heroicon-o-eye')           // Ver detalles
->icon('heroicon-o-pencil')        // Editar

// Solid para acciones principales
->icon('heroicon-s-plus')          // Agregar nuevo
->icon('heroicon-s-trash')         // Eliminar (crítico)
```

### 2. Para Nuevo Desarrollo

#### Antes de Usar un Icono
1. **Consultar**: Visitar https://heroicons.com/
2. **Verificar**: `./vendor/bin/sail artisan icons:list | grep nombre`
3. **Probar**: Implementar en un archivo de prueba primero
4. **Validar**: Probar en diferentes tamaños y navegadores

#### Documentación de Iconos Nuevos
```php
/**
 * @var string $navigationIcon
 * Descripción: Icono para navegación principal
 * Heroicon: outline/home
 * Contexto: Página principal del dashboard
 * Alternativas: heroicon-o-rectangle-group
 */
protected static ?string $navigationIcon = 'heroicon-o-home';
```

### 3. Durante Code Review

#### Checklist de Revisión
- [ ] ¿Todos los iconos usan el prefijo correcto (`heroicon-`)?
- [ ] ¿Los iconos existen en la versión actual de Heroicons?
- [ ] ¿La consistencia de estilos es apropiada (o/s/m)?
- [ ] ¿Se limpió el caché de iconos después de cambios?
- [ ] ¿Los iconos son descriptivos y no ambiguos?
- [ ] ¿Se probaron en diferentes navegadores?

#### Comentarios de Review Constructivos
```markdown
**Sugerencia**: Considerar usar 'heroicon-o-user-group' en lugar de 'heroicon-o-users'
para mejor consistencia con otros recursos del sistema.

**Observación**: El icono 'heroicon-o-x-circle' podría ser confuso en este contexto.
¿Consideramos 'heroicon-o-x-mark'?

**Requerimiento**: Agregar `./vendor/bin/sail artisan icons:cache` al script de despliegue.
```

---

## 📝 Checklist de Revisión

### Pre-Development
- [ ] He verificado la documentación oficial de Heroicons
- [ ] He confirmado que el icono existe en nuestra versión
- [ ] He probado el icono en un ambiente aislado
- [ ] He considerado alternativas si el icono no está disponible

### During Development
- [ ] Uso siempre el prefijo completo: `heroicon-{style}-{name}`
- [ ] Mantengo consistencia en el estilo (o/s/m)
- [ ] Elijo iconos descriptivos y no ambiguos
- [ ] Pruebo la visualización en diferentes tamaños

### Pre-Commit
- [ ] Ejecuto `./vendor/bin/sail artisan icons:cache`
- [ ] Verifico que no haya referencias rotas
- [ ] Limpio caché de vistas si es necesario
- [ ] Documento iconos nuevos o cambios significativos

### Post-Deployment
- [ ] Verifico que los iconos se muestran correctamente en producción
- [ ] Pruebo en diferentes navegadores y dispositivos
- [ ] Documento problemas encontrados y soluciones
- [ ] Actualizo la documentación del equipo si es necesario

---

## 📚 Recursos y Referencias

### Documentación Oficial
- **Heroicons v2**: https://heroicons.com/
- **Blade Icons**: https://github.com/blade-ui-kit/blade-icons
- **Filament Icons**: https://filamentphp.com/docs/3.x/icons/installation

### Herramientas Útiles
```bash
# Lista completa de iconos disponibles
./vendor/bin/sail artisan icons:list

# Verificar si un icono específico existe
./vendor/bin/sail artisan tinker
>>> heroicon('heroicon-o-home')->exists()

# Obtener el SVG de un icono
./vendor/bin/sail artisan tinker
>>> heroicon('heroicon-o-home')->toSvg()
```

### Atajos y Comandos
```bash
# Limpiar caché relacionado con iconos
./vendor/bin/sail artisan icons:clear && ./vendor/bin/sail artisan icons:cache

# Verificar estado de iconos
./vendor/bin/sail artisan icons:cache --show-path

# Debug de iconos (mostrar ruta y tamaño)
./vendor/bin/sail artisan icons:cache --debug
```

### Configuración de Desarrollo
```json
// .vscode/settings.json - Para VS Code
{
    "emmet.includeLanguages": {
        "blade": "html"
    },
    "files.associations": {
        "*.blade.php": "blade"
    },
    "editor.quickSuggestions": {
        "strings": true
    }
}
```

---

## 🚀 Comandos de Emergencia

### Si los iconos dejan de funcionar completamente
```bash
# Secuencia de emergencia completa
./vendor/bin/sail artisan down
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan icons:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan icons:cache
./vendor/bin/sail artisan filament:clear-cached-components
./vendor/bin/sail artisan filament:assets
./vendor/bin/sail artisan optimize
./vendor/bin/sail artisan up
```

### Para diagnóstico rápido en producción
```bash
# Verificar que los servicios de iconos funcionen
php artisan icons:list | head -5

# Probar un icono conocido
php artisan tinker --execute="echo heroicon('heroicon-o-home')->exists() ? 'ICONOS OK' : 'ERROR ICONOS'"
```

---

## 📞 Contacto y Soporte

### Para Problemas de Iconos
1. **Documentar** el error con screenshots y pasos
2. **Consultar** esta guía primero
3. **Buscar** en issues similares del repositorio
4. **Contactar** al equipo de desarrollo con la información completa

### Actualización de Esta Guía
Esta guía debe actualizarse cuando:
- Se detecten nuevos patrones de error
- Se introduzcan nuevas dependencias de iconos
- Cambie la convención de nomenclatura
- Se encuentren mejores herramientas de diagnóstico

---

**Versión**: 1.0
**Última Actualización**: 27 de noviembre de 2025
**Mantenido por**: Equipo de Desarrollo Emporio Digital
**Aprobado por**: [Nombre del Líder Técnico]

---

*Este documento es parte del conjunto de guías de desarrollo del proyecto Emporio Digital. Su cumplimiento es obligatorio para todo el equipo de desarrollo.*