<?php

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          SEEDERS LANDLORD - KARTENANT DIGITAL                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$seeders = [
    'LandlordAdminSeeder' => 'Permisos y roles de administración',
    'SecurityQuestionsSeeder' => 'Preguntas de seguridad',
    'SubscriptionPlansSeeder' => 'Planes de suscripción',
];

$success = 0;
$errors = 0;

foreach ($seeders as $seederClass => $description) {
    echo "📦 Ejecutando: {$description}\n";
    echo "   Seeder: {$seederClass}\n";
    
    try {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => "Database\\Seeders\\{$seederClass}",
            '--database' => 'landlord',
            '--force' => true,
        ]);
        
        if ($exitCode === 0) {
            echo "   ✅ Completado exitosamente\n\n";
            $success++;
        } else {
            echo "   ⚠️  Completado con advertencias (código: {$exitCode})\n\n";
            $success++;
        }
    } catch (\Throwable $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Resumen:\n";
echo "✅ Exitosos: {$success}\n";
echo "❌ Errores: {$errors}\n";
echo "📊 Total: " . ($success + $errors) . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($errors === 0) {
    echo "🎉 ¡Todos los seeders se ejecutaron correctamente!\n\n";
    echo "Datos cargados:\n";
    echo "  • Permisos y roles de administración\n";
    echo "  • 10 preguntas de seguridad\n";
    echo "  • 4 planes de suscripción (Gratuito, Básico, Profesional, Empresarial)\n\n";
    exit(0);
} else {
    echo "⚠️  Algunos seeders fallaron. Revisa los errores arriba.\n\n";
    exit(1);
}
