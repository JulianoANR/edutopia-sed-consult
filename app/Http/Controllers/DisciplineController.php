<?php

namespace App\Http\Controllers;

use App\Models\Discipline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class DisciplineController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $selectedSchool = Session::get('selected_school');
        if (!$selectedSchool) {
            return redirect()->route('schools.index')->with('error', 'É necessário escolher uma escola para gerenciar disciplinas.');
        }

        $tenantId = auth()->user()?->tenant_id;

        return Inertia::render('Disciplines/Index', [
            'disciplines' => Discipline::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'selectedSchool' => $selectedSchool,
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('disciplines', 'name')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('disciplines', 'code')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
        ], [
            'name.unique' => 'Já existe uma disciplina com esse nome neste tenant.',
            'code.unique' => 'Já existe uma disciplina com esse código neste tenant.',
        ]);
        
        $validated['tenant_id'] = $tenantId;
        $discipline = Discipline::create($validated);
        return redirect()->back()->with('success', 'Disciplina criada');
    }

    public function update(Request $request, Discipline $discipline)
    {
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('disciplines', 'name')->ignore($discipline->id)->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('disciplines', 'code')->ignore($discipline->id)->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
        ], [
            'name.unique' => 'Já existe uma disciplina com esse nome neste tenant.',
            'code.unique' => 'Já existe uma disciplina com esse código neste tenant.',
        ]);
        $validated['tenant_id'] = $tenantId;
        $discipline->update($validated);
        return redirect()->back()->with('success', 'Disciplina atualizada');
    }

    public function destroy(Discipline $discipline)
    {
        $discipline->delete();
        return redirect()->back()->with('success', 'Disciplina removida');
    }
}