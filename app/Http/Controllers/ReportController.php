<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Discipline;
use App\Services\SedEscolasService;
use App\Services\SedTurmasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected SedTurmasService $sedTurmasService;
    protected SedEscolasService $sedEscolasService;

    public function __construct(SedTurmasService $sedTurmasService, SedEscolasService $sedEscolasService)
    {
        $this->sedTurmasService = $sedTurmasService;
        $this->sedEscolasService = $sedEscolasService;
    }

    /**
     * Página de frequência por turma.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        $schools = $this->sedEscolasService->getEscolasPorMunicipio();
        $disciplines = Discipline::all();


        $tiposEnsino = [
            'Educação Básica',
            'Educação Profissional',
            'Textos de teste'
        ];
      
        return Inertia::render('Reports/Index', [
            'schools' => $schools['outEscolas'] ?? [],
            'disciplines' => $disciplines,
            'tiposEnsino' => $tiposEnsino,
        ]);
    }
}