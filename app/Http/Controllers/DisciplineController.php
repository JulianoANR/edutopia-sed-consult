<?php

namespace App\Http\Controllers;

use App\Models\Discipline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class DisciplineController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $selectedSchool = Session::get('selected_school');
        if (!$selectedSchool) {
            return redirect()->route('schools.index')->with('error', 'É necessário escolher uma escola para gerenciar disciplinas.');
        }

        return Inertia::render('Disciplines/Index', [
            'disciplines' => Discipline::orderBy('name')->get(),
            'selectedSchool' => $selectedSchool,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:disciplines,name',
            'code' => 'nullable|string|max:50|unique:disciplines,code',
        ], [
            'name.unique' => 'Já existe uma disciplina com esse nome.',
            'code.unique' => 'Já existe uma disciplina com esse código.',
        ]);
        $discipline = Discipline::create($validated);
        return redirect()->back()->with('success', 'Disciplina criada');
    }

    public function update(Request $request, Discipline $discipline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:disciplines,name,' . $discipline->id,
            'code' => 'nullable|string|max:50|unique:disciplines,code,' . $discipline->id,
        ], [
            'name.unique' => 'Já existe uma disciplina com esse nome.',
            'code.unique' => 'Já existe uma disciplina com esse código.',
        ]);
        $discipline->update($validated);
        return redirect()->back()->with('success', 'Disciplina atualizada');
    }

    public function destroy(Discipline $discipline)
    {
        $discipline->delete();
        return redirect()->back()->with('success', 'Disciplina removida');
    }
}