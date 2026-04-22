<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;
use App\Models\User;

class InstallStandalone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kartenant:install-standalone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala la versión Open-Core (Standalone) uniendo bases de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando instalación Standalone (Open-Core)...');

        if (env('APP_MODE', 'saas') !== 'standalone') {
            $this->error('Error: APP_MODE no está configurado como "standalone" en el archivo .env.');
            return Command::FAILURE;
        }

        $this->info('1. Ejecutando migraciones del Sistema Central (Landlord)...');
        Artisan::call('migrate', [
            '--database' => env('DB_CONNECTION', 'pgsql'),
            '--path' => 'database/migrations/landlord',
            '--force' => true,
        ]);
        $this->info(Artisan::output());

        $this->info('2. Ejecutando migraciones Operativas (Tenant)...');
        // En standalone, tenant apunta a la misma DB configurada en config/database.php
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $this->info(Artisan::output());

        $this->info('3. Configurando Entorno Único...');
        $tenant = Tenant::firstOrCreate(
            ['id' => 1],
            ['name' => 'Ferretería Open-Core', 'domain' => 'localhost', 'database' => null]
        );

        $this->info('4. Creando Administrador Inicial...');
        $email = $this->ask('Ingresa el email del administrador', 'admin@ferreteria.local');
        $password = $this->secret('Ingresa una contraseña (mínimo 8 caracteres)');

        if (strlen($password) < 8) {
            $this->error('La contraseña es muy corta. Usando "password" temporalmente.');
            $password = 'password';
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => bcrypt($password),
                'is_super_admin' => false,
                'force_renew_password' => false, // No forzamos cambio en open-core
            ]
        );

        // Vincular usuario al tenant
        if (!$tenant->users()->where('users.id', $user->id)->exists()) {
            $tenant->users()->attach($user->id);
        }

        // Sembrar permisos y roles si existe el seeder correspondiente en el modulo (opcional)
        // Artisan::call('db:seed', ['--class' => 'MovementReasonsSeeder', '--database' => 'tenant']);

        $this->info('');
        $this->info('✅ ¡Instalación Completada!');
        $this->info('Puedes acceder al sistema operativo ingresando a la URL de tu aplicación (ej. http://localhost/app).');
        
        return Command::SUCCESS;
    }
}
