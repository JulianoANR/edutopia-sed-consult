<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ClearUserCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se houve mudança de usuário
        $this->checkUserChange($request);
        
        return $next($request);
    }

    /**
     * Check if user has changed and clear caches if necessary
     */
    private function checkUserChange(Request $request): void
    {
        try {
            $currentUser = Auth::user();
            $sessionUserId = $request->session()->get('current_user_id');
            
            if ($currentUser) {
                $currentUserId = $currentUser->id;
                
                // Se há um usuário diferente na sessão, limpar caches
                if ($sessionUserId && $sessionUserId !== $currentUserId) {
                    $this->clearAllUserCaches($sessionUserId);
                    $this->clearSedApiCache();
                    
                    Log::info('User changed detected, caches cleared', [
                        'previous_user' => $sessionUserId,
                        'current_user' => $currentUserId
                    ]);
                }
                
                // Atualizar ID do usuário na sessão
                $request->session()->put('current_user_id', $currentUserId);
            } else {
                // Se não há usuário logado mas há ID na sessão, limpar tudo
                if ($sessionUserId) {
                    $this->clearAllUserCaches($sessionUserId);
                    $this->clearSedApiCache();
                    $request->session()->forget('current_user_id');
                    
                    Log::info('User logged out detected, caches cleared', [
                        'previous_user' => $sessionUserId
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error in ClearUserCache middleware: ' . $e->getMessage());
        }
    }

    /**
     * Clear all caches related to a specific user
     */
    private function clearAllUserCaches(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        try {
            $userCacheKeys = [
                "user_data_{$userId}",
                "user_permissions_{$userId}",
                "user_sed_config_{$userId}",
                "user_schools_{$userId}",
                "user_classes_{$userId}",
                "user_students_{$userId}"
            ];
            
            foreach ($userCacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info('User specific caches cleared', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::warning('Failed to clear user caches: ' . $e->getMessage());
        }
    }

    /**
     * Clear SED API related caches
     */
    private function clearSedApiCache(): void
    {
        try {
            $sedCacheKeys = [
                'sed_api_token',
                'sed_api_diretorias',
                'sed_api_tipos_ensino',
                'sed_api_escolas_municipio'
            ];
            
            foreach ($sedCacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info('SED API caches cleared');
        } catch (\Exception $e) {
            Log::warning('Failed to clear SED API caches: ' . $e->getMessage());
        }
    }
}