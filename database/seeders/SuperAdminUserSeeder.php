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
        User::updateOrCreate(
            ['email' => 'superadmin@edutopia.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin@2025'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'tenant_id' => Tenant::first()->id,
            ]
        );
    }
}