<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'superadmin@edutopia.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin@2025'),
                'email_verified_at' => now(),
                'tenant_id' => Tenant::first()->id,
            ]
        );

        // Garantir multi-role via tabela user_roles
        $user->roleLinks()->updateOrCreate(['role' => 'super_admin'], []);
    }
}