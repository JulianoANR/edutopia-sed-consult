<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuários por role para cada conexão (tenant)
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $slug = strtolower(str_replace([' ', 'á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'], ['','a','a','a','a','e','e','i','o','o','o','u','c'], $tenant->name));

            // Admin
            User::updateOrCreate(
                ['email' => $slug.'@admin.com'],
                [
                    'name' => $tenant->name.' Admin',
                    'password' => Hash::make($slug.'@2025'),
                    'email_verified_at' => now(),
                    'role' => 'admin',
                    'tenant_id' => $tenant->id,
                ]
            );

            // Gestor
            User::updateOrCreate(
                ['email' => $slug.'@gestor.com'],
                [
                    'name' => $tenant->name.' Gestor',
                    'password' => Hash::make($slug.'gestor@2025'),
                    'email_verified_at' => now(),
                    'role' => 'gestor',
                    'tenant_id' => $tenant->id,
                ]
            );

            // jacarei@professor.com
            // jacareiprofessor@2025
            // Professor
            User::updateOrCreate(
                ['email' => $slug.'@professor.com'],
                [
                    'name' => $tenant->name.' Professor',
                    'password' => Hash::make($slug.'professor@2025'),
                    'email_verified_at' => now(),
                    'role' => 'professor',
                    'tenant_id' => $tenant->id,
                ]
            );
        }
    }
}
