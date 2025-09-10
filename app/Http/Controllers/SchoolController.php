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
use App\Exports\StudentsExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            
            // Se a conexão falhar, retorna com dados de teste
            if (!$connectionStatus['success']) {
                $testSchools = [
                    [
                        'outCodEscola' => '123456',
                        'outDescNomeEscola' => 'Escola Estadual Teste 1',
                        'outDescEndereco' => 'Rua das Flores, 123',
                        'outDescBairro' => 'Centro',
                        'outDescCidade' => 'São Paulo',
                        'outDescUF' => 'SP'
                    ],
                    [
                        'outCodEscola' => '789012',
                        'outDescNomeEscola' => 'Escola Estadual Teste 2',
                        'outDescEndereco' => 'Av. Principal, 456',
                        'outDescBairro' => 'Vila Nova',
                        'outDescCidade' => 'São Paulo',
                        'outDescUF' => 'SP'
                    ]
                ];
                
                return Inertia::render('Schools/Index', [
                    'schools' => $testSchools,
                    'selectedSchool' => Session::get('selected_school'),
                    'connectionStatus' => array_merge($connectionStatus, ['message' => 'Usando dados de teste - API SED indisponível'])
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

    /**
     * Exportar todos os alunos de uma escola para Excel
     * 
     * @param Request $request
     * @return StreamedResponse|JsonResponse
     */
    /**
     * Buscar turmas de uma escola para exportação em etapas
     */
    public function getSchoolClasses(Request $request)
    {
        try {
            $request->validate([
                'ano_letivo' => 'required|string|size:4',
                'cod_escola' => 'required|string'
            ]);
            
            $anoLetivo = $request->input('ano_letivo');
            $codEscola = $request->input('cod_escola');
            
            Log::info('Buscando turmas da escola para exportação', [
                'cod_escola' => $codEscola,
                'ano_letivo' => $anoLetivo
            ]);
            
            $classesResult = $this->sedApiService->getRelacaoClasses(
                $anoLetivo,
                $codEscola
            );
            
            $classes = $classesResult['outClasses'] ?? [];
            
            if (empty($classes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma turma encontrada para esta escola no ano letivo informado.'
                ], 404);
            }
            
            // Formatar dados das turmas para o frontend
            $formattedClasses = array_map(function($class) {
                return [
                    'cod_turma' => $class['outNumClasse'] ?? null,
                    'nome_turma' => ($class['outCodSerieAno'] ?? '') . '°' . ($class['outTurma'] ?? ''),
                    'serie' => $class['outCodSerieAno'] ?? '',
                    'turma' => $class['outTurma'] ?? ''
                ];
            }, $classes);
            
            // Filtrar turmas sem código
            $formattedClasses = array_filter($formattedClasses, function($class) {
                return !empty($class['cod_turma']);
            });
            
            Log::info('Turmas encontradas', ['total' => count($formattedClasses)]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'classes' => array_values($formattedClasses),
                    'total' => count($formattedClasses)
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (SedApiException $e) {
            Log::error('Erro na API SED ao buscar turmas', [
                'error' => $e->getMessage(),
                'cod_escola' => $request->input('cod_escola')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao acessar dados do SED',
                'error' => $e->getMessage()
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar turmas', [
                'error' => $e->getMessage(),
                'cod_escola' => $request->input('cod_escola')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar turmas'
            ], 500);
        }
    }
    
    /**
     * Buscar alunos de uma turma específica para exportação
     */
    public function getClassStudentsForExport(Request $request)
    {
        try {
            $request->validate([
                'cod_turma' => 'required|string',
                'nome_turma' => 'required|string',
                'nome_escola' => 'required|string',
                'cod_escola' => 'required|string'
            ]);
            
            $codTurma = $request->input('cod_turma');
            $nomeTurma = $request->input('nome_turma');
            $nomeEscola = $request->input('nome_escola');
            $codEscola = $request->input('cod_escola');
            
            Log::info('Buscando alunos da turma para exportação', [
                'cod_turma' => $codTurma,
                'nome_turma' => $nomeTurma
            ]);
            
            // Buscar alunos da turma
            $studentsResult = $this->sedApiService->consultarTurma($codTurma);
            $students = $studentsResult['outAlunos'] ?? [];
            
            if (empty($students)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'students' => [],
                        'additional_data' => [],
                        'total' => 0
                    ]
                ]);
            }
            
            $studentsData = [];
            $additionalData = [];
            
            // Para cada aluno, buscar a ficha completa
            foreach ($students as $student) {
                $numRA = $student['outNumRA'] ?? null;
                $digitoRA = $student['outDigitoRA'] ?? null;
                $ufRA = $student['outSiglaUFRA'] ?? 'SP';
                
                if (!$numRA) {
                    Log::warning('Aluno sem RA encontrado', ['aluno' => $student]);
                    continue;
                }
                
                try {
                    // Buscar ficha completa do aluno
                    $studentDetails = $this->sedApiService->getStudentProfile([
                        'inNumRA' => $numRA,
                        'inDigitoRA' => $digitoRA,
                        'inSiglaUFRA' => $ufRA
                    ]);
                    
                    $studentsData[] = $studentDetails;
                    
                    // Adicionar dados contextuais
                    $additionalData[] = [
                        'turma' => $nomeTurma,
                        'escola' => $nomeEscola,
                        'codigo_escola' => $codEscola,
                        'data_inicio_matricula' => $student['outDataInicioMatricula'] ?? '',
                        'data_fim_matricula' => $student['outDataFimMatricula'] ?? ''
                    ];
                    
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar detalhes do aluno', [
                        'ra' => $numRA . '-' . $digitoRA,
                        'turma' => $nomeTurma,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Continuar com próximo aluno em caso de erro
                    continue;
                }
            }
            
            Log::info('Alunos processados da turma', [
                'turma' => $nomeTurma,
                'total_alunos' => count($studentsData)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $studentsData,
                    'additional_data' => $additionalData,
                    'total' => count($studentsData)
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (SedApiException $e) {
            Log::error('Erro na API SED ao buscar alunos da turma', [
                'error' => $e->getMessage(),
                'cod_turma' => $request->input('cod_turma')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao acessar dados do SED',
                'error' => $e->getMessage()
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar alunos da turma', [
                'error' => $e->getMessage(),
                'cod_turma' => $request->input('cod_turma')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar alunos da turma'
            ], 500);
        }
    }
    
    /**
     * Exportar dados coletados para Excel
     */
    public function exportCollectedStudents(Request $request)
    {
        try {
            $request->validate([
                'students_data' => 'required|array',
                'additional_data' => 'required|array',
                'cod_escola' => 'required|string',
                'ano_letivo' => 'required|string|size:4'
            ]);
            
            $studentsData = $request->input('students_data');
            $additionalData = $request->input('additional_data');
            $codEscola = $request->input('cod_escola');
            $anoLetivo = $request->input('ano_letivo');
            
            if (empty($studentsData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum dado de aluno fornecido para exportação.'
                ], 400);
            }
            
            Log::info('Gerando exportação de dados coletados', [
                'total_alunos' => count($studentsData),
                'cod_escola' => $codEscola
            ]);
            
            // Gerar o arquivo CSV usando a classe de exportação
            $export = new StudentsExport($studentsData, $additionalData, false);
            $csvData = $export->exportCsv();
            
            // Retornar o arquivo para download
            $filename = 'alunos_escola_' . $codEscola . '_' . $anoLetivo . '_' . date('Y-m-d_H-i-s') . '.csv';
            
            return new StreamedResponse(function() use ($csvData) {
                $handle = fopen('php://output', 'w');
                
                // Adicionar BOM para UTF-8
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                foreach ($csvData as $row) {
                    fputcsv($handle, $row, ';');
                }
                
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro inesperado durante exportação final', [
                'error' => $e->getMessage(),
                'cod_escola' => $request->input('cod_escola')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor durante a exportação',
                'error' => 'Erro inesperado durante a exportação'
            ], 500);
        }
    }
    
    /**
     * Método original mantido para compatibilidade (DEPRECATED)
     */
    public function exportStudents(Request $request)
    {
        try {
            // Validar parâmetros obrigatórios
            $request->validate([
                'ano_letivo' => 'required|string|size:4',
                'cod_escola' => 'required|string',
                'nome_escola' => 'required|string'
            ]);
            
            $anoLetivo = $request->input('ano_letivo');
            $codEscola = $request->input('cod_escola');
            $nomeEscola = $request->input('nome_escola');
            
            Log::info('Iniciando exportação de alunos da escola (método legado)', [
                'cod_escola' => $codEscola,
                'ano_letivo' => $anoLetivo
            ]);
            
            // 1. Buscar todas as turmas da escola
            $classesResult = $this->sedApiService->getRelacaoClasses(
                $anoLetivo,
                $codEscola
            );
            
            $classes = $classesResult['outClasses'] ?? [];

    
            if (empty($classes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma turma encontrada para esta escola no ano letivo informado.'
                ], 404);
            }
            
            Log::info('Turmas encontradas', ['total' => count($classes)]);
            
            $allStudentsData = [];
            $additionalData = [];
            
            // 2. Para cada turma, buscar os alunos
            foreach ($classes as $class) {
                $codTurma = $class['outNumClasse'] ?? null;
                $nomeTurma = $class['outCodSerieAno'] . '°' . $class['outTurma'];

                if (!$codTurma) {
                    Log::warning('Turma sem código encontrada', ['turma' => $class]);
                    continue;
                }
                
                try {
                    // Buscar alunos da turma usando consultarTurma
                    $studentsResult = $this->sedApiService->consultarTurma($codTurma);
                    $students = $studentsResult['outAlunos'] ?? [];

                    Log::info('Alunos encontrados na turma', [
                        'turma' => $nomeTurma,
                        'total_alunos' => count($students)
                    ]);
                    
                    // 3. Para cada aluno, buscar a ficha completa
                    foreach ($students as $student) {
                        $numRA = $student['outNumRA'] ?? null;
                        $digitoRA = $student['outDigitoRA'] ?? null;
                        $ufRA = $student['outSiglaUFRA'] ?? 'SP';
                        
                        if (!$numRA) {
                            Log::warning('Aluno sem RA encontrado', ['aluno' => $student]);
                            continue;
                        }
                        
                        try {
                            // Buscar ficha completa do aluno
                            $studentDetails = $this->sedApiService->getStudentProfile([
                                'inNumRA' => $numRA,
                                'inDigitoRA' => $digitoRA,
                                'inSiglaUFRA' => $ufRA
                            ]);

                            $allStudentsData[] = $studentDetails;
                            
                            // Adicionar dados contextuais
                            $additionalData[] = [
                                'turma' => $nomeTurma,
                                'escola' => $nomeEscola,
                                'codigo_escola' => $codEscola,
                                'data_inicio_matricula' => $student['outDataInicioMatricula'] ?? '',
                                'data_fim_matricula' => $student['outDataFimMatricula'] ?? ''
                            ];
                            
                        } catch (\Exception $e) {
                            Log::error('Erro ao buscar detalhes do aluno', [
                                'ra' => $numRA . '-' . $digitoRA,
                                'turma' => $nomeTurma,
                                'error' => $e->getMessage()
                            ]);
                            
                            // Continuar com próximo aluno em caso de erro
                            continue;
                        }
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar alunos da turma', [
                        'turma' => $nomeTurma,
                        'cod_turma' => $codTurma,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Continuar com próxima turma em caso de erro
                    continue;
                }
            }
            
            if (empty($allStudentsData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum aluno encontrado nas turmas desta escola.'
                ], 404);
            }
            
            Log::info('Exportação preparada', [
                'total_alunos' => count($allStudentsData),
                'escola' => $nomeEscola
            ]);
            
            // 4. Gerar o arquivo CSV usando a classe de exportação
            $export = new StudentsExport($allStudentsData, $additionalData, false); // usar formato completo
            $csvData = $export->exportCsv();
            
            // 5. Retornar o arquivo para download
            $filename = 'alunos_escola_' . $codEscola . '_' . $anoLetivo . '_' . date('Y-m-d_H-i-s') . '.csv';
            
            return new StreamedResponse(function() use ($csvData) {
                $handle = fopen('php://output', 'w');
                
                // Adicionar BOM para UTF-8
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                foreach ($csvData as $row) {
                    fputcsv($handle, $row, ';');
                }
                
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (SedApiException $e) {
            Log::error('Erro na API SED durante exportação', [
                'error' => $e->getMessage(),
                'cod_escola' => $codEscola ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao acessar dados do SED',
                'error' => $e->getMessage()
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Erro inesperado durante exportação de alunos da escola', [
                'error' => $e->getMessage(),
                'cod_escola' => $codEscola ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor durante a exportação',
                'error' => 'Erro inesperado durante a exportação'
            ], 500);
        }
    }
}