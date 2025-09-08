<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\SedApiService;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    protected $sedApiService;

    public function __construct(SedApiService $sedApiService)
    {
        $this->sedApiService = $sedApiService;
    }

    /**
     * Exibe a ficha do aluno
     */
    public function show($studentRa)
    {
        try {
            // Extrair RA e dígito do parâmetro
            $raData = $this->parseStudentRa($studentRa);
            
            // Buscar dados do aluno na API SED
            $studentData = $this->sedApiService->getStudentProfile($raData);
                
            return Inertia::render('Students/Show', [
                'studentRa' => $studentRa,
                'studentData' => $studentData,
                'selectedSchool' => session('selected_school')
            ]);
        } catch (\App\Exceptions\SedApiException $e) {
            Log::error('Erro SED API ao carregar ficha do aluno: ' . $e->getMessage());
            
            // Verificar se é erro de RA não encontrado
            $errorMessage = 'Não foi possível carregar os dados do aluno.';
            if (str_contains($e->getMessage(), 'ncaapi-api-aluno-exibirfichaaluno')) {
                $errorMessage = 'RA não encontrado ou inválido. Verifique se o número do RA está correto.';
            }
            
            return Inertia::render('Students/Show', [
                'studentRa' => $studentRa,
                'studentData' => null,
                'error' => $errorMessage,
                'selectedSchool' => session('selected_school')
            ]);
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao carregar ficha do aluno: ' . $e->getMessage());
            
            return Inertia::render('Students/Show', [
                'studentRa' => $studentRa,
                'studentData' => null,
                'error' => 'Erro interno do sistema. Tente novamente mais tarde.',
                'selectedSchool' => session('selected_school')
            ]);
        }
    }

    /**
     * Extrai o RA e dígito do parâmetro da URL
     */
    private function parseStudentRa($studentRa)
    {
        // Assumindo que o RA vem no formato: 123456789012 ou 123456789012-1
        $parts = explode('-', $studentRa);
        
        // Remover zeros à esquerda desnecessários, mas manter pelo menos 1 dígito
        $raNumber = ltrim($parts[0], '0') ?: '0';
        
        return [
            'inNumRA' => $raNumber,
            'inDigitoRA' => isset($parts[1]) ? $parts[1] : '',
            'inSiglaUFRA' => 'SP' // Assumindo SP como padrão, pode ser configurável
        ];
    }
}