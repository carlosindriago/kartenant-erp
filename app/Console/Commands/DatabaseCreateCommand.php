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
use Illuminate\Support\Facades\DB;

class DatabaseCreateCommand extends Command
{
    /**
     * El nombre y la firma de nuestro nuevo comando.
     * {name} es el argumento que recibirá (el nombre de la DB).
     */
    protected $signature = 'db:create {name}';

    /**
     * La descripción que aparecerá cuando listes los comandos.
     */
    protected $description = 'Create a new PostgreSQL database';

    /**
     * El método que contiene la lógica principal.
     */
    public function handle()
    {
        // Obtenemos el nombre de la base de datos del argumento del comando.
        $databaseName = $this->argument('name');

        try {
            // Usamos el Facade DB para ejecutar una sentencia SQL en crudo.
            // Esta es la instrucción directa para PostgreSQL para crear una base de datos.
            // Es importante que la conexión por defecto tenga permisos para hacer esto.
            // Con Sail y nuestra configuración, sí los tiene.
            $databaseNameStr = (string) $databaseName;
            DB::statement("CREATE DATABASE \"$databaseNameStr\"");

            // Le informamos al artesano que la herramienta funcionó.
            $this->info("Database '$databaseNameStr' created successfully!");

        } catch (\Exception $e) {
            // Si algo sale mal (ej: la DB ya existe), atrapamos el error.
            $this->error($e->getMessage());

            return 1; // Retornamos un código de error.
        }

        return 0; // Retornamos un código de éxito.
    }
}
