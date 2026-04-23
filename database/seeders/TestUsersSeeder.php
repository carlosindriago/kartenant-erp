<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $cajero = User::create([
            'name' => 'Cajero de Prueba',
            'email' => 'cajero@test.com',
            'password' => bcrypt('password'),
        ]);

        $cajeroRole = Role::findByName('cajero');
        $cajero->assignRole($cajeroRole);
    }
}
