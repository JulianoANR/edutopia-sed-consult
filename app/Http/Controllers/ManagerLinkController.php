<?php

namespace App\Http\Controllers;

use App\Models\ManagerSchoolLink;
use App\Models\User;
use App\Services\SedEscolasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Inertia\Response;

class ManagerLinkController extends Controller
{
    protected SedEscolasService $sedEscolasService;

    public function __construct(SedEscolasService $sedEscolasService)
    {
        $this->sedEscolasService = $sedEscolasService;
    }

    public function index($schoolCode = null): Response|RedirectResponse
    {
        // Permitir uso da tela mesmo sem escola selecionada
        $selectedSchool = Session::get('selected_school');

        $user = Auth::user();
        $tenantId = $user?->tenant_id;
        $schools = $this->sedEscolasService->getEscolasPorMunicipio();

        $links = ManagerSchoolLink::with(['user'])
            ->where('tenant_id', $tenantId)
            ->when($schoolCode, fn($q) => $q->where('school_code', $schoolCode))
            ->orderBy('user_id')
            ->get();

        // Buscar usuários com role 'gestor' via user_roles (multi-role)
        $gestors = User::where('tenant_id', $tenantId)
            ->whereHas('roleLinks', function ($q) { $q->where('role', 'gestor'); })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('ManagerLinks/Index', compact(
            'links', 
            'gestors', 
            'selectedSchool',
            'schools'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'school_code' => 'required|string',
            'school_name' => 'required|string',
        ]);

        $tenantId = $request->user()->tenant_id;

        // Verifica duplicidade antes de criar para retornar mensagem amigável
        $alreadyExists = ManagerSchoolLink::where('tenant_id', $tenantId)
            ->where('user_id', $validated['user_id'])
            ->where('school_code', $validated['school_code'])
            ->exists();
        if ($alreadyExists) {
            $gestorName = optional(User::find($validated['user_id']))->name ?? 'Gestor';
            $message = "Já existe um vínculo de {$gestorName} com esta escola.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }
            return redirect()->back()->with('error', $message);
        }

        try {
            $validated['tenant_id'] = $tenantId;
            ManagerSchoolLink::create($validated);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null; // 1062 para MySQL duplicate
            if ($sqlState === '23000' || $driverCode === 1062 || str_contains($e->getMessage(), 'unique_manager_school')) {
                $gestorName = optional(User::find($validated['user_id']))->name ?? 'Gestor';
                $message = "Já existe um vínculo de {$gestorName} com esta escola.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 409);
                }
                return redirect()->back()->with('error', $message);
            }
            throw $e;
        }

        return redirect()->back()->with('success', 'Vínculo criado');
    }

    public function destroy(ManagerSchoolLink $link)
    {
        $link->delete();
        return redirect()->back()->with('success', 'Vínculo removido');
    }
}