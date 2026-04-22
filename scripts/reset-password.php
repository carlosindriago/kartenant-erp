<?php

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'admin@kartenant.test';
$newPassword = $argv[2] ?? bin2hex(random_bytes(8));

echo "=== Reset Password para Superadmin ===\n\n";

$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "❌ Usuario no encontrado: {$email}\n";
    exit(1);
}

if (!$user->is_super_admin) {
    echo "❌ El usuario no es superadmin\n";
    exit(1);
}

$user->forceFill([
    'password' => bcrypt($newPassword),
    'force_renew_password' => true,
    'last_password_change_at' => null,
])->save();

echo "✅ Contraseña actualizada exitosamente\n\n";
echo "Email: {$user->email}\n";
echo "Nueva Password: {$newPassword}\n";
echo "Force Renew: true\n\n";
echo "Ahora puedes acceder a:\n";
echo "https://kartenant.test/admin/login\n\n";
echo "El sistema te redirigirá a:\n";
echo "https://kartenant.test/admin/cambiar-contrasena\n";
