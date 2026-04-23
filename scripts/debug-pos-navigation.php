#!/usr/bin/env php
<?php

/**
 * Debug script para verificar navegación POS
 * Ejecutar: php debug-pos-navigation.php
 */

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "DEBUG: NAVEGACIÓN POS EN FILAMENT\n";
echo "========================================\n\n";

// Simular contexto de tenant
$tenant = App\Models\Tenant::find(6);

if (! $tenant) {
    echo "❌ Tenant ID 6 no encontrado\n";
    exit(1);
}

echo "Tenant: {$tenant->name} (DB: {$tenant->database})\n\n";

$tenant->execute(function () {
    // Autenticar usuario
    $user = App\Models\User::find(4);
    auth('tenant')->login($user);

    echo "Usuario: {$user->email}\n";
    echo 'Rol: '.$user->roles->pluck('name')->join(', ')."\n\n";

    echo "=== VERIFICACIÓN POS PAGE ===\n";

    // Verificar clase
    $posClass = 'App\Filament\App\Pages\POS';
    echo '1. Clase existe: '.(class_exists($posClass) ? '✓' : '✗')."\n";

    // Verificar canAccess
    $canAccess = $posClass::canAccess();
    echo '2. canAccess(): '.($canAccess ? '✓ TRUE' : '✗ FALSE')."\n";

    // Verificar propiedades de navegación
    $reflection = new ReflectionClass($posClass);
    $props = $reflection->getDefaultProperties();

    echo "3. navigationLabel: '{$props['navigationLabel']}'\n";
    echo "4. navigationGroup: '{$props['navigationGroup']}'\n";
    echo "5. navigationIcon: '{$props['navigationIcon']}'\n";
    echo "6. navigationSort: {$props['navigationSort']}\n\n";

    echo "=== VERIFICACIÓN PANEL ===\n";

    // Obtener panel
    $panel = filament()->getPanel('app');

    echo '7. Panel ID: '.$panel->getId()."\n";

    // Obtener todas las páginas registradas
    $pages = $panel->getPages();
    echo '8. Total páginas registradas: '.count($pages)."\n";

    echo "\nPáginas registradas:\n";
    foreach ($pages as $pageClass) {
        $label = method_exists($pageClass, 'getNavigationLabel') ? $pageClass::getNavigationLabel() : 'N/A';
        $group = method_exists($pageClass, 'getNavigationGroup') ? $pageClass::getNavigationGroup() : 'N/A';
        $canAccessPage = method_exists($pageClass, 'canAccess') ? ($pageClass::canAccess() ? 'SI' : 'NO') : 'N/A';

        echo "  - {$pageClass}\n";
        echo "    Label: {$label}\n";
        echo "    Group: {$group}\n";
        echo "    canAccess: {$canAccessPage}\n\n";
    }

    // Verificar si POS está en la lista
    $posRegistered = collect($pages)->contains(function ($pageClass) use ($posClass) {
        return $pageClass === $posClass;
    });

    echo '9. POS registrado en panel: '.($posRegistered ? '✓ SI' : '✗ NO')."\n\n";

    if (! $posRegistered) {
        echo "❌ PROBLEMA: La página POS no está registrada en el panel\n";
        echo "Posibles causas:\n";
        echo "  - Autodiscovery no está funcionando\n";
        echo "  - Archivo no está en el directorio correcto\n";
        echo "  - Namespace incorrecto\n";
    } else {
        echo "✓ La página POS está correctamente registrada\n";
        echo "Si no aparece en navegación, problema está en:\n";
        echo "  - Cache del navegador\n";
        echo "  - JavaScript/CSS de Filament\n";
        echo "  - Renderización del componente de navegación\n";
    }
});

echo "\n========================================\n";
echo "DEBUG COMPLETADO\n";
echo "========================================\n";
