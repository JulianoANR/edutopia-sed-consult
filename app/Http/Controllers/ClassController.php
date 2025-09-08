<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    /**
     * Display the specified class.
     */
    public function show(Request $request, string $classCode): Response|RedirectResponse
    {
        // Recupera a escola selecionada da sessão
        $selectedSchool = session('selected_school');
           
        if (!$selectedSchool) {
            return redirect()->route('schools.index')
                ->with('error', 'Você precisa selecionar uma escola primeiro.');
        }
        
        return Inertia::render('Classes/Show', [
            'classCode' => $classCode,
            'selectedSchool' => $selectedSchool,
        ]);
    }
}