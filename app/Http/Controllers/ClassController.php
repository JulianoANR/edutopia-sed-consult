<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\{
    SedApiService,
    SedTurmasService,
    SedAlunosService
};
use App\Exports\StudentsExport;
use App\Models\TeacherClassDisciplineLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClassController extends Controller
{
    protected SedAlunosService $sedAlunosService;
    protected SedTurmasService $sedTurmasService;

    public function __construct(SedAlunosService $sedAlunosService, SedTurmasService $sedTurmasService)
    {
        $this->sedAlunosService = $sedAlunosService;
        $this->sedTurmasService = $sedTurmasService;
    }

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
            $completeStudentsData = [];
            
            foreach ($students as $student) {
                Log::info('Processando aluno para exportação', ['student' => $student]);
                
                if (isset($student['ra'])) {
                    try {
                        // Remover o dígito verificador do RA (parte após o hífen)
                        $raNumber = explode('-', $student['ra'])[0];
                        $digit = explode('-', $student['ra'])[1];
                        
                        Log::info('Processando RA', ['ra_original' => $student['ra'], 'ra_processado' => $raNumber]);
                        
                        $studentProfile = $this->sedAlunosService->getStudentProfile([
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

    /**
     * Display the user's classes.
     */
    public function myClasses()
    {
        $user = Auth::user();
        $anoLetivo = date('Y');

        $classLinks = TeacherClassDisciplineLink::where('user_id', $user->id)
            ->with('discipline')
            ->get();

        $schoolsIds = $classLinks->pluck('school_code')->unique()->filter()->toArray();
        $classesIds = $classLinks->pluck('class_code')->unique()->filter()->toArray();
        
        // Debug: Log dos IDs coletados
        Log::info('IDs coletados do banco', [
            'schoolsIds' => $schoolsIds,
            'classesIds' => $classesIds,
            'classLinks' => $classLinks->toArray()
        ]);

        $schools = [];

        foreach ($schoolsIds as $schoolId) {
            
            // Buscar apenas as turmas do professor nesta escola específica
            $classesIdsForThisSchool = $classLinks
                ->where('school_code', $schoolId)
                ->pluck('class_code')
                ->unique()
                ->filter()
                ->toArray();
            
            Log::info('Turmas do professor na escola ' . $schoolId, [
                'classesIdsForThisSchool' => $classesIdsForThisSchool
            ]);
            
            $schoolsTmpResult = $this->sedTurmasService
                ->getRelacaoClasses(
                    $anoLetivo,
                    $schoolId
                );

            // Debug: Log dos dados brutos da API
            Log::info('Dados brutos da escola ' . $schoolId, [
                'outClasses_count' => count($schoolsTmpResult['outClasses'] ?? []),
                'outClasses' => $schoolsTmpResult['outClasses'] ?? []
            ]);

            if ($schoolsTmpResult['outClasses']) {
                $originalCount = count($schoolsTmpResult['outClasses']);
                
                // Filtrar apenas as turmas do professor nesta escola específica
                $schoolsTmpResult['outClasses'] = array_filter($schoolsTmpResult['outClasses'], function($class) use ($classesIdsForThisSchool) {
                    return in_array($class['outNumClasse'], $classesIdsForThisSchool);
                });

                // Debug: Log após filtro
                Log::info('Após filtro da escola ' . $schoolId, [
                    'original_count' => $originalCount,
                    'filtered_count' => count($schoolsTmpResult['outClasses']),
                    'classesIdsForThisSchool' => $classesIdsForThisSchool,
                    'filtered_classes' => $schoolsTmpResult['outClasses']
                ]);

                $schools[] = $schoolsTmpResult;
            }
        }
       
        return Inertia::render('Classes/MyClasses', [
            'schools' => $schools
        ]);
    }
}