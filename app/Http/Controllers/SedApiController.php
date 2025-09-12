<?php

namespace App\Http\Controllers;

use App\Services\SedApiService;
use App\Exceptions\SedApiException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SedApiController extends Controller
{
    protected SedApiService $sedApiService;

    public function __construct(SedApiService $sedApiService)
    {
        $this->sedApiService = $sedApiService;
    }

    /**
     * Test the SED API connection
     * 
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        try {
            // First, test if we can instantiate the service
            $config = [
                'url' => config('sed.api.url'),
                'username' => config('sed.api.username'),
                'password' => config('sed.api.password'),
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
                    'message' => 'Configuração SED_USERNAME não encontrada',
                    'config_check' => $config,
                ], 500);
            }
            
            if (empty($config['password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuração SED_PASSWORD não encontrada',
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
                    'username' => config('sed.api.username') ? 'SET' : 'NOT SET',
                    'password' => config('sed.api.password') ? 'SET' : 'NOT SET',
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
                    'username' => config('sed.api.username') ? 'SET' : 'NOT SET',
                    'password' => config('sed.api.password') ? 'SET' : 'NOT SET',
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
        try {
            $isValid = $this->sedApiService->isTokenValid();
            $token = $this->sedApiService->getToken();

            return response()->json([
                'success' => true,
                'data' => [
                    'has_token' => !empty($token),
                    'is_valid' => $isValid,
                    'token_preview' => $token ? substr($token, 0, 10) . '...' : null,
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
            $result = $this->sedApiService->getEscolasPorMunicipio($codDiretoria, $codMunicipio, $codRedeEnsino);
            
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
        try {
            // Validar parâmetro obrigatório
            $request->validate([
                'inNumClasse' => 'required|string|max:50'
            ]);
            
            $inNumClasse = $request->input('inNumClasse');
            
            // Chamar o serviço
            $result = $this->sedApiService->consultarTurma($inNumClasse);

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
}