<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use App\Models\Discipline;
use App\Models\User;
use App\Services\SedApiService;
use App\Services\SedEscolasService;
use App\Services\SedTurmasService;
use App\Services\SedAlunosService;
use App\Helpers\TipoEnsinoHelper;

class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Only run in non-production environments
        if (app()->environment('production')) {
            $this->command->warn('DemoAttendanceSeeder só deve ser executado em ambientes de teste. Abortando.');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->command->error('Nenhum usuário encontrado. Crie um usuário antes de rodar o seeder.');
            return;
        }
        $tenantId = $user->tenant_id;

        $this->command->info('Iniciando geração de dados de presença demonstrativos para tenant_id='.$tenantId);

        // Remover registros de seed anteriores marcados com note=demo-seed para evitar duplicatas
        $deletedDemo = AttendanceRecord::where('tenant_id', $tenantId)
            ->where('note', 'demo-seed')
            ->delete();
        if ($deletedDemo) {
            $this->command->info("Registros demo anteriores removidos (note=demo-seed): {$deletedDemo}");
        }

        // Try SED connectivity first, only if config is present to avoid TypeError
        $sedOnline = false;
        $canUseSed = !empty(config('sed.api.url'))
            && !empty(config('sed.api.username'))
            && !empty(config('sed.api.password'))
            && !empty(config('sed.api.diretoria_id'))
            && !empty(config('sed.api.municipio_id'))
            && !empty(config('sed.api.rede_ensino_cod'));

        if ($canUseSed) {
            try {
                $sedApi = app(SedApiService::class);
                $test = $sedApi->testConnection();
                $sedOnline = $test['success'] ?? false;
                $this->command->info('Conexão SED: '.($sedOnline ? 'OK' : 'Falhou').(isset($test['message']) ? ' - '.$test['message'] : ''));
            } catch (\Throwable $e) {
                $this->command->warn('Falha ao testar conexão SED: '.$e->getMessage());
            }
        } else {
            $this->command->warn('Config SED ausente/incompleta. Usando fallback de dados sintéticos.');
        }

        $schools = [];
        $classesBySchool = [];
        $studentsByClass = [];

        if ($sedOnline) {
            // Consult real schools from SED
            try {
                $sedEscolas = app(SedEscolasService::class);
                $sedTurmas = app(SedTurmasService::class);
                $sedAlunos = app(SedAlunosService::class);

                // Get schools by municipio & rede configured in SedApiService
                $schoolsResp = $sedEscolas->getEscolasPorMunicipio();
                $schools = data_get($schoolsResp, 'outEscolas', []);
            } catch (\Throwable $e) {
                $this->command->warn('Erro ao consultar escolas/turmas/alunos SED, caindo para fallback: '.$e->getMessage());
                $sedOnline = false;
            }

            if ($sedOnline && !empty($schools)) {
                // Limit to a small subset to keep seed fast
                $schools = array_slice($schools, 0, 2);
                foreach ($schools as $school) {
                    $codEscola = data_get($school, 'outCodEscola');
                    $classes = [];
                    try {
                        // Ajustar chamada ao serviço com assinatura correta
                        $dataClasses = $sedTurmas->getRelacaoClasses((string) date('Y'), (string) $codEscola);
                        $classes = data_get($dataClasses, 'outClasses', []);
                    } catch (\Throwable $e) {
                        $this->command->warn('Erro ao consultar turmas SED para escola '.$codEscola.': '.$e->getMessage());
                    }
                    // Limit classes per school
                    $classesBySchool[$codEscola] = array_slice($classes ?? [], 0, 3);

                    foreach ($classesBySchool[$codEscola] as $class) {
                        // Usar número da classe para consultar formação e tentar extrair alunos
                        $numClasse = (string) data_get($class, 'outNumClasse', data_get($class, 'outCodTurma'));
                        $turmaInfo = [];
                        try {
                            $turmaInfo = $sedTurmas->consultarTurma($numClasse);
                        } catch (\Throwable $e) {
                            $this->command->warn('Erro ao consultar formação da turma '.$numClasse.': '.$e->getMessage());
                            $turmaInfo = [];
                        }
                        // Extrair possíveis listas de alunos em diferentes chaves
                        $rawStudents = data_get($turmaInfo, 'outAlunos', []);
                        if (empty($rawStudents)) {
                            $rawStudents = data_get($turmaInfo, 'outAlunosClasse', []);
                        }
                        if (empty($rawStudents)) {
                            $rawStudents = data_get($turmaInfo, 'outAlunosDaClasse', []);
                        }
                        $studentsByClass[$numClasse] = collect($rawStudents)->map(function ($aluno) {
                            $ra = (string) (data_get($aluno, 'outRA') ?? data_get($aluno, 'ra'));
                            $nome = (string) (data_get($aluno, 'outNome') ?? data_get($aluno, 'nome'));
                            return [
                                'ra' => $ra,
                                'nome' => $nome,
                            ];
                        })->filter(function ($a) {
                            return !empty($a['ra']);
                        })->take(20)->values()->all();
                    }
                }
            }
        }

        // Fallback synthetic data if SED is not available or returned nothing
        if (!$sedOnline || empty($schools)) {
            $this->command->warn('Usando fallback para escolas/turmas/alunos.');
            $schools = [
                ['outCodEscola' => '100000', 'outDescNomeEscola' => 'ESCOLA DEMO CENTRAL'],
                ['outCodEscola' => '100001', 'outDescNomeEscola' => 'ESCOLA DEMO LESTE'],
            ];
            $classesBySchool = [
                '100000' => [
                    ['outCodTurma' => 'TURMA-1000-A', 'outNumClasse' => '1000', 'outDescSerie' => '1ª SERIE', 'outTipoEnsino' => 25],
                    ['outCodTurma' => 'TURMA-1001-B', 'outNumClasse' => '1001', 'outDescSerie' => '2ª SERIE', 'outTipoEnsino' => 25],
                ],
                '100001' => [
                    ['outCodTurma' => 'TURMA-2000-A', 'outNumClasse' => '2000', 'outDescSerie' => '1ª SERIE', 'outTipoEnsino' => 30],
                ],
            ];
            $studentsByClass = [
                'TURMA-1000-A' => self::fakeStudents('1000', 15),
                'TURMA-1001-B' => self::fakeStudents('1001', 18),
                'TURMA-2000-A' => self::fakeStudents('2000', 20),
            ];
        }

        // Disciplines linked to tenant (fallback to common names if none)
        $disciplines = Discipline::where('tenant_id', $tenantId)->limit(6)->get();
        if ($disciplines->isEmpty()) {
            $this->command->warn('Nenhuma disciplina no banco para o tenant. Criando disciplinas padrão.');
            $defaultNames = ['MATEMÁTICA','PORTUGUÊS','CIÊNCIAS','HISTÓRIA','GEOGRAFIA','INGLÊS'];
            foreach ($defaultNames as $name) {
                Discipline::firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $name],
                    ['code' => null]
                );
            }
            $disciplines = Discipline::where('tenant_id', $tenantId)->limit(6)->get();
        }

        // Build a date range for recent days (skip Sundays for demo)
        $dates = [];
        $start = Carbon::now()->subDays(20);
        for ($d = 0; $d < 15; $d++) {
            $day = (clone $start)->addDays($d);
            if (!$day->isSunday()) {
                $dates[] = $day->format('Y-m-d');
            }
        }

        // Weighted statuses
        $statusWeights = [
            'present' => 75,
            'absent' => 20,
            'justified' => 5,
        ];

        // Teaching type mapping using TipoEnsinoHelper if available; fallback otherwise
        $tipoEnsinoLookup = function ($code) {
            $desc = TipoEnsinoHelper::getDescricaoTipoEnsino($code);
            return $desc ?? 'ENSINO FUNDAMENTAL';
        };

        $toInsert = [];
        $maxPerDayPerClass = 60; // guard against excessive inserts

        foreach ($schools as $school) {
            $codEscola = data_get($school, 'outCodEscola');
            $nomeEscola = data_get($school, 'outDescNomeEscola', 'ESCOLA DESCONHECIDA');
            $classes = $classesBySchool[$codEscola] ?? [];

            foreach ($classes as $class) {
                $codTurma = data_get($class, 'outCodTurma');
                $numClasse = (string) data_get($class, 'outNumClasse', $codTurma);
                $serie = (string) data_get($class, 'outDescSerie', 'SERIE');
                $tipoEnsinoCode = (int) data_get($class, 'outTipoEnsino', 25);
                $teachingType = $tipoEnsinoLookup($tipoEnsinoCode);

                $students = $studentsByClass[$numClasse] ?? [];
                if (empty($students)) {
                    // fallback students for this class
                    $students = self::fakeStudents($numClasse, 20);
                }

                foreach ($dates as $date) {
                    // pick a subset of students to simulate attendance on this date
                    $n = min(rand(15, 25), count($students), $maxPerDayPerClass);
                    $picked = Arr::random($students, $n);

                    foreach ($picked as $stu) {
                        $status = self::pickWeighted($statusWeights);
                        $discipline = $disciplines->random();

                        $toInsert[] = [
                            'tenant_id' => $tenantId,
                            'school_code' => (string) $codEscola,
                            'school_name' => (string) $nomeEscola,
                            'class_code' => (string) $numClasse,
                            'class_name' => (string) ($serie.' - '.$numClasse),
                            'discipline_id' => is_object($discipline) ? $discipline->id : $discipline['id'],
                            'student_ra' => (string) data_get($stu, 'ra', '00000000'),
                            'date' => $date,
                            'status' => $status,
                            'type_ensino' => $teachingType,
                            'user_id' => $user->id,
                            'note' => 'demo-seed',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];

                        if (count($toInsert) >= 1500) {
                            \Illuminate\Support\Facades\DB::table('attendance_records')->upsert(
                                $toInsert,
                                ['tenant_id', 'class_code', 'date', 'student_ra', 'discipline_id'],
                                ['status', 'note', 'user_id', 'school_code', 'school_name', 'class_name', 'type_ensino', 'updated_at']
                            );
                            $this->command->info('Upsert de '.count($toInsert).' registros até agora...');
                            $toInsert = [];
                        }
                    }
                }
            }
        }

        if (!empty($toInsert)) {
            \Illuminate\Support\Facades\DB::table('attendance_records')->upsert(
                $toInsert,
                ['tenant_id', 'class_code', 'date', 'student_ra', 'discipline_id'],
                ['status', 'note', 'user_id', 'school_code', 'school_name', 'class_name', 'type_ensino', 'updated_at']
            );
            $this->command->info('Upsert de '.count($toInsert).' registros finais.');
        }

        $totalQuery = AttendanceRecord::where('tenant_id', $tenantId)->where('note', 'demo-seed');
        $total = $totalQuery->count();
        $this->command->info("Total de registros demo gerados: {$total}");
    }

    private static function pickWeighted(array $weights): string
    {
        $sum = array_sum($weights);
        $rand = rand(1, max(1, $sum));
        $cumulative = 0;
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }
        return array_key_first($weights);
    }

    private static function fakeStudents(string $classCode, int $count = 20): array
    {
        $arr = [];
        for ($i = 1; $i <= $count; $i++) {
            $arr[] = [
                'ra' => str_pad($classCode, 4, '0', STR_PAD_LEFT).str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'nome' => 'ALUNO '.Str::upper(Str::random(5)),
            ];
        }
        return $arr;
    }
}