<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\SedApiService;
use App\Exports\StudentsExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClassController extends Controller
{
    /**
     * Display the specified class.
     */
    public function show(Request $request, string $classCode): Response|RedirectResponse
    {
        // Recupera a escola selecionada da sessão
        $selectedSchool = session('selected_school');
           
        if (!$selectedSchool) {
            return redirect()->route('schools.index')
                ->with('error', 'Você precisa selecionar uma escola primeiro.');
        }
        
        return Inertia::render('Classes/Show', [
            'classCode' => $classCode,
            'selectedSchool' => $selectedSchool,
        ]);
    }

    /**
     * Export students data to Excel.
     */
    public function exportExcel(Request $request)
    {
        try {
            $classCode = $request->input('classCode');
            $students = $request->input('students', []);
            
            Log::info('Exportação CSV iniciada', [
                'classCode' => $classCode,
                'studentsCount' => count($students),
                'studentsData' => $students
            ]);
            
            if (empty($students)) {
                Log::warning('Nenhum aluno encontrado para exportação');
                return response()->json(['error' => 'Nenhum aluno encontrado'], 400);
            }
            
            // Buscar dados completos de cada aluno
            $sedApiService = new SedApiService();
            $completeStudentsData = [];
            
            foreach ($students as $student) {
                Log::info('Processando aluno para exportação', ['student' => $student]);
                
                if (isset($student['ra'])) {
                    try {
                        // Remover o dígito verificador do RA (parte após o hífen)
                        $raNumber = explode('-', $student['ra'])[0];
                        $digit = explode('-', $student['ra'])[1];
                        
                        Log::info('Processando RA', ['ra_original' => $student['ra'], 'ra_processado' => $raNumber]);
                        
                        $studentProfile = $sedApiService->getStudentProfile([
                            'inNumRA' => $raNumber,
                            'inDigitoRA' => $digit,
                            'inSiglaUFRA' => 'SP'
                        ]);
                        
                        if ($studentProfile) {
                            $completeStudentsData[] = $studentProfile;
                            Log::info('Dados do aluno obtidos com sucesso', ['ra_original' => $student['ra'], 'ra_processado' => $raNumber]);
                        } else {
                            Log::warning('Perfil do aluno não encontrado', ['ra_original' => $student['ra'], 'ra_processado' => $raNumber]);
                        }
                    } catch (\Exception $e) {
                        // Log do erro mas continua com os outros alunos
                        Log::warning('Erro ao buscar dados do aluno RA: ' . $student['ra'] . ' - ' . $e->getMessage());
                    }
                } else {
                    Log::warning('Aluno sem RA definido', ['student' => $student]);
                }
            }

            Log::info('Dados completos obtidos', [
                'totalStudents' => count($students),
                'completeDataCount' => count($completeStudentsData)
            ]);
            
            if (empty($completeStudentsData)) {
                Log::error('Nenhum dado completo de aluno foi obtido');
                return response()->json(['error' => 'Não foi possível obter dados completos dos alunos'], 400);
            }
            
            // Criar o arquivo CSV
            $export = new StudentsExport($completeStudentsData);
            $csvData = $export->exportCsv();
            
            $fileName = "alunos_turma_{$classCode}.csv";
            $tempFile = tempnam(sys_get_temp_dir(), 'csv');
            
            $handle = fopen($tempFile, 'w');
            
            // Adicionar BOM para UTF-8 (para Excel abrir corretamente)
            fwrite($handle, "\xEF\xBB\xBF");
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row, ';'); // Usar ponto e vírgula como separador
            }
            
            fclose($handle);
            
            return response()->download($tempFile, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Erro na exportação CSV: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno do servidor'], 500);
        }
    }
}