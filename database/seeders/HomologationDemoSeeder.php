<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Discipline;
use App\Models\TeacherClassDisciplineLink;
use Illuminate\Support\Facades\Hash;

class HomologationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disciplinas de exemplo
        $disciplines = [
            ['name' => 'Português', 'code' => 'POR'],
            ['name' => 'Matemática', 'code' => 'MAT'],
            ['name' => 'Ciências', 'code' => 'CIE'],
            ['name' => 'História', 'code' => 'HIS'],
            ['name' => 'Geografia', 'code' => 'GEO'],
            ['name' => 'Inglês', 'code' => 'ING'],
            ['name' => 'Educação Física', 'code' => 'EDF'],
            ['name' => 'Artes', 'code' => 'ART'],
        ];

        $disciplineRecords = [];
        foreach ($disciplines as $d) {
            $disciplineRecords[$d['code']] = Discipline::updateOrCreate(
                ['name' => $d['name']],
                ['code' => $d['code']]
            );
        }

        // Professores de exemplo
        $profAna = User::updateOrCreate(
            ['email' => 'ana.prof@edutopia.com'],
            [
                'name' => 'Prof. Ana',
                'password' => Hash::make('profana@2025'),
                'email_verified_at' => now(),
                'role' => 'professor',
                // Campos SED (opcionais para homologação)
                'tenant_id' => \App\Models\Tenant::query()->value('id'),
            ]
        );

        $profBruno = User::updateOrCreate(
            ['email' => 'bruno.prof@edutopia.com'],
            [
                'name' => 'Prof. Bruno',
                'password' => Hash::make('profbruno@2025'),
                'email_verified_at' => now(),
                'role' => 'professor',
                'tenant_id' => \App\Models\Tenant::query()->value('id'),
            ]
        );

        // Turmas de exemplo
        $class2928 = '292814696'; // Turma já usada nos testes
        $classDemo = '123456789'; // Turma fictícia para homologação

        // Vínculos professor-turma-disciplina
        TeacherClassDisciplineLink::updateOrCreate(
            ['user_id' => $profAna->id, 'class_code' => $class2928, 'discipline_id' => $disciplineRecords['POR']->id],
            ['full_access' => false]
        );

        TeacherClassDisciplineLink::updateOrCreate(
            ['user_id' => $profBruno->id, 'class_code' => $class2928, 'discipline_id' => $disciplineRecords['MAT']->id],
            ['full_access' => false]
        );

        // Exemplo de acesso total à turma (sem disciplina específica)
        TeacherClassDisciplineLink::updateOrCreate(
            ['user_id' => $profBruno->id, 'class_code' => $classDemo, 'discipline_id' => null],
            ['full_access' => true]
        );
    }
}