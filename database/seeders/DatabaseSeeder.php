<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ordem: Tenants -> SuperAdmin -> Users por role
        $this->call(TenantSeeder::class);
        $this->call(SuperAdminUserSeeder::class);
        $this->call(AdminUserSeeder::class);
    }
}
