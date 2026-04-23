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

use App\Models\User;
use Illuminate\Console\Command;

class ViewUserCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:show-code {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar el código 2FA actual de un usuario por su email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ Usuario no encontrado: {$email}");

            return 1;
        }

        if (! $user->email_2fa_code) {
            $this->warn('⚠️  El usuario no tiene un código activo.');
            $this->info('Intenta iniciar sesión primero para generar un código.');

            return 0;
        }

        if ($user->email_2fa_expires_at && $user->email_2fa_expires_at->isPast()) {
            $this->error('❌ El código ha expirado.');
            $this->info("Expiraba: {$user->email_2fa_expires_at->format('d/m/Y H:i:s')}");

            return 0;
        }

        $this->newLine();
        $this->info("✅ Usuario: {$user->name} ({$user->email})");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔑 Código 2FA: '.$user->email_2fa_code);
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($user->email_2fa_expires_at) {
            $this->comment("⏰ Expira: {$user->email_2fa_expires_at->format('d/m/Y H:i:s')} ({$user->email_2fa_expires_at->diffForHumans()})");
        }

        $this->newLine();

        return 0;
    }
}
