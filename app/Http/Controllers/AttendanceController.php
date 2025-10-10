<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\SedTurmasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected SedTurmasService $sedTurmasService;

    public function __construct(SedTurmasService $sedTurmasService)
    {
        $this->sedTurmasService = $sedTurmasService;
    }

    /**
     * Página de frequência por turma.
     */
    public function show(Request $request, string $classCode): Response|RedirectResponse
    {
        $selectedSchool = session('selected_school');
        if (!$selectedSchool) {
            return redirect()->route('schools.index')
                ->with('error', 'Você precisa selecionar uma escola primeiro.');
        }

        // Disciplinas disponíveis para o usuário nesta turma
        $user = $request->user();
        $links = \App\Models\TeacherClassDisciplineLink::with('discipline')
            ->where('user_id', $user->id)
            ->where('class_code', $classCode)
            ->get();

        if ($links->isEmpty()) {
            return redirect()->route('classes.show', $classCode)
                ->with('error', 'Você não tem acesso a esta turma.');
        }

        // Se tiver acesso total à turma, listar todas as disciplinas do sistema
        if ($links->contains(fn($l) => (bool)$l->full_access)) {
            $availableDisciplines = \App\Models\Discipline::orderBy('name')
                ->get(['id','name','code'])
                ->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code])
                ->values();
        } else {
            // Caso contrário, listar apenas as disciplinas vinculadas
            $availableDisciplines = $links
                ->map(function($link){
                    return [
                        'id' => $link->discipline?->id,
                        'name' => $link->discipline?->name ?? '',
                        'code' => $link->discipline?->code,
                    ];
                })
                ->filter(fn($d) => !empty($d['id']))
                ->unique('id')
                ->values();
        }

        return Inertia::render('Classes/Attendance', [
            'classCode' => $classCode,
            'selectedSchool' => $selectedSchool,
            'today' => Carbon::today()->toDateString(),
            'disciplines' => $availableDisciplines,
        ]);
    }

    /**
     * Dados de frequência para uma data.
     */
    public function getAttendance(Request $request, string $classCode): JsonResponse
    {
        $date = $request->query('date');
        if (!$date) {
            $date = Carbon::today()->toDateString();
        }

        $disciplineId = $request->query('discipline_id');
        // Validar acesso do professor à disciplina/turma via TeacherClassDisciplineLink
        $user = $request->user();
        $links = \App\Models\TeacherClassDisciplineLink::where('user_id', $user->id)
            ->where('class_code', $classCode)
            ->get();
        if ($links->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Você não tem acesso a esta turma.'], 403);
        }
        $hasFullAccess = $links->contains(fn($l) => (bool)$l->full_access);
        if (!$hasFullAccess) {
            if (empty($disciplineId)) {
                return response()->json(['success' => false, 'message' => 'Disciplina obrigatória para consultar frequência.'], 422);
            }
            $allowedDisciplineIds = $links->pluck('discipline_id')->filter()->unique()->values();
            if (!$allowedDisciplineIds->contains((int)$disciplineId)) {
                return response()->json(['success' => false, 'message' => 'Você não tem acesso a esta disciplina nesta turma.'], 403);
            }
        }

        // Buscar dados da turma e alunos via SED
        $classData = $this->sedTurmasService->consultarTurma($classCode);

        $alunos = $classData['outAlunos'] ?? [];

        // Mapear RA completo e nome
        $students = array_map(function ($aluno) {
            $ra = $aluno['outNumRA'] . '-' . ($aluno['outDigitoRA'] ?? '');
            return [
                'ra' => $ra,
                'name' => $aluno['outNomeAluno'] ?? '',
                'number' => $aluno['outNumAluno'] ?? null,
            ];
        }, $alunos);

        // Buscar registros existentes no banco
        $existing = AttendanceRecord::where('class_code', $classCode)
            ->when($disciplineId, fn($q) => $q->where('discipline_id', $disciplineId))
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_ra');

        // Construir resposta combinando base SED com registros locais
        $records = array_map(function ($student) use ($existing) {
            $ra = $student['ra'];
            $record = $existing->get($ra);
            return [
                'ra' => $student['ra'],
                'name' => $student['name'],
                'number' => $student['number'],
                'status' => $record->status ?? null,
                'note' => $record->note ?? null,
            ];
        }, $students);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'class' => [
                    'code' => $classCode,
                    'name' => $classData['nome_turma'] ?? null,
                    'shift' => $classData['outDescricaoTurno'] ?? null,
                    'school' => $classData['outDescNomeAbrevEscola'] ?? null,
                ],
                'students' => $records,
                'editable' => Carbon::parse($date)->isToday(),
            ]
        ]);
    }

    /**
     * Salvar/atualizar frequência de uma turma para uma data.
     */
    public function saveAttendance(Request $request, string $classCode): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'discipline_id' => 'nullable|exists:disciplines,id',
            'records' => 'required|array',
            'records.*.ra' => 'required|string',
            'records.*.status' => 'nullable|in:present,absent,justified',
            'records.*.note' => 'nullable|string',
        ]);

        $date = Carbon::parse($validated['date']);
        if (!$date->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Edição permitida apenas no dia atual.'
            ], 403);
        }

        $disciplineId = $validated['discipline_id'] ?? null;
        // Validar acesso do professor à disciplina/turma via TeacherClassDisciplineLink
        $user = $request->user();
        $links = \App\Models\TeacherClassDisciplineLink::where('user_id', $user->id)
            ->where('class_code', $classCode)
            ->get();
        if ($links->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Você não tem acesso a esta turma.'], 403);
        }
        $hasFullAccess = $links->contains(fn($l) => (bool)$l->full_access);
        if (!$hasFullAccess) {
            if (empty($disciplineId)) {
                return response()->json(['success' => false, 'message' => 'Disciplina obrigatória para salvar frequência.'], 422);
            }
            $allowedDisciplineIds = $links->pluck('discipline_id')->filter()->unique()->values();
            if (!$allowedDisciplineIds->contains((int)$disciplineId)) {
                return response()->json(['success' => false, 'message' => 'Você não tem acesso a esta disciplina nesta turma.'], 403);
            }
        }

        $userId = $user->id;

        foreach ($validated['records'] as $rec) {
            if (empty($rec['status']) && empty($rec['note'])) {
                // Se ambos nulos, remover registro se existir
                AttendanceRecord::where('class_code', $classCode)
                    ->whereDate('date', $date)
                    ->where('student_ra', $rec['ra'])
                    ->when($disciplineId, fn($q) => $q->where('discipline_id', $disciplineId))
                    ->delete();
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'class_code' => $classCode,
                    'date' => $date->toDateString(),
                    'student_ra' => $rec['ra'],
                    'discipline_id' => $disciplineId,
                ],
                [
                    'status' => $rec['status'],
                    'note' => $rec['note'] ?? null,
                    'user_id' => $userId,
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Frequência salva com sucesso.']);
    }
}