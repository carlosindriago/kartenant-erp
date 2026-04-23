<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create or promote a Super Admin on the landlord database (conditionally registered)
// Nota: No se registra en producción salvo ALLOW_SUPERADMIN_COMMAND=true
$__allowMakeSuperadmin = ! app()->isProduction() || (bool) env('ALLOW_SUPERADMIN_COMMAND', false);
if ($__allowMakeSuperadmin) {
    Artisan::command('kartenant:make-superadmin {email?} {--name=} {--password=}', function () {
        $isProd = app()->isProduction();

        // GUARDIÁN: Ejecutar solo una vez (instalación inicial)
        if (\App\Models\User::where('is_super_admin', true)->exists()) {
            $this->error('OPERACIÓN ABORTADA: Ya existe un Super Administrador en el sistema.');
            $this->info('Este comando solo puede ejecutarse una vez durante la configuración inicial.');

            return 1;
        }

        // Confirmación adicional en producción
        if ($isProd) {
            $this->warn('ADVERTENCIA: Está en un entorno de PRODUCCIÓN. Esta acción será registrada.');
            $confirmation = $this->ask('Para continuar, escriba exactamente el nombre del entorno: '.config('app.env'));
            if ($confirmation !== config('app.env')) {
                $this->error('Confirmación incorrecta. Operación abortada.');

                return 1;
            }
        }

        $email = $this->argument('email') ?: $this->ask('Email');
        $name = $this->option('name') ?: $this->ask('Nombre', 'Super Admin');

        // Generación de contraseña segura (o validación de la proporcionada)
        $providedPassword = $this->option('password');
        $generated = false;
        if (! $providedPassword) {
            $providedPassword = bin2hex(random_bytes(16)); // 32 chars hex, ~128 bits
            $generated = true;
        } else {
            if (strlen((string) $providedPassword) < 12) {
                $this->error('La contraseña debe tener al menos 12 caracteres.');

                return 1;
            }
        }

        $execUser = function_exists('get_current_user') ? (get_current_user() ?: 'cli') : 'cli';
        $host = function_exists('gethostname') ? (gethostname() ?: php_uname('n')) : php_uname('n');

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            /** @var \App\Models\User|null $user */
            $user = \App\Models\User::query()->where('email', $email)->first();
            $isNew = false;

            if (! $user) {
                $user = new \App\Models\User;
                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt((string) $providedPassword),
                    'is_super_admin' => true,
                    'email_verified_at' => now(),
                    'force_renew_password' => true, // Siempre forzar cambio de contraseña en primer login
                ])->save();
                $isNew = true;
                $this->info("Usuario creado y promovido a Super Admin: {$email}");
            } else {
                // Permitir promover usuario existente SOLO en la ejecución inicial
                $user->forceFill([
                    'name' => $name ?: $user->name,
                    'is_super_admin' => true,
                    'force_renew_password' => true, // Forzar cambio de contraseña al promover
                    'password' => bcrypt((string) $providedPassword),
                ])->save();
                $this->info("Usuario existente promovido a Super Admin: {$email}");
            }

            // Configurar Spatie Permission en contexto landlord para este comando
            config()->set('permission.models.role', \App\Models\Landlord\Role::class);
            config()->set('permission.models.permission', \App\Models\Landlord\Permission::class);
            config()->set('permission.cache.key', 'spatie.permission.cache.landlord');
            config()->set('permission.default_guard', 'superadmin');
            // Asegurar que el guard por defecto sea "superadmin" durante esta ejecución en consola
            config()->set('auth.defaults.guard', 'superadmin');

            // Registrar/actualizar permisos en el Gate para este contexto
            app(\Spatie\Permission\PermissionRegistrar::class)
                ->registerPermissions(app(\Illuminate\Contracts\Auth\Access\Gate::class));

            // Asignar TODOS los permisos landlord al Super Admin creado/promovido (usar modelos para evitar conflicto de guard)
            $allPermModels = \App\Models\Landlord\Permission::query()->get();
            if ($allPermModels->isEmpty()) {
                $this->warn('No se encontraron permisos landlord. ¿Ejecutaste el seeder LandlordAdminSeeder?');
            } else {
                $user->syncPermissions($allPermModels);
                $this->info('Todos los permisos landlord (guard: superadmin) asignados al usuario.');
            }

            // Limpiar caché de permisos para reflejar los cambios inmediatamente
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            \Illuminate\Support\Facades\DB::commit();

            // Registro CRÍTICO de seguridad
            \Illuminate\Support\Facades\Log::critical('Comando kartenant:make-superadmin ejecutado', [
                'env' => config('app.env'),
                'executor' => $execUser,
                'host' => $host,
                'email' => $email,
                'action' => $isNew ? 'create' : 'promote',
                'generated_password' => $generated,
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Alerta inmediata por Slack si está configurado
            if (config('logging.channels.slack.url')) {
                \Illuminate\Support\Facades\Log::channel('slack')->critical('Kartenant: SUPERADMIN creado/promovido', [
                    'env' => config('app.env'),
                    'executor' => $execUser,
                    'host' => $host,
                    'email' => $email,
                    'action' => $isNew ? 'create' : 'promote',
                    'time' => now()->toDateTimeString(),
                ]);
            }

            $this->info("Listo. Ahora puedes iniciar sesión en /admin con {$email}");
            if ($generated) {
                $this->warn('Contraseña generada (MOSTRAR SOLO UNA VEZ, guárdala ahora):');
                $this->line($providedPassword);
            }
            $this->warn('IMPORTANTE: Se requerirá cambiar la contraseña en el primer inicio de sesión.');

            return 0;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->error('Error al crear/promover al Super Administrador: '.$e->getMessage());
            \Illuminate\Support\Facades\Log::critical('Fallo kartenant:make-superadmin', [
                'env' => config('app.env'),
                'executor' => $execUser ?? null,
                'host' => $host ?? null,
                'email' => $email ?? null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return 1;
        }
    })->purpose('Crear o promover un Super Admin (landlord) con is_super_admin=true');
}

// Sincronizar TODOS los permisos landlord al/los superadmin(s)
Artisan::command('kartenant:sync-superadmin-perms {email?} {--all}', function () {
    $email = $this->argument('email');
    $allFlag = (bool) $this->option('all');

    // Configurar Spatie Permission en contexto landlord
    config()->set('permission.models.role', \App\Models\Landlord\Role::class);
    config()->set('permission.models.permission', \App\Models\Landlord\Permission::class);
    config()->set('permission.cache.key', 'spatie.permission.cache.landlord');
    config()->set('permission.default_guard', 'superadmin');
    config()->set('auth.defaults.guard', 'superadmin');

    app(\Spatie\Permission\PermissionRegistrar::class)
        ->registerPermissions(app(\Illuminate\Contracts\Auth\Access\Gate::class));

    $allPerms = \App\Models\Landlord\Permission::pluck('name')->all();
    if (empty($allPerms)) {
        $this->warn('No se encontraron permisos landlord. Ejecuta el seeder LandlordAdminSeeder primero.');

        return 1;
    }

    $users = collect();
    if ($allFlag) {
        $users = \App\Models\User::where('is_super_admin', true)->get();
    } else {
        if (! $email) {
            $email = $this->ask('Email del superadmin a sincronizar');
        }
        $user = \App\Models\User::where('email', $email)->first();
        if (! $user) {
            $this->error("Usuario no encontrado: {$email}");

            return 1;
        }
        if (! $user->is_super_admin) {
            $this->warn('El usuario no es superadmin. Se requiere is_super_admin=true.');

            return 1;
        }
        $users = collect([$user]);
    }

    $count = 0;
    foreach ($users as $u) {
        $u->syncPermissions($allPerms);
        $count++;
        $this->info("Permisos sincronizados para: {$u->email}");
    }

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->info("Listo. Superadmins sincronizados: {$count}");

    return 0;
})->purpose('Sincroniza todos los permisos landlord (guard superadmin) para uno o todos los superadmins');

// Eliminar superadmin (SOLO PARA DESARROLLO/TESTING)
$__allowDeleteSuperadmin = ! app()->isProduction() || (bool) env('ALLOW_DELETE_SUPERADMIN_COMMAND', false);
if ($__allowDeleteSuperadmin) {
    Artisan::command('kartenant:delete-superadmin {email?} {--force}', function () {
        $isProd = app()->isProduction();

        // Advertencia en producción
        if ($isProd) {
            $this->error('ADVERTENCIA: Está en un entorno de PRODUCCIÓN.');
            $this->warn('Eliminar el Super Administrador es una operación CRÍTICA.');
            $confirmation = $this->ask('Para continuar, escriba exactamente: DELETE SUPERADMIN');
            if ($confirmation !== 'DELETE SUPERADMIN') {
                $this->error('Confirmación incorrecta. Operación abortada.');

                return 1;
            }
        }

        $email = $this->argument('email');
        if (! $email) {
            $email = $this->ask('Email del Super Admin a eliminar');
        }

        $user = \App\Models\User::where('email', $email)->first();

        if (! $user) {
            $this->error("Usuario no encontrado: {$email}");

            return 1;
        }

        if (! $user->is_super_admin) {
            $this->error("El usuario {$email} no es un Super Admin.");

            return 1;
        }

        // Confirmación adicional
        if (! $this->option('force')) {
            $confirm = $this->confirm("¿Está seguro de eliminar al Super Admin: {$user->name} ({$user->email})?", false);
            if (! $confirm) {
                $this->info('Operación cancelada.');

                return 0;
            }
        }

        try {
            $userName = $user->name;
            $userEmail = $user->email;

            // Eliminar usuario
            $user->delete();

            // Log de seguridad
            \Illuminate\Support\Facades\Log::critical('Super Admin eliminado', [
                'env' => config('app.env'),
                'email' => $userEmail,
                'name' => $userName,
                'timestamp' => now()->toDateTimeString(),
            ]);

            $this->info("Super Admin eliminado exitosamente: {$userName} ({$userEmail})");
            $this->warn('Ahora puede crear un nuevo Super Admin con: php artisan kartenant:make-superadmin');

            return 0;
        } catch (\Throwable $e) {
            $this->error('Error al eliminar Super Admin: '.$e->getMessage());

            return 1;
        }
    })->purpose('Eliminar un Super Admin (SOLO DESARROLLO/TESTING)');
}

// StoreSettings Management - Operation Ernesto Freedom
Artisan::command('emporio:store-settings {action : Action to perform (migrate, seed, reset)} {--tenant= : Specific tenant ID (optional)} {--force : Force execution without confirmation}', function () {
    $action = $this->argument('action');
    $tenantId = $this->option('tenant');
    $force = $this->option('force');

    // Import the command class
    $command = new \App\Console\Commands\ManageStoreSettings;
    $command->setLaravel($this->laravel);
    $command->setInput($this->input);
    $command->setOutput($this->output);

    return $command->handle();
})->purpose('Manage StoreSettings for tenants - Operation Ernesto Freedom (actions: migrate, seed, reset)');

// (La programación de tareas ahora se declara en bootstrap/app.php con ->withSchedule())
