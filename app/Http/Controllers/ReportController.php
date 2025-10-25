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

    /**
     * Dados agregados de relatório de frequência.
     * Todos os filtros são arrays (mesmo selects simples no front devem enviar arrays).
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'school_codes' => 'array',
            'school_codes.*' => 'string',
            'class_codes' => 'array',
            'class_codes.*' => 'string',
            'discipline_ids' => 'array',
            'discipline_ids.*' => 'integer',
            'tipo_ensino' => 'array',
            'tipo_ensino.*' => 'string',
            'student_ras' => 'array',
            'student_ras.*' => 'string',
            'dates' => 'array',
            'dates.*' => 'date',
        ]);

        $query = AttendanceRecord::query()
            ->where('tenant_id', $tenantId);

        if (!empty($validated['school_codes'])) {
            $query->whereIn('school_code', $validated['school_codes']);
        }
        if (!empty($validated['class_codes'])) {
            $query->whereIn('class_code', $validated['class_codes']);
        }
        if (!empty($validated['discipline_ids'])) {
            $query->whereIn('discipline_id', $validated['discipline_ids']);
        }
        if (!empty($validated['tipo_ensino'])) {
            $query->whereIn('type_ensino', $validated['tipo_ensino']);
        }
        if (!empty($validated['student_ras'])) {
            $query->whereIn('student_ra', $validated['student_ras']);
        }
        if (!empty($validated['dates'])) {
            // Se datas específicas forem enviadas, filtrar por elas
            $query->whereIn('date', $validated['dates']);
        }

        $records = $query->get([
            'class_code',
            'class_name',
            'school_code',
            'school_name',
            'type_ensino',
            'date',
            'student_ra',
            'discipline_id',
            'status',
        ]);

        $total = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $justified = $records->where('status', 'justified')->count();

        // Agregação por data
        $byDate = $records->groupBy(function ($r) {
            return \Carbon\Carbon::parse($r->date)->toDateString();
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'justified' => $group->where('status', 'justified')->count(),
                'total' => $group->count(),
            ];
        })->values();

        // Agregação por turma
        $byClass = $records->groupBy('class_code')->map(function ($group) {
            return [
                'class_code' => $group->first()->class_code,
                'class_name' => $group->first()->class_name,
                'school_name' => $group->first()->school_name,
                'present' => $group->where('status', 'present')->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'justified' => $group->where('status', 'justified')->count(),
                'total' => $group->count(),
            ];
        })->values();

        // Agregação por disciplina (se houver)
        $byDiscipline = collect();
        $disciplineIds = $records->pluck('discipline_id')->filter()->unique()->values();
        if ($disciplineIds->isNotEmpty()) {
            $disciplines = Discipline::whereIn('id', $disciplineIds)->get()->keyBy('id');
            $byDiscipline = $records->whereNotNull('discipline_id')->groupBy('discipline_id')->map(function ($group, $id) use ($disciplines) {
                $d = $disciplines->get((int)$id);
                return [
                    'discipline_id' => (int)$id,
                    'discipline_name' => $d->name ?? null,
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'justified' => $group->where('status', 'justified')->count(),
                    'total' => $group->count(),
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'filters' => [
                'school_codes' => $validated['school_codes'] ?? [],
                'class_codes' => $validated['class_codes'] ?? [],
                'discipline_ids' => $validated['discipline_ids'] ?? [],
                'tipo_ensino' => $validated['tipo_ensino'] ?? [],
                'student_ras' => $validated['student_ras'] ?? [],
                'dates' => $validated['dates'] ?? [],
            ],
            'summary' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'justified' => $justified,
                'attendance_rate' => $total ? round(($present / $total) * 100, 1) : 0,
            ],
            'byDate' => $byDate,
            'byClass' => $byClass,
            'byDiscipline' => $byDiscipline,
        ]);
    }
}