<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

class InstallCommand extends Command
{
    protected $signature = 'kartenant:install 
                            {--db-host=localhost : Database host}
                            {--db-port=5432 : Database port}
                            {--db-database= : Database name}
                            {--db-username= : Database username}
                            {--db-password= : Database password}
                            {--admin-name= : Admin name}
                            {--admin-email= : Admin email}
                            {--admin-password= : Admin password}
                            {--app-name=Kartenant : Application name}
                            {--app-url= : Application URL}
                            {--force : Force installation even if already installed}';

    protected $description = 'Install Kartenant SaaS platform via CLI';

    public function handle()
    {
        $this->info('🚀 Kartenant Installation Wizard');
        $this->newLine();

        // Check if already installed
        if (!$this->option('force') && ($this->isInstalled() || $this->hasSuperAdmin())) {
            $this->error('❌ Sistema ya instalado. Use --force para reinstalar.');
            return 1;
        }

        try {
            // Collect configuration
            $config = $this->collectConfiguration();
            
            // Validate configuration
            $this->validateConfiguration($config);
            
            // Generate .env file
            $this->info('📝 Generando archivo de configuración...');
            $this->generateEnvFile($config);
            
            // Test database connection
            $this->info('🔌 Probando conexión a la base de datos...');
            $this->testDatabaseConnection($config);
            
            // Run migrations
            $this->info('🗄️  Ejecutando migraciones...');
            Artisan::call('migrate:fresh', ['--force' => true]);
            
            // Seed permissions
            $this->info('🔐 Configurando permisos...');
            Artisan::call('db:seed', ['--class' => 'LandlordAdminSeeder', '--force' => true]);
            
            // Create superadmin
            $this->info('👤 Creando cuenta de administrador...');
            $this->createSuperAdmin($config);
            
            // Create installation lock
            $this->info('🔒 Finalizando instalación...');
            $this->createInstallationLock();
            
            $this->newLine();
            $this->info('✅ ¡Instalación completada exitosamente!');
            $this->newLine();
            $this->line('📋 <info>Detalles de acceso:</info>');
            $this->line("   URL: <comment>{$config['app_url']}/admin</comment>");
            $this->line("   Email: <comment>{$config['admin_email']}</comment>");
            $this->line("   Contraseña: <comment>[la que configuraste]</comment>");
            $this->newLine();
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("❌ Error durante la instalación: {$e->getMessage()}");
            return 1;
        }
    }

    private function collectConfiguration(): array
    {
        $config = [];

        // Database configuration
        $config['db_host'] = $this->option('db-host') ?: $this->ask('Host de la base de datos', 'localhost');
        $config['db_port'] = $this->option('db-port') ?: $this->ask('Puerto de la base de datos', '5432');
        $config['db_database'] = $this->option('db-database') ?: $this->ask('Nombre de la base de datos');
        $config['db_username'] = $this->option('db-username') ?: $this->ask('Usuario de la base de datos');
        $config['db_password'] = $this->option('db-password') ?: $this->secret('Contraseña de la base de datos (opcional)');

        // Admin configuration
        $config['admin_name'] = $this->option('admin-name') ?: $this->ask('Nombre del administrador');
        $config['admin_email'] = $this->option('admin-email') ?: $this->ask('Email del administrador');
        
        if (!$this->option('admin-password')) {
            do {
                $password = $this->secret('Contraseña del administrador (mín. 12 caracteres)');
                if (strlen($password) < 12) {
                    $this->error('La contraseña debe tener al menos 12 caracteres.');
                }
            } while (strlen($password) < 12);
            $config['admin_password'] = $password;
        } else {
            $config['admin_password'] = $this->option('admin-password');
        }

        // App configuration
        $config['app_name'] = $this->option('app-name');
        $config['app_url'] = $this->option('app-url') ?: $this->ask('URL de la aplicación', 'http://localhost');

        return $config;
    }

    private function validateConfiguration(array $config): void
    {
        $required = ['db_database', 'db_username', 'admin_name', 'admin_email', 'admin_password'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new Exception("Campo requerido faltante: {$field}");
            }
        }

        if (!filter_var($config['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email de administrador inválido.');
        }

        if (strlen($config['admin_password']) < 12) {
            throw new Exception('La contraseña debe tener al menos 12 caracteres.');
        }
    }

    private function generateEnvFile(array $config): void
    {
        $envContent = "APP_NAME=\"{$config['app_name']}\"
APP_ENV=production
APP_KEY=" . config('app.key') . "
APP_DEBUG=false
APP_TIMEZONE=America/Mexico_City
APP_URL={$config['app_url']}

DB_CONNECTION=pgsql
DB_HOST={$config['db_host']}
DB_PORT={$config['db_port']}
DB_DATABASE={$config['db_database']}
DB_USERNAME={$config['db_username']}
DB_PASSWORD={$config['db_password']}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=\"noreply@{$config['app_url']}\"
MAIL_FROM_NAME=\"{$config['app_name']}\"

LANDLORD_DB_CONNECTION=pgsql
LANDLORD_DB_HOST={$config['db_host']}
LANDLORD_DB_PORT={$config['db_port']}
LANDLORD_DB_DATABASE={$config['db_database']}
LANDLORD_DB_USERNAME={$config['db_username']}
LANDLORD_DB_PASSWORD={$config['db_password']}";

        File::put(base_path('.env'), $envContent);
    }

    private function testDatabaseConnection(array $config): void
    {
        $connection = new \PDO(
            "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_database']}",
            $config['db_username'],
            $config['db_password']
        );
    }

    private function createSuperAdmin(array $config): void
    {
        User::create([
            'name' => $config['admin_name'],
            'email' => $config['admin_email'],
            'password' => Hash::make($config['admin_password']),
            'email_verified_at' => now(),
            'is_super_admin' => true,
            'must_change_password' => false,
        ]);
    }

    private function createInstallationLock(): void
    {
        File::put(base_path('.installed'), json_encode([
            'installed_at' => now()->toISOString(),
            'version' => '1.0.0',
            'method' => 'cli'
        ]));
    }

    private function isInstalled(): bool
    {
        return File::exists(base_path('.installed'));
    }

    private function hasSuperAdmin(): bool
    {
        try {
            return User::where('is_super_admin', true)->exists();
        } catch (Exception $e) {
            return false;
        }
    }
}
