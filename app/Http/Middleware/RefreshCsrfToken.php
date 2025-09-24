<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RefreshCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se é uma requisição de login ou logout
        if ($this->shouldRefreshToken($request)) {
            $this->refreshCsrfToken($request);
        }
        
        $response = $next($request);
        
        // Adicionar header com novo token CSRF se necessário
        if ($this->shouldAddCsrfHeader($request)) {
            $response->headers->set('X-CSRF-TOKEN', csrf_token());
        }
        
        return $response;
    }

    /**
     * Determine if CSRF token should be refreshed
     */
    private function shouldRefreshToken(Request $request): bool
    {
        $refreshRoutes = [
            'login',
            'logout',
            'register',
            'password.email',
            'password.update',
            'verification.send'
        ];
        
        return in_array($request->route()?->getName(), $refreshRoutes) ||
               $request->is('login') ||
               $request->is('logout') ||
               $request->is('register');
    }

    /**
     * Determine if CSRF header should be added to response
     */
    private function shouldAddCsrfHeader(Request $request): bool
    {
        return $request->expectsJson() || 
               $request->wantsJson() ||
               $request->ajax();
    }

    /**
     * Refresh CSRF token
     */
    private function refreshCsrfToken(Request $request): void
    {
        try {
            // Regenerar token CSRF
            $request->session()->regenerateToken();
            
            // Garantir que o novo token seja definido
            Session::put('_token', csrf_token());
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to refresh CSRF token: ' . $e->getMessage());
        }
    }
}