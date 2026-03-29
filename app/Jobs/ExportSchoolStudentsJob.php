<?php

namespace App\Jobs;

use App\Exports\StudentsExport;
use App\Models\StudentExportRequest;
use App\Services\SedAlunosService;
use App\Services\SedTurmasService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportSchoolStudentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Tempo máximo de execução: 8 horas (exportações grandes / muitas escolas).
     * O worker deve usar --timeout >= este valor (ex.: php artisan queue:work --timeout=28800).
     */
    public int $timeout = 28800;

    /**
     * Sem retry automático para evitar reprocessamento de dados já parcialmente gerados
     */
    public int $tries = 1;

    public function __construct(
        private int $exportRequestId,
        private int $userId,
    ) {}

    public function handle(): void
    {
        $exportRequest = StudentExportRequest::findOrFail($this->exportRequestId);

        // Se foi cancelada antes de iniciar, não processa.
        if (($exportRequest->status ?? null) === 'cancelled') {
            Log::warning('ExportSchoolStudentsJob: Cancelado antes de iniciar', [
                'export_request_id' => $this->exportRequestId,
                'user_id' => $this->userId,
            ]);
            return;
        }

        $exportRequest->update(['status' => 'processing']);

        Log::info('ExportSchoolStudentsJob: Iniciando', [
            'export_request_id' => $this->exportRequestId,
            'user_id' => $this->userId,
            'school_codes' => array_column($exportRequest->school_codes, 'code'),
        ]);

        // Autenticar o usuário no contexto do job para que SedApiService
        // consiga resolver as credenciais do tenant corretamente
        Auth::loginUsingId($this->userId, false);

        // Resolver serviços após autenticação
        $turmasService = app(SedTurmasService::class);
        $alunosService = app(SedAlunosService::class);

        $allStudentsData = [];
        $allAdditionalData = [];
        $processedStudents = 0;

        $schoolsTotal = count($exportRequest->school_codes);
        $schoolIndex = 0;
        $schoolsCompleted = 0;
        $schoolsSkippedNoClasses = 0;
        $schoolsFailedApi = 0;

        Log::info('ExportSchoolStudentsJob: Plano da execução', [
            'export_request_id' => $this->exportRequestId,
            'tenant_id' => $exportRequest->tenant_id,
            'ano_letivo' => $exportRequest->ano_letivo,
            'total_escolas' => $schoolsTotal,
            'codigos_escolas' => array_column($exportRequest->school_codes, 'code'),
        ]);

        foreach ($exportRequest->school_codes as $schoolInfo) {
            // Cancelamento cooperativo: se o usuário reiniciou, encerra o job cedo.
            $exportRequest->refresh();
            if (($exportRequest->status ?? null) === 'cancelled') {
                Log::warning('ExportSchoolStudentsJob: Cancelado durante execução (antes da próxima escola)', [
                    'export_request_id' => $this->exportRequestId,
                    'progress_current' => $exportRequest->progress_current,
                ]);
                return;
            }

            $schoolIndex++;
            $codEscola = $schoolInfo['code'];
            $nomeEscola = $schoolInfo['name'];

            Log::info('ExportSchoolStudentsJob: Início escola', [
                'export_request_id' => $this->exportRequestId,
                'escola' => "{$schoolIndex}/{$schoolsTotal}",
                'cod_escola' => $codEscola,
                'nome_escola' => $nomeEscola,
                'alunos_acumulados_ate_agora' => $processedStudents,
            ]);

            $studentsBeforeSchool = $processedStudents;
            $classesProcessed = 0;
            $classesFailed = 0;
            $skippedNoRa = 0;
            $profileErrors = 0;

            try {
                $classesResult = $turmasService->getRelacaoClasses($exportRequest->ano_letivo, $codEscola);
                $classes = $classesResult['outClasses'] ?? [];
                $classesTotal = count($classes);

                if (empty($classes)) {
                    Log::warning('ExportSchoolStudentsJob: Escola sem turmas no ano letivo (pulando)', [
                        'export_request_id' => $this->exportRequestId,
                        'escola' => "{$schoolIndex}/{$schoolsTotal}",
                        'cod_escola' => $codEscola,
                        'ano_letivo' => $exportRequest->ano_letivo,
                    ]);
                    $schoolsSkippedNoClasses++;
                    continue;
                }

                Log::info('ExportSchoolStudentsJob: Turmas encontradas para a escola', [
                    'export_request_id' => $this->exportRequestId,
                    'escola' => "{$schoolIndex}/{$schoolsTotal}",
                    'cod_escola' => $codEscola,
                    'total_turmas' => $classesTotal,
                ]);

                $classIndex = 0;
                foreach ($classes as $class) {
                    $exportRequest->refresh();
                    if (($exportRequest->status ?? null) === 'cancelled') {
                        Log::warning('ExportSchoolStudentsJob: Cancelado durante execução (antes da próxima turma)', [
                            'export_request_id' => $this->exportRequestId,
                            'cod_escola' => $codEscola,
                            'progress_current' => $exportRequest->progress_current,
                        ]);
                        return;
                    }

                    $classIndex++;
                    $codTurma = $class['outNumClasse'];
                    $nomeTurma = $class['nome_turma'] ?? $codTurma;
                    $addedThisClass = 0;

                    try {
                        $turmaData = $turmasService->consultarTurma($codTurma);
                        $students = $turmaData['outAlunos'] ?? [];
                        $studentsListed = count($students);

                        foreach ($students as $student) {
                            if ($processedStudents % 25 === 0) {
                                $exportRequest->refresh();
                                if (($exportRequest->status ?? null) === 'cancelled') {
                                    Log::warning('ExportSchoolStudentsJob: Cancelado durante execução (durante alunos)', [
                                        'export_request_id' => $this->exportRequestId,
                                        'cod_escola' => $codEscola,
                                        'cod_turma' => $codTurma,
                                        'progress_current' => $exportRequest->progress_current,
                                    ]);
                                    return;
                                }
                            }

                            $numRA    = $student['outNumRA'] ?? null;
                            $digitoRA = $student['outDigitoRA'] ?? null;
                            $ufRA     = $student['outSiglaUFRA'] ?? 'SP';

                            if (!$numRA) {
                                $skippedNoRa++;
                                continue;
                            }

                            try {
                                $studentDetails = $alunosService->getStudentProfile([
                                    'inNumRA'      => $numRA,
                                    'inDigitoRA'   => $digitoRA,
                                    'inSiglaUFRA'  => $ufRA,
                                ]);

                                $allStudentsData[]    = $studentDetails;
                                $allAdditionalData[]  = [
                                    'turma'                 => $nomeTurma,
                                    'escola'                => $nomeEscola,
                                    'codigo_escola'         => $codEscola,
                                    'turno'                 => $turmaData['outDescricaoTurno'] ?? '',
                                    'tipo_ensino'           => $turmaData['outDescTipoEnsino'] ?? '',
                                    'tipo_classe'           => $turmaData['outDescTipoClasse'] ?? '',
                                    'cod_tipo_ensino'       => $turmaData['outCodTipoEnsino'] ?? '',
                                    'cod_tipo_classe'       => $turmaData['outCodTipoClasse'] ?? '',
                                    'situacao_matricula'    => $student['outDescSitMatricula'] ?? '',
                                    'data_inicio_matricula' => $student['outDataInicioMatricula'] ?? '',
                                    'data_fim_matricula'    => $student['outDataFimMatricula'] ?? '',
                                ];

                                $processedStudents++;
                                $addedThisClass++;

                                // Atualizar progresso a cada 10 alunos para não sobrecarregar o banco
                                if ($processedStudents % 10 === 0) {
                                    $exportRequest->update(['progress_current' => $processedStudents]);
                                }

                            } catch (\Exception $e) {
                                $profileErrors++;
                                Log::warning('ExportSchoolStudentsJob: Erro ao buscar perfil do aluno', [
                                    'export_request_id' => $this->exportRequestId,
                                    'escola' => "{$schoolIndex}/{$schoolsTotal}",
                                    'cod_escola' => $codEscola,
                                    'turma' => "{$classIndex}/{$classesTotal}",
                                    'cod_turma' => $codTurma,
                                    'ra'    => $numRA . '-' . $digitoRA,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        $classesProcessed++;
                        Log::info('ExportSchoolStudentsJob: Turma concluída', [
                            'export_request_id' => $this->exportRequestId,
                            'escola' => "{$schoolIndex}/{$schoolsTotal}",
                            'cod_escola' => $codEscola,
                            'turma' => "{$classIndex}/{$classesTotal}",
                            'cod_turma' => $codTurma,
                            'nome_turma' => $nomeTurma,
                            'alunos_listados_sed' => $studentsListed,
                            'perfis_exportados_ok' => $addedThisClass,
                            'alunos_total_export' => $processedStudents,
                        ]);

                    } catch (\Exception $e) {
                        $classesFailed++;
                        Log::warning('ExportSchoolStudentsJob: Erro ao consultar turma', [
                            'export_request_id' => $this->exportRequestId,
                            'escola' => "{$schoolIndex}/{$schoolsTotal}",
                            'cod_escola' => $codEscola,
                            'turma' => "{$classIndex}/{$classesTotal}",
                            'cod_turma' => $codTurma,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }

                $schoolsCompleted++;
                $studentsThisSchool = $processedStudents - $studentsBeforeSchool;

                $exportRequest->update(['progress_current' => $processedStudents]);

                Log::info('ExportSchoolStudentsJob: Fim escola', [
                    'export_request_id' => $this->exportRequestId,
                    'escola' => "{$schoolIndex}/{$schoolsTotal}",
                    'cod_escola' => $codEscola,
                    'nome_escola' => $nomeEscola,
                    'turmas_ok' => $classesProcessed,
                    'turmas_falha' => $classesFailed,
                    'alunos_novos_nesta_escola' => $studentsThisSchool,
                    'alunos_total_export' => $processedStudents,
                    'alunos_sem_ra_ignorados' => $skippedNoRa,
                    'falhas_perfil_aluno' => $profileErrors,
                ]);

            } catch (\Exception $e) {
                $schoolsFailedApi++;
                Log::error('ExportSchoolStudentsJob: Erro ao buscar turmas da escola', [
                    'export_request_id' => $this->exportRequestId,
                    'escola' => "{$schoolIndex}/{$schoolsTotal}",
                    'cod_escola' => $codEscola,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('ExportSchoolStudentsJob: Resumo após todas as escolas', [
            'export_request_id' => $this->exportRequestId,
            'total_escolas_planejadas' => $schoolsTotal,
            'escolas_com_processamento_ok' => $schoolsCompleted,
            'escolas_sem_turmas' => $schoolsSkippedNoClasses,
            'escolas_com_erro_api_turmas' => $schoolsFailedApi,
            'total_alunos_exportados' => $processedStudents,
        ]);

        if (empty($allStudentsData)) {
            $exportRequest->update([
                'status'        => 'failed',
                'error_message' => 'Nenhum aluno encontrado nas escolas selecionadas.',
            ]);
            return;
        }

        $filePath = $this->generateCsv($exportRequest, $allStudentsData, $allAdditionalData);

        $exportRequest->update([
            'status'           => 'done',
            'file_path'        => $filePath,
            'progress_current' => $processedStudents,
        ]);

        Log::info('ExportSchoolStudentsJob: Concluído', [
            'export_request_id' => $this->exportRequestId,
            'total_alunos'      => $processedStudents,
            'file_path'         => $filePath,
        ]);
    }

    private function generateCsv(StudentExportRequest $exportRequest, array $studentsData, array $additionalData): string
    {
        $export  = new StudentsExport($studentsData, $additionalData, false, $exportRequest->selected_fields ?? []);
        $csvData = $export->exportCsv();

        $tenantId = $exportRequest->tenant_id;
        // Sempre sobrescreve o último arquivo para o tenant (o histórico mantém apenas registros)
        $path = "exports/{$tenantId}/alunos_export_latest.csv";

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Callback chamado quando o job falha (timeout, exceção não capturada, etc.)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExportSchoolStudentsJob: Job falhou', [
            'export_request_id' => $this->exportRequestId,
            'error'             => $exception->getMessage(),
        ]);

        StudentExportRequest::where('id', $this->exportRequestId)->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
