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
     * Tempo máximo de execução: 2 horas (para escolas com muitos alunos)
     */
    public int $timeout = 7200;

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

        foreach ($exportRequest->school_codes as $schoolInfo) {
            $codEscola = $schoolInfo['code'];
            $nomeEscola = $schoolInfo['name'];

            Log::info('ExportSchoolStudentsJob: Processando escola', [
                'cod_escola' => $codEscola,
                'nome_escola' => $nomeEscola,
            ]);

            try {
                $classesResult = $turmasService->getRelacaoClasses($exportRequest->ano_letivo, $codEscola);
                $classes = $classesResult['outClasses'] ?? [];

                if (empty($classes)) {
                    Log::warning('ExportSchoolStudentsJob: Nenhuma turma encontrada', ['cod_escola' => $codEscola]);
                    continue;
                }

                foreach ($classes as $class) {
                    $codTurma = $class['outNumClasse'];
                    $nomeTurma = $class['nome_turma'] ?? $codTurma;

                    try {
                        $turmaData = $turmasService->consultarTurma($codTurma);
                        $students = $turmaData['outAlunos'] ?? [];

                        foreach ($students as $student) {
                            $numRA    = $student['outNumRA'] ?? null;
                            $digitoRA = $student['outDigitoRA'] ?? null;
                            $ufRA     = $student['outSiglaUFRA'] ?? 'SP';

                            if (!$numRA) {
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

                                // Atualizar progresso a cada 10 alunos para não sobrecarregar o banco
                                if ($processedStudents % 10 === 0) {
                                    $exportRequest->update(['progress_current' => $processedStudents]);
                                }

                            } catch (\Exception $e) {
                                Log::warning('ExportSchoolStudentsJob: Erro ao buscar perfil do aluno', [
                                    'ra'    => $numRA . '-' . $digitoRA,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                    } catch (\Exception $e) {
                        Log::warning('ExportSchoolStudentsJob: Erro ao consultar turma', [
                            'cod_turma' => $codTurma,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error('ExportSchoolStudentsJob: Erro ao buscar turmas da escola', [
                    'cod_escola' => $codEscola,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

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
        $schoolCodes = implode('-', array_column($exportRequest->school_codes, 'code'));
        $filename = "export_alunos_{$schoolCodes}_{$exportRequest->ano_letivo}_" . now()->format('Ymd_His') . '.csv';
        $path = "exports/{$tenantId}/{$filename}";

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

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
