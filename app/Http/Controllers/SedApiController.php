<?php

namespace App\Http\Controllers;

use App\Services\SedApiService;
use App\Services\SedEscolasService;
use App\Services\SedTurmasService;
use App\Services\SedDadosBasicosService;
use App\Exceptions\SedApiException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SedApiController extends Controller
{
    protected SedApiService $sedApiService;
    protected SedEscolasService $sedEscolasService;
    protected SedTurmasService $sedTurmasService;
    protected SedDadosBasicosService $sedDadosBasicosService;

    public function __construct(
        SedApiService $sedApiService,
        SedEscolasService $sedEscolasService,
        SedTurmasService $sedTurmasService,
        SedDadosBasicosService $sedDadosBasicosService
    ) {
        $this->sedApiService = $sedApiService;
        $this->sedEscolasService = $sedEscolasService;
        $this->sedTurmasService = $sedTurmasService;
        $this->sedDadosBasicosService = $sedDadosBasicosService;
    }

    private function ensureTenant(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso indisponível devido à falta de cadastro SED',
            ], 403);
        }
        return null;
    }

    /**
     * Test the SED API connection
     * 
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            // First, test if we can instantiate the service
            $user = Auth::user();

            $tenant = $user ? $user->tenant : null;
            $config = [
                'url' => config('sed.api.url'),
                'username' => $tenant ? $tenant->sed_username : config('sed.api.username'),
                'has_password' => $tenant ? (!empty($tenant->sed_password_encrypted)) : (!empty(config('sed.api.password'))),
            ];
            
            // Check if configuration is loaded
            if (empty($config['url'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração SED_URL não encontrada',
                    'config_check' => $config,
                ], 500);
            }
            
            if (empty($config['username'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário SED não configurado no tenant',
                    'config_check' => $config,
                ], 500);
            }
            
            if (empty($config['has_password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha SED não configurada no tenant',
                    'config_check' => $config,
                ], 500);
            }
            
            // Test authentication
            $authResult = $this->sedApiService->authenticate();
            
            return response()->json([
                'success' => true,
                'message' => 'Conexão com a API SED estabelecida com sucesso',
                'config_check' => $config,
                'data' => [
                    'authenticated' => true,
                    'token_expires_at' => $authResult['expires_at'] ?? null,
                ]
            ]);
        } catch (SedApiException $e) {
            Log::error('SED API Connection Test Failed', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Falha na conexão com a API SED',
                'error' => $e->getMessage(),
                'config_check' => [
                    'url' => config('sed.api.url'),
                    'username' => ($user && $user->tenant)? ((!empty($user->tenant->sed_username))? 'SET' : 'NOT SET') : 'NOT SET',
                    'password' => ($user && $user->tenant)? ((!empty($user->tenant->sed_password_encrypted))? 'SET' : 'NOT SET') : 'NOT SET',
                ],
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error during SED API connection test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage(),
                'config_check' => [
                    'url' => config('sed.api.url'),
                    'username' => ($user && $user->tenant)? ((!empty($user->tenant->sed_username))? 'SET' : 'NOT SET') : 'NOT SET',
                    'password' => ($user && $user->tenant)? ((!empty($user->tenant->sed_password_encrypted))? 'SET' : 'NOT SET') : 'NOT SET',
                ],
            ], 500);
        }
    }

    /**
     * Get token status
     * 
     * @return JsonResponse
     */
    public function getTokenStatus(): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            $hasValidToken = $this->sedApiService->hasValidToken();
            $tokenInfo = $this->sedApiService->getTokenInfo();

            return response()->json([
                'success' => true,
                'data' => [
                    'has_token' => !empty($tokenInfo),
                    'is_valid' => $hasValidToken,
                    'token_preview' => $tokenInfo && isset($tokenInfo['token']) ? substr($tokenInfo['token'], 0, 10) . '...' : null,
                    'expires_at' => $tokenInfo['expires_at'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status do token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear the current token
     * 
     * @return JsonResponse
     */
    public function clearToken(): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            $this->sedApiService->clearToken();

            return response()->json([
                'success' => true,
                'message' => 'Token removido com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar escolas por município e rede de ensino
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getEscolasPorMunicipio(Request $request): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            // Obter parâmetros da requisição
            $codDiretoria = $request->query('diretoria_id', null);
            $codMunicipio = $request->query('municipio_id', null);
            $codRedeEnsino = $request->query('rede_ensino', 2); // Padrão: Estadual
            
            // Converter para inteiros se fornecidos
            $codDiretoria = $codDiretoria ? (int) $codDiretoria : null;
            $codMunicipio = $codMunicipio ? (int) $codMunicipio : null;
            $codRedeEnsino = (int) $codRedeEnsino;
            
            // Chamar o serviço
            $result = $this->sedEscolasService->getEscolasPorMunicipio($codDiretoria, $codMunicipio, $codRedeEnsino);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Escolas obtidas com sucesso',
                'total_escolas' => count($result['outEscolas'] ?? []),
            ]);
            
        } catch (SedApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar escolas por município na API SED',
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error while fetching schools by municipality', [
                'error' => $e->getMessage(),
                'diretoria_id' => $codDiretoria ?? null,
                'municipio_id' => $codMunicipio ?? null,
                'rede_ensino' => $codRedeEnsino ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar escolas por município',
            ], 500);
        }
    }

    /**
     * Consultar informações de uma turma específica
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function consultarTurma(Request $request): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            // Validar parâmetro obrigatório
            $request->validate([
                'inNumClasse' => 'required|string|max:50'
            ]);
            
            $inNumClasse = $request->input('inNumClasse');
            
            // Chamar o serviço
            $result = $this->sedTurmasService->consultarTurma($inNumClasse);

            $name = getNomeTurma($result['outCodTipoEnsino'], $result['outCodSerieAno']);

            if (isset($result['outTurma'])) {
                $name .= ' ' . strtoupper($result['outTurma']);
            }

            if (isset($result['outDescricaoTurno'])) {
                $name .= ' - ' . $result['outDescricaoTurno'];
            }

            $result['name'] = $name;

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Turma consultada com sucesso',
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (SedApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar turma na API SED',
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        } catch (\Exception $e) {
            Log::error('Unexpected error while consulting class', [
                'error' => $e->getMessage(),
                'inNumClasse' => $request->input('inNumClasse'),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao consultar turma',
            ], 500);
        }
    }

    /**
     * Buscar lista de diretorias de ensino estaduais
     * Endpoint: GET /sed-api/diretorias
     * 
     * @return JsonResponse
     */
    public function getDiretorias(): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            $diretorias = $this->sedDadosBasicosService->getDiretorias();
            
            return response()->json([
                'success' => true,
                'data' => $diretorias,
                'message' => 'Diretorias obtidas com sucesso'
            ]);
            
        } catch (SedApiException $e) {
            Log::error('SED API Controller: Erro ao buscar diretorias', [
                'error' => $e->getMessage(),
                'http_code' => $e->getHttpStatusCode()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar diretorias',
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
            
        } catch (\Exception $e) {
            Log::error('Unexpected error while fetching diretorias', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar diretorias',
            ], 500);
        }
    }

    /**
     * Buscar tipos de ensino
     * Endpoint: GET /sed-api/tipo-ensino
     * 
     * @return JsonResponse
     */
    public function getTipoEnsino(): JsonResponse
    {
        $guard = $this->ensureTenant();
        if ($guard) {
            return $guard;
        }
        try {
            $tipoEnsino = $this->sedDadosBasicosService->getTipoEnsino();
            
            return response()->json([
                'success' => true,
                'data' => $tipoEnsino,
                'message' => 'Tipos de ensino obtidos com sucesso'
            ]);
            
        } catch (SedApiException $e) {
            Log::error('SED API Controller: Erro ao buscar tipos de ensino', [
                'error' => $e->getMessage(),
                'http_code' => $e->getHttpStatusCode()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de ensino',
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
            
        } catch (\Exception $e) {
            Log::error('Unexpected error while fetching tipos de ensino', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => 'Erro inesperado ao buscar tipos de ensino',
            ], 500);
        }
    }
}