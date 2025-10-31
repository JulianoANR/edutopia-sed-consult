<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', [], false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Capturar dados do usuário antes do logout
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        
        // Limpar caches específicos do usuário e da API SED
        $this->clearSedApiCache($userId);
        $this->clearUserRelatedCaches($userId);
        
        // Logout padrão do Laravel
        Auth::guard('web')->logout();

        // Invalidar sessão e regenerar token
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Não limpar cookies manualmente; o fluxo padrão acima é suficiente.
        
        // Limpar qualquer cache adicional do Laravel
        $this->clearLaravelCaches();

        return redirect()->route('login');
    }

    /**
     * Clear SED API cache
     */
    private function clearSedApiCache(?int $userId = null): void
    {
        try {
            // Usar o serviço SED API para limpeza completa
            $sedApiService = app(\App\Services\SedApiService::class);
            $sedApiService->clearAllCaches();
            
            if ($userId) {
                $sedApiService->clearUserCaches($userId);
            }
            
            \Illuminate\Support\Facades\Log::info('SED API cache cleared during logout', ['user_id' => $userId]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear SED API cache during logout: ' . $e->getMessage());
        }
    }

    /**
     * Clear user related caches
     */
    private function clearUserRelatedCaches(?int $userId = null): void
    {
        try {
            if ($userId) {
                // Limpar caches específicos do usuário
                $userCacheKeys = [
                    "user_data_{$userId}",
                    "user_permissions_{$userId}",
                    "user_sed_config_{$userId}",
                    "user_schools_{$userId}",
                    "user_classes_{$userId}",
                    "user_students_{$userId}"
                ];
                
                foreach ($userCacheKeys as $key) {
                    \Illuminate\Support\Facades\Cache::forget($key);
                }
            }
            
            \Illuminate\Support\Facades\Log::info('User related caches cleared during logout', ['user_id' => $userId]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear user caches during logout: ' . $e->getMessage());
        }
    }

    /**
     * Clear session cookies
     */
    private function clearSessionCookies(Request $request): void
    {
        try {
            // Limpar cookies de sessão
            $cookieName = config('session.cookie');
            
            if ($cookieName) {
                cookie()->queue(cookie()->forget($cookieName));
            }
            
            // Limpar cookie CSRF
            // cookie()->queue(cookie()->forget('XSRF-TOKEN'));
            
            // Limpar outros cookies relacionados à autenticação
            cookie()->queue(cookie()->forget('laravel_session'));
            cookie()->queue(cookie()->forget('remember_web'));
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear session cookies during logout: ' . $e->getMessage());
        }
    }

    /**
     * Clear Laravel framework caches
     */
    private function clearLaravelCaches(): void
    {
        try {
            // Limpar caches do framework
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            
            \Illuminate\Support\Facades\Log::info('Laravel framework caches cleared during logout');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear Laravel caches during logout: ' . $e->getMessage());
        }
    }

}
