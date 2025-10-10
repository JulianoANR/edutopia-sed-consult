<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfessorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'professor@edutopia.com'],
            [
                'name' => 'Professor Demo',
                'password' => Hash::make('professor@2025'),
                'email_verified_at' => now(),
                'role' => 'professor',
                // Optional SED fields for demo; adjust as needed
                'sed_diretoria_id' => '20206',
                'sed_municipio_id' => '9448',
                'sed_username' => 'PROF001',
                'sed_password' => 'demo-pass',
            ]
        );
    }
}