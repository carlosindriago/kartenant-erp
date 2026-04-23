<?php

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Verificando Superadmins ===\n\n";

$superadmins = \App\Models\User::where('is_super_admin', true)->get(['id', 'name', 'email', 'force_renew_password', 'last_password_change_at']);

if ($superadmins->isEmpty()) {
    echo "❌ No hay superadmins en el sistema.\n";
    echo "✅ Puedes crear uno con: php artisan kartenant:make-superadmin\n";
} else {
    echo '✅ Superadmins encontrados: '.$superadmins->count()."\n\n";
    foreach ($superadmins as $user) {
        echo "ID: {$user->id}\n";
        echo "Nombre: {$user->name}\n";
        echo "Email: {$user->email}\n";
        echo 'Force Renew: '.($user->force_renew_password ? 'true' : 'false')."\n";
        echo 'Last Change: '.($user->last_password_change_at ?? 'null')."\n";
        echo str_repeat('-', 50)."\n";
    }
}
