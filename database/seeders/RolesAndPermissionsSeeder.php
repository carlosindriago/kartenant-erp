<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use DefaultRolesSeeder instead.
 * This seeder is kept for backward compatibility and delegates to DefaultRolesSeeder.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Delegate to the comprehensive DefaultRolesSeeder
        // which creates 38 permissions and 5 predefined roles with proper permissions
        $this->call(DefaultRolesSeeder::class);
    }
}
