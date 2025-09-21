<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Jacarei',
            'email' => 'admin@jacarei.com',
            'password' => Hash::make('jacarei@1542536'),
            'email_verified_at' => now(),
            'sed_diretoria_id' => '20207',
            'sed_municipio_id' => '9267',
            'sed_username' => 'SME392',
            'sed_password' => 'zw28frb32x',
        ]);
    }
}
