<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Discipline;
use App\Models\TeacherClassDisciplineLink;
use App\Models\AttendanceRecord;
use App\Services\SedTurmasService;
use App\Helpers\TipoEnsinoHelper;

class UnifiedTenantSeedCommand extends Command
{
    protected $signature = 'seed:unified {tenant=Jacarei} {--school=447523} {--school-name="ADELIA MONTEIRO PROFA EMEF"} {--classes=292814696,292815289,295752562} {--days=8}';
    protected $description = 'Unifica seeders: cria disciplinas, professores, vínculos e frequências para um tenant específico.';

    public function handle(): int
    {
        $tenantName = (string) $this->argument('tenant');
        $schoolCodeOpt = (string) $this->option('school');
        $schoolName = (string) $this->option('school-name');
        $classesOpt = (string) $this->option('classes');
        $days = (int) $this->option('days');

        $schoolCode = preg_replace('/[^0-9]/', '', $schoolCodeOpt) ?: $schoolCodeOpt;
        $classesCodes = array_values(array_filter(array_map('trim', explode(',', $classesOpt))));

        $tenant = Tenant::where('name', $tenantName)->first();
        if (!$tenant) {
            $this->error('Tenant não encontrado: ' . $tenantName . '. Execute TenantSeeder primeiro.');
            return self::FAILURE;
        }
        $tenantId = $tenant->id;
        $slugTenant = $this->slugify($tenantName);

        // Tentar setar um usuário autenticado para habilitar SED via credentials do tenant
        $gestorEmailSlug = $slugTenant.'@gestor.com';
        $gestorEmailRaw = $tenantName.'@gestor.com';
        $gestor = User::where('email', $gestorEmailSlug)->orWhere('email', $gestorEmailRaw)->first();
        $admin = User::where('email', $slugTenant.'@admin.com')->orWhere('email', $tenantName.'@admin.com')->first();
        $authUser = $gestor ?: $admin ?: User::where('tenant_id', $tenantId)->first();
        if ($authUser) {
            Auth::setUser($authUser);
        }

        // Criar disciplinas base para o tenant
        $defaultDisciplines = [
            ['name' => 'Português', 'code' => 'LP'],
            ['name' => 'Matemática', 'code' => 'MAT'],
            ['name' => 'Ciências', 'code' => 'CIE'],
            ['name' => 'História', 'code' => 'HIS'],
            ['name' => 'Geografia', 'code' => 'GEO'],
            ['name' => 'Artes', 'code' => 'ART'],
            ['name' => 'Educação Física', 'code' => 'EF'],
        ];

        $disciplines = [];
        foreach ($defaultDisciplines as $d) {
            $disciplines[] = Discipline::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $d['name']],
                ['code' => $d['code']]
            );
        }
        $this->info('Disciplinas garantidas para tenant '.$tenantName.': '.count($disciplines));

        // Instanciar SedTurmasService sob guarda; se falhar, usar fallback
        $sedTurmas = null;
        $canUseSed = !empty(config('sed.api.url'))
            && (Auth::user()?->tenant?->sed_username || !empty(config('sed.api.username')))
            && (Auth::user()?->tenant?->sed_password_encrypted || !empty(config('sed.api.password')))
            && (!empty(Auth::user()?->tenant?->diretoria_id) || !empty(config('sed.api.diretoria_id')))
            && (!empty(Auth::user()?->tenant?->municipio_id) || !empty(config('sed.api.municipio_id')))
            && !empty(config('sed.api.rede_ensino_cod'));
        if ($canUseSed) {
            try {
                $sedTurmas = app(SedTurmasService::class);
                $this->info('SED disponível: consultas de turmas serão realizadas.');
            } catch (\Throwable $e) {
                $this->warn('SED indisponível: '.$e->getMessage().' — usando nomes/tipos padrão.');
                $sedTurmas = null;
            }
        } else {
            $this->warn('Config SED ausente/incompleta. Usando fallback para nomes/tipos.');
        }

        // Criar professores por disciplina e vincular às turmas
        $demoMappingNames = [
            '292814696' => '1° ANO A - MANHA',
            '292815289' => '1° ANO B - MANHA',
            '295752562' => '2° NIVEL H - TARDE',
        ];

        $totalLinks = 0;
        $totalRecords = 0;

        foreach ($disciplines as $disc) {
            $slugDisc = $this->slugify($disc->name);
            $email = $slugDisc.'-'.$slugTenant.'@professor.com';

            $prof = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $disc->name.' - '.$tenantName.' Professor',
                    'password' => Hash::make($slugTenant.'professor@2025'),
                    'email_verified_at' => now(),
                    'tenant_id' => $tenantId,
                ]
            );
            // Garantir role via tabela user_roles
            $prof->roleLinks()->updateOrCreate(['role' => 'professor'], []);

            foreach ($classesCodes as $classCode) {
                $classInfo = null;
                $className = $demoMappingNames[$classCode] ?? null;
                $schoolYear = date('Y');
                $classSchoolCode = $schoolCode;
                $classTipoEnsinoCod = null;

                if ($sedTurmas) {
                    try {
                        $classInfo = $sedTurmas->consultarTurma($classCode);
                        $className = $classInfo['nome_turma'] ?? $className;
                        $schoolYear = $classInfo['outAnoLetivo'] ?? $schoolYear;
                        $classSchoolCode = $classInfo['outCodEscola'] ?? $classSchoolCode;
                        $classTipoEnsinoCod = $classInfo['outCodTipoEnsino'] ?? null;
                    } catch (\Throwable $e) {
                        // mantém fallback
                    }
                }

                TeacherClassDisciplineLink::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'user_id' => $prof->id,
                        'class_code' => $classCode,
                        'discipline_id' => $disc->id,
                    ],
                    [
                        'full_access' => false,
                        'school_code' => $classSchoolCode,
                        'class_name' => $className,
                        'school_year' => $schoolYear,
                    ]
                );
                $totalLinks++;

                // Gerar frequências
                AttendanceRecord::where('tenant_id', $tenantId)
                    ->where('note', 'unified-seed')
                    ->where('class_code', $classCode)
                    ->where('discipline_id', $disc->id)
                    ->delete();

                $students = $classInfo['outAlunos'] ?? [];
                if (empty($students)) {
                    $students = $this->fakeStudents(25);
                }

                $dates = $this->recentSchoolDays($days);
                $inserts = [];
                foreach ($dates as $date) {
                    foreach ($students as $student) {
                        $ra = is_array($student)
                            ? (isset($student['outNumRA'])
                                ? ($student['outNumRA'].'-'.($student['outDigitoRA'] ?? ''))
                                : ($student['ra'] ?? null))
                            : null;
                        if (!$ra) continue;
                        $status = $this->pickWeighted(['present' => 85, 'absent' => 10, 'justified' => 5]);
                        $inserts[] = [
                            'tenant_id' => $tenantId,
                            'user_id' => $prof->id,
                            'class_code' => $classCode,
                            'class_name' => $className,
                            'school_code' => $classSchoolCode,
                            'school_name' => $schoolName,
                            'type_ensino' => $classTipoEnsinoCod ? (TipoEnsinoHelper::getDescricaoTipoEnsino($classTipoEnsinoCod) ?? null) : null,
                            'date' => $date->format('Y-m-d'),
                            'student_ra' => (string) $ra,
                            'discipline_id' => $disc->id,
                            'status' => $status,
                            'note' => 'unified-seed',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($inserts)) {
                    AttendanceRecord::upsert(
                        $inserts,
                        ['tenant_id','class_code','date','student_ra','discipline_id'],
                        ['status','note','user_id','school_code','school_name','class_name','type_ensino','updated_at']
                    );
                    $totalRecords += count($inserts);
                }
            }
        }

        $this->info("Vínculos criados/atualizados: {$totalLinks}");
        $this->info("Registros de frequência inseridos/atualizados: {$totalRecords}");
        $this->info('Seed unificado concluído com sucesso para tenant '.$tenantName.' (escola '.$schoolName.' - '.$schoolCode.').');

        return self::SUCCESS;
    }

    private function slugify(string $s): string
    {
        $s = strtolower(str_replace([' ', 'á','à','ã','â','é','ê','í','ó','ô','õ','ú','ç'], ['','a','a','a','a','e','e','i','o','o','o','u','c'], $s));
        return preg_replace('/[^a-z0-9]+/', '', $s);
    }

    private function recentSchoolDays(int $n): array
    {
        $days = [];
        $date = Carbon::today();
        while (count($days) < $n) {
            if (!in_array($date->dayOfWeekIso, [6, 7])) {
                $days[] = $date->copy();
            }
            $date->subDay();
        }
        return array_reverse($days);
    }

    private function pickWeighted(array $weights): string
    {
        $sum = array_sum($weights);
        $r = mt_rand(1, $sum);
        $acc = 0;
        foreach ($weights as $key => $w) {
            $acc += $w;
            if ($r <= $acc) return $key;
        }
        return array_key_first($weights);
    }

    private function fakeStudents(int $count): array
    {
        $students = [];
        for ($i = 0; $i < $count; $i++) {
            $students[] = ['ra' => 'RA' . str_pad((string) $i, 6, '0', STR_PAD_LEFT) . '-0'];
        }
        return $students;
    }
}