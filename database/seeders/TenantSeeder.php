<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Jacarei
        Tenant::updateOrCreate(
            ['name' => 'Jacarei'],
            [
                'diretoria_id' => 20207,
                'municipio_id' => 9267,
                'rede_ensino_cod' => 2,
                'sed_username' => 'SME392',
                'sed_password_encrypted' => encrypt('zw28frb32x'),
                'status' => 'active',
                'last_validated_at' => null,
            ]
        );

        // Paraibuna
        Tenant::updateOrCreate(
            ['name' => 'Paraibuna'],
            [
                'diretoria_id' => 20206,
                'municipio_id' => 9448,
                'rede_ensino_cod' => 2,
                'sed_username' => 'SME504',
                'sed_password_encrypted' => encrypt('a4i3rx86'),
                'status' => 'active',
                'last_validated_at' => null,
            ]
        );
    }
}