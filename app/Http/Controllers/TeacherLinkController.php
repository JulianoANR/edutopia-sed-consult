<?php

namespace App\Http\Controllers;

use App\Models\TeacherClassDisciplineLink;
use App\Models\Discipline;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Session;
use App\Services\SedTurmasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class TeacherLinkController extends Controller
{
    public function index($schoolCode = null): Response|RedirectResponse
    {
        $selectedSchool = Session::get('selected_school');
        if (!$selectedSchool) {
            return redirect()->route('schools.index')->with('error', 'É necessário escolher uma escola para gerenciar os vínculos.');
        }
        
        $user = Auth::user();
        $tenantId = $user?->tenant_id;

        $links = TeacherClassDisciplineLink::with(['user','discipline'])->where('tenant_id', $tenantId)->where('school_code', $schoolCode)->orderBy('user_id')->get();
        $teachers = User::where('role', 'professor')->where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'email']);
        $disciplines = Discipline::where('tenant_id', $tenantId)->orderBy('name')->get(['id','name','code']);
        
        return Inertia::render('TeacherLinks/Index', compact('links','teachers','disciplines','selectedSchool'));
    }

    public function store(Request $request, SedTurmasService $sedTurmasService)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'class_code' => 'required|string',
            'discipline_id' => 'nullable|exists:disciplines,id',
            'full_access' => 'boolean',
            'school_code' => 'nullable|string',
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $dadosTurma = $sedTurmasService->consultarTurma($validated['class_code']);
            $validated['class_name'] = $dadosTurma['nome_turma'] ?? null;
            $validated['school_year'] = $dadosTurma['outAnoLetivo'] ?? null;
            $validated['school_code'] = $dadosTurma['outCodEscola'] ?? $validated['school_code'];

        } catch (\Throwable $e) {
            $validated['class_name'] = $validated['class_name'] ?? null;
            $validated['school_year'] = $validated['school_year'] ?? null;
        }

        // Verifica duplicidade antes de criar para retornar mensagem amigável
        $alreadyExists = TeacherClassDisciplineLink::where('tenant_id', $tenantId)
            ->where('user_id', $validated['user_id'])
            ->where('class_code', $validated['class_code'])
            ->where('discipline_id', $validated['discipline_id'])
            ->exists();
        if ($alreadyExists) {
            $profName = optional(User::find($validated['user_id']))->name ?? 'Professor';
            $discName = $validated['discipline_id'] ? (optional(Discipline::find($validated['discipline_id']))->name ?? 'Disciplina') : 'todas as disciplinas';
            $turma = $validated['class_name'] ?? $validated['class_code'];
            $year = $validated['school_year'] ?? null;
            $message = "Já existe um vínculo para {$profName} na turma {$turma}" . ($year ? " ({$year})" : '') . " com " . ($validated['discipline_id'] ? "a disciplina {$discName}" : $discName) . ".";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }
            return redirect()->back()->with('error', $message);
        }

        try {
            $validated['tenant_id'] = $tenantId;
            TeacherClassDisciplineLink::create($validated);
        } catch (QueryException $e) {
            // Trata violação de chave única com mensagem amigável
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null; // 1062 para MySQL duplicate
            if ($sqlState === '23000' || $driverCode === 1062 || str_contains($e->getMessage(), 'unique_teacher_class_discipline')) {
                $profName = optional(User::find($validated['user_id']))->name ?? 'Professor';
                $discName = $validated['discipline_id'] ? (optional(Discipline::find($validated['discipline_id']))->name ?? 'Disciplina') : 'todas as disciplinas';
                $turma = $validated['class_name'] ?? $validated['class_code'];
                $year = $validated['school_year'] ?? null;
                $message = "Já existe um vínculo para {$profName} na turma {$turma}" . ($year ? " ({$year})" : '') . " com " . ($validated['discipline_id'] ? "a disciplina {$discName}" : $discName) . ".";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 409);
                }
                return redirect()->back()->with('error', $message);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Vínculo criado');
    }

    public function update(Request $request, TeacherClassDisciplineLink $link, SedTurmasService $sedTurmasService)
    {
        $validated = $request->validate([
            'class_code' => 'required|string',
            'discipline_id' => 'nullable|exists:disciplines,id',
            'full_access' => 'boolean',
            'school_code' => 'nullable|string',
        ]);

        if (!empty($validated['class_code'])) {
            try {
                $dadosTurma = $sedTurmasService->consultarTurma($validated['class_code']);
                $validated['class_name'] = $dadosTurma['nome_turma'] ?? null;
                $validated['school_year'] = $dadosTurma['outAnoLetivo'] ?? null;
                $validated['school_code'] = $dadosTurma['outCodEscola'] ?? $validated['school_code'];

            } catch (\Throwable $e) {
                $validated['class_name'] = $link->class_name;
                $validated['school_year'] = $link->school_year;
            }
        }

        $tenantId = $link->tenant_id ?? $request->user()->tenant_id;

        // Verifica duplicidade antes de atualizar (ignorando o próprio registro)
        $alreadyExists = TeacherClassDisciplineLink::where('tenant_id', $tenantId)
            ->where('user_id', $link->user_id)
            ->where('class_code', $validated['class_code'])
            ->where('discipline_id', $validated['discipline_id'])
            ->where('id', '!=', $link->id)
            ->exists();
        if ($alreadyExists) {
            $profName = optional($link->user)->name ?? 'Professor';
            $discName = $validated['discipline_id'] ? (optional(Discipline::find($validated['discipline_id']))->name ?? 'Disciplina') : 'todas as disciplinas';
            $turma = $validated['class_name'] ?? $validated['class_code'];
            $year = $validated['school_year'] ?? null;
            $message = "Já existe um vínculo para {$profName} na turma {$turma}" . ($year ? " ({$year})" : '') . " com " . ($validated['discipline_id'] ? "a disciplina {$discName}" : $discName) . ".";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }
            return redirect()->back()->with('error', $message);
        }

        try {
            $link->update($validated);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            if ($sqlState === '23000' || $driverCode === 1062 || str_contains($e->getMessage(), 'unique_teacher_class_discipline')) {
                $profName = optional($link->user)->name ?? 'Professor';
                $discName = $validated['discipline_id'] ? (optional(Discipline::find($validated['discipline_id']))->name ?? 'Disciplina') : 'todas as disciplinas';
                $turma = $validated['class_name'] ?? $validated['class_code'];
                $year = $validated['school_year'] ?? null;
                $message = "Já existe um vínculo para {$profName} na turma {$turma}" . ($year ? " ({$year})" : '') . " com " . ($validated['discipline_id'] ? "a disciplina {$discName}" : $discName) . ".";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 409);
                }
                return redirect()->back()->with('error', $message);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Vínculo atualizado');
    }

    public function destroy(TeacherClassDisciplineLink $link)
    {
        $link->delete();
        return redirect()->back()->with('success', 'Vínculo removido');
    }
}