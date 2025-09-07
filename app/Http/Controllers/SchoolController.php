<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\SedApiService;
use App\Exceptions\SedApiException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class SchoolController extends Controller
{
    protected $sedApiService;

    public function __construct(SedApiService $sedApiService)
    {
        $this->sedApiService = $sedApiService;
    }

    /**
     * Exibe a listagem de escolas
     */
    public function index(Request $request)
    {
        try {
            // Testa a conexão primeiro
            $connectionStatus = $this->getConnectionStatus();
            
            // Se a conexão falhar, retorna com erro
            if (!$connectionStatus['success']) {
                return Inertia::render('Schools/Index', [
                    'schools' => [],
                    'selectedSchool' => Session::get('selected_school'),
                    'connectionStatus' => $connectionStatus
                ]);
            }
            
            // Busca escolas por município via API SED
            $schoolsData = $this->sedApiService->getEscolasPorMunicipio();
            $schools = $schoolsData['outEscolas'] ?? [];
            
            // Verifica se há uma escola selecionada na sessão
            $selectedSchool = Session::get('selected_school');
            
            return Inertia::render('Schools/Index', [
                'schools' => $schools,
                'selectedSchool' => $selectedSchool,
                'connectionStatus' => $connectionStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar escolas: ' . $e->getMessage());
            
            return Inertia::render('Schools/Index', [
                'schools' => [],
                'selectedSchool' => Session::get('selected_school'),
                'connectionStatus' => [
                    'success' => false,
                    'message' => 'Erro ao carregar escolas',
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Exibe os detalhes de uma escola específica
     */
    public function show(Request $request, $schoolId)
    {
        try {
            // Busca todas as escolas e filtra pela específica
            $schoolsData = $this->sedApiService->getEscolasPorMunicipio();
            $schools = $schoolsData['outEscolas'] ?? [];
            
            // Filtra a escola pelo código
            $school = collect($schools)->firstWhere('outCodEscola', $schoolId);
            
            if (!$school) {
                return redirect()->route('schools.index')
                    ->with('error', 'Escola não encontrada.');
            }
            
            // Converte para array se necessário
            $school = is_array($school) ? $school : $school->toArray();
            
            // Adiciona contadores padrão se não existirem
            if (!isset($school['students_count'])) {
                $school['students_count'] = 0;
            }
            if (!isset($school['classes_count'])) {
                $school['classes_count'] = 0;
            }
            if (!isset($school['teachers_count'])) {
                $school['teachers_count'] = 0;
            }
            
            $selectedSchool = Session::get('selected_school');
            
            return Inertia::render('Schools/Show', [
                'school' => $school,
                'selectedSchool' => $selectedSchool
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar detalhes da escola: ' . $e->getMessage());
            
            return redirect()->route('schools.index')
                ->with('error', 'Erro ao carregar detalhes da escola: ' . $e->getMessage());
        }
    }

    /**
     * Seleciona uma escola para trabalhar
     */
    public function select(Request $request)
    {
        $request->validate([
            'school_id' => 'required|string',
            'school_name' => 'required|string'
        ]);

        // Armazena a escola selecionada na sessão
        Session::put('selected_school', [
            'id' => $request->school_id,
            'name' => $request->school_name,
            'selected_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Escola selecionada com sucesso!',
            'school' => Session::get('selected_school')
        ]);
    }

    /**
     * Busca os alunos de uma escola específica
     */
    public function students(Request $request, $schoolId)
    {
        try {
            $students = $this->sedApiService->getStudents($schoolId);
            
            // Normaliza os dados dos alunos
            $normalizedStudents = collect($students)->map(function ($student) {
                return [
                    'id' => $student['id'] ?? null,
                    'name' => $student['name'] ?? $student['nome'] ?? 'N/A',
                    'registration' => $student['registration'] ?? $student['matricula'] ?? 'N/A',
                    'class' => $student['class'] ?? $student['turma'] ?? 'N/A',
                    'status' => $student['status'] ?? 'active'
                ];
            })->toArray();
            
            return response()->json([
                'success' => true,
                'students' => $normalizedStudents
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar alunos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar alunos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca as turmas de uma escola específica
     */
    public function classes(Request $request, $schoolId)
    {
        try {
            $classes = $this->sedApiService->getClasses($schoolId);
            
            // Normaliza os dados das turmas
            $normalizedClasses = collect($classes)->map(function ($class) {
                return [
                    'id' => $class['id'] ?? null,
                    'name' => $class['name'] ?? $class['nome'] ?? 'N/A',
                    'grade' => $class['grade'] ?? $class['serie'] ?? 'N/A',
                    'shift' => $class['shift'] ?? $class['turno'] ?? 'N/A',
                    'students_count' => $class['students_count'] ?? $class['total_alunos'] ?? 0,
                    'teacher' => $class['teacher'] ?? $class['professor'] ?? 'N/A'
                ];
            })->toArray();
            
            return response()->json([
                'success' => true,
                'classes' => $normalizedClasses
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar turmas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar turmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Testa a conexão com a API SED
     */
    public function testConnection()
    {
        return response()->json($this->getConnectionStatus());
    }
    
    /**
     * Verifica o status da conexão com a API SED
     */
    private function getConnectionStatus()
    {
        try {
            $result = $this->sedApiService->testConnection();
            
            return [
                'success' => true,
                'message' => 'Conexão com SED estabelecida com sucesso',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Erro na conexão SED: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Falha na conexão com SED',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar classes de uma escola via API SED
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClasses(Request $request): JsonResponse
    {
        try {
            // Validar parâmetros obrigatórios
            $request->validate([
                'ano_letivo' => 'required|string|size:4',
                'cod_escola' => 'required|string',
                'cod_tipo_ensino' => 'nullable|string',
                'cod_serie_ano' => 'nullable|string',
                'cod_turno' => 'nullable|string',
                'semestre' => 'nullable|string'
            ]);
            
            $anoLetivo = $request->input('ano_letivo');
            $codEscola = $request->input('cod_escola');
            $codTipoEnsino = $request->input('cod_tipo_ensino');
            $codSerieAno = $request->input('cod_serie_ano');
            $codTurno = $request->input('cod_turno');
            $semestre = $request->input('semestre');
            
            // Buscar classes via SED API Service
            $result = $this->sedApiService->getRelacaoClasses(
                $anoLetivo, 
                $codEscola,
                $codTipoEnsino,
                $codSerieAno,
                $codTurno,
                $semestre
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Classes obtidas com sucesso',
                'total_classes' => count($result['outClasses'] ?? [])
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (SedApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar classes na API SED',
                'error' => $e->getMessage()
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar classes', [
                'error' => $e->getMessage(),
                'ano_letivo' => $anoLetivo ?? null,
                'cod_escola' => $codEscola ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar classes'
            ], 500);
        }
    }
}