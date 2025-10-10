<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'jacarei@admin.com'],
            [
                'name' => 'Jacarei',
                'password' => Hash::make('jacarei@2025'),
                'email_verified_at' => now(),
                'sed_diretoria_id' => '20207',
                'sed_municipio_id' => '9267',
                'sed_username' => 'SME392',
                'sed_password' => 'zw28frb32x',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'paraibuna@admin.com'],
            [
                'name' => 'Paraibuna',
                'password' => Hash::make('paraibuna@2025'),
                'email_verified_at' => now(),
                'sed_diretoria_id' => '20206', // Mesma de taubate
                'sed_municipio_id' => '9448',
                'sed_username' => 'SME504',
                'sed_password' => 'a4i3rx86',
                'role' => 'admin',
            ]
        );
    }
}
