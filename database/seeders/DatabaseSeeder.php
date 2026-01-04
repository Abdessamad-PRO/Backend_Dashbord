<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Manager
        User::firstOrCreate(
            ['email' => 'yasserbeloukid@gmail.com'],
            [
                'name' => 'yasser',
                'password' => bcrypt('123456789'),
                'role' => 'manager'
            ]
        );

        // Admin
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
                'role' => 'admin'
            ]
        );

        // Employé
        User::firstOrCreate(
            ['email' => 'employe@gmail.com'],
            [
                'name' => 'Employé',
                'password' => bcrypt('employe123'),
                'role' => 'employe'
            ]
        );
    }
}
