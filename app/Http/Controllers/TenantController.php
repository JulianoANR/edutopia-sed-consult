<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    /**
     * Listagem e gestão de Tenants (somente super admin via Gate manage-tenants).
     */
    public function index(Request $request): Response
    {
        // Como boa prática, não expor o campo de senha criptografada no frontend
        $tenants = Tenant::query()
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'diretoria_id' => $t->diretoria_id,
                    'municipio_id' => $t->municipio_id,
                    'rede_ensino_cod' => $t->rede_ensino_cod,
                    'sed_username' => $t->sed_username,
                    'status' => $t->status,
                    'last_validated_at' => optional($t->last_validated_at)->toDateTimeString(),
                    'has_password' => !empty($t->sed_password_encrypted),
                ];
            });

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Cria um novo tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'diretoria_id' => ['required', 'integer'],
            'municipio_id' => ['required', 'integer'],
            'rede_ensino_cod' => ['required', 'integer'],
            'sed_username' => ['required', 'string', 'max:255'],
            'sed_password' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $tenant = new Tenant();
        $tenant->name = $validated['name'];
        $tenant->diretoria_id = (int) $validated['diretoria_id'];
        $tenant->municipio_id = (int) $validated['municipio_id'];
        $tenant->rede_ensino_cod = (int) $validated['rede_ensino_cod'];
        $tenant->sed_username = $validated['sed_username'];
        $tenant->sed_password_encrypted = encrypt($validated['sed_password']);
        $tenant->status = $validated['status'] ?? 'active';
        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant criado com sucesso.');
    }

    /**
     * Atualiza um tenant existente.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'diretoria_id' => ['required', 'integer'],
            'municipio_id' => ['required', 'integer'],
            'rede_ensino_cod' => ['required', 'integer'],
            'sed_username' => ['required', 'string', 'max:255'],
            'sed_password' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $tenant->name = $validated['name'];
        $tenant->diretoria_id = (int) $validated['diretoria_id'];
        $tenant->municipio_id = (int) $validated['municipio_id'];
        $tenant->rede_ensino_cod = (int) $validated['rede_ensino_cod'];
        $tenant->sed_username = $validated['sed_username'];
        if (!empty($validated['sed_password'])) {
            $tenant->sed_password_encrypted = encrypt($validated['sed_password']);
        }
        $tenant->status = $validated['status'] ?? $tenant->status;
        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant atualizado com sucesso.');
    }

    /**
     * Remove um tenant.
     */
    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', 'Tenant removido com sucesso.');
    }
}