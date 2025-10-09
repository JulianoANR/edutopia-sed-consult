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

        return Inertia::render('Classes/Attendance', [
            'classCode' => $classCode,
            'selectedSchool' => $selectedSchool,
            'today' => Carbon::today()->toDateString(),
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

        $userId = $request->user()->id;

        foreach ($validated['records'] as $rec) {
            if (empty($rec['status']) && empty($rec['note'])) {
                // Se ambos nulos, remover registro se existir
                AttendanceRecord::where('class_code', $classCode)
                    ->whereDate('date', $date)
                    ->where('student_ra', $rec['ra'])
                    ->delete();
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'class_code' => $classCode,
                    'date' => $date->toDateString(),
                    'student_ra' => $rec['ra'],
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