<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\SedApiException;
use Carbon\Carbon;

class SedApiService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $diretoriaId;
    private int $municipioId;
    private int $redeEnsinoCod;
    private string $tokenCacheKey = 'sed_api_token';
    private int $tokenExpirationBuffer = 300; // 5 minutes buffer before expiration

    public function __construct()
    {
        $this->baseUrl = config('sed.api.url');
        $this->redeEnsinoCod = config('sed.api.rede_ensino_cod');
        
        // Get user data from authenticated user
        $user = auth()->user();
        
        if ($user) {
            $this->username = $user->sed_username;
            $this->password = $user->sed_password;
            $this->diretoriaId = (int) $user->sed_diretoria_id;
            $this->municipioId = (int) $user->sed_municipio_id;
        } else {
            // Fallback to config if no user is authenticated (for testing purposes)
            $this->username = config('sed.api.username');
            $this->password = config('sed.api.password');
            $this->diretoriaId = config('sed.api.diretoria_id');
            $this->municipioId = config('sed.api.municipio_id');
        }
        
        // Validate required configuration
        if (empty($this->baseUrl)) {
            throw SedApiException::configurationError('SED API URL not configured');
        }
        if (empty($this->username)) {
            throw SedApiException::configurationError('SED API username not configured');
        }
        if (empty($this->password)) {
            throw SedApiException::configurationError('SED API password not configured');
        }
        if (empty($this->diretoriaId)) {
            throw SedApiException::configurationError('SED API diretoria ID not configured');
        }
        if (empty($this->municipioId)) {
            throw SedApiException::configurationError('SED API municipio ID not configured');
        }
        if (empty($this->redeEnsinoCod)) {
            throw SedApiException::configurationError('SED API rede ensino code not configured');
        }
    }

    /**
     * Get a valid authentication token
     * 
     * @return string
     * @throws SedApiException
     */
    public function getToken(): string
    {
        $cachedToken = Cache::get($this->tokenCacheKey);
            
        if ($cachedToken && $this->isTokenValid($cachedToken)) {
            return $cachedToken['token'];
        }

        return $this->authenticate();
    }

    /**
     * Authenticate with SED API and cache the token
     * Baseado na documentação NCA001_SEESP_Integracao_ValidarUsuario.pdf
     * 
     * @return string
     * @throws SedApiException
     */
    public function authenticate(): string
    {
        try {
            Log::info('SED API: Attempting authentication', ['username' => $this->username]);
            
            // Conforme documentação: GET /Usuario/ValidarUsuario com Basic Auth
            $response = Http::timeout(30)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . '/Usuario/ValidarUsuario');

            if (!$response->successful()) {
                throw new SedApiException(
                    'Authentication failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();
            
            // Verificar se há erro de negócio
            if (!empty($data['outErro'])) {
                throw new SedApiException('SED API Error: ' . $data['outErro']);
            }
            
            // Conforme documentação, o token vem em 'outAutenticacao'
            if (!isset($data['outAutenticacao']) || empty($data['outAutenticacao'])) {
                throw new SedApiException('Token not found in authentication response');
            }

            $tokenData = [
                'token' => $data['outAutenticacao'],
                'usuario' => $data['outUsuario'] ?? null,
                'request_id' => $data['outRequestID'] ?? null,
                'expires_at' => Carbon::now()->addMinutes(30), // Token válido por 30 minutos conforme doc
                'created_at' => Carbon::now(),
            ];

            // Cache token for 25 minutes (30 - 5 buffer)
            $cacheMinutes = 25;
            Cache::put($this->tokenCacheKey, $tokenData, now()->addMinutes($cacheMinutes));

            Log::info('SED API: Authentication successful', [
                'usuario' => $tokenData['usuario'],
                'request_id' => $tokenData['request_id'],
                'expires_at' => $tokenData['expires_at'],
                'cache_minutes' => $cacheMinutes
            ]);

            return $tokenData['token'];
            
        } catch (\Exception $e) {
            Log::error('SED API: Authentication failed', [
                'error' => $e->getMessage(),
                'username' => $this->username
            ]);
            
            throw new SedApiException(
                'Failed to authenticate with SED API: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Check if the cached token is still valid
     * 
     * @param array $tokenData
     * @return bool
     */
    private function isTokenValid(array $tokenData): bool
    {
        if (!isset($tokenData['expires_at'])) {
            return false;
        }

        $expiresAt = Carbon::parse($tokenData['expires_at']);
        $now = Carbon::now();
        
        // Check if token expires within the buffer time
        return $now->addSeconds($this->tokenExpirationBuffer)->isBefore($expiresAt);
    }

    /**
     * Check if there is a valid cached token
     * 
     * @return bool
     */
    public function hasValidToken(): bool
    {
        $cachedToken = Cache::get($this->tokenCacheKey);
        return $cachedToken && $this->isTokenValid($cachedToken);
    }


    /**
     * Make an authenticated HTTP GET request
     * 
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws SedApiException
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Make an authenticated HTTP POST request
     * 
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws SedApiException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Make an authenticated HTTP PUT request
     * 
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws SedApiException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data);
    }

    /**
     * Make an authenticated HTTP DELETE request
     * 
     * @param string $endpoint
     * @return array
     * @throws SedApiException
     */
    public function delete(string $endpoint): array
    {
        return $this->makeRequest('DELETE', $endpoint);
    }

    /**
     * Make an authenticated HTTP request
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws SedApiException
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $token = $this->getToken();
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
       
        try {
            Log::debug('SED API: Making request', [
                'method' => $method,
                'url' => $url,
                'data_keys' => array_keys($data)
            ]);

            $request = Http::timeout(30)
                ->withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            $response = match(strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new SedApiException('Unsupported HTTP method: ' . $method)
            };

            if (!$response->successful()) {
                // If unauthorized, clear token cache and retry once
                if ($response->status() === 401) {
                    Log::warning('SED API: Token expired, clearing cache and retrying');
                    Cache::forget($this->tokenCacheKey);
                    
                    // Retry with new token (only once to avoid infinite loop)
                    $newToken = $this->getToken();

                    $requestWithNewToken = Http::timeout(30)
                        ->withToken($newToken)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ]);

                    $response = match(strtoupper($method)) {
                        'GET' => $requestWithNewToken->get($url, $data),
                        'POST' => $requestWithNewToken->post($url, $data),
                        'PUT' => $requestWithNewToken->put($url, $data),
                        'DELETE' => $requestWithNewToken->delete($url),
                        default => throw new SedApiException('Unsupported HTTP method: ' . $method)
                    };
                    
                    if (!$response->successful()) {
                        throw new SedApiException(
                            'Request failed after token refresh: ' . $response->body(),
                            $response->status()
                        );
                    }
                } else {
                    throw new SedApiException(
                        'Request failed: ' . $response->body(),
                        $response->status()
                    );
                }
            }

            $responseData = $response->json();
            
            // Verificar erros de negócio da API SED
            if (!empty($responseData['Erro']) || !empty($responseData['outErro'])) {
                $errorMessage = $responseData['Mensagem'] ?? $responseData['outErro'] ?? 'Erro desconhecido da API';
                $errorId = $responseData['Erro'] ?? 'N/A';
                
                Log::error('SED API: Business error', [
                    'method' => $method,
                    'url' => $url,
                    'error_id' => $errorId,
                    'error_message' => $errorMessage,
                    'process_id' => $responseData['outProcessoID'] ?? null
                ]);
                
                throw SedApiException::businessError($errorMessage);
            }
            
            Log::debug('SED API: Request successful', [
                'method' => $method,
                'url' => $url,
                'status' => $response->status()
            ]);

            return $responseData ?? [];
            
        } catch (SedApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SED API: Request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            throw new SedApiException(
                'HTTP request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Clear cached token (useful for logout or manual refresh)
     * 
     * @return void
     */
    public function clearToken(): void
    {
        Cache::forget($this->tokenCacheKey);
        Log::info('SED API: Token cache cleared');
    }

    /**
     * Get token information from cache
     * 
     * @return array|null
     */
    public function getTokenInfo(): ?array
    {
        return Cache::get($this->tokenCacheKey);
    }


    /**
     * Testa a conexão com a API do SED
     * Usa o próprio endpoint de validação de usuário para testar conectividade
     */
    public function testConnection()
    {
        try {
            // Tenta obter um token válido - isso testa tanto conectividade quanto autenticação
            $token = $this->getToken();
            
            return [
                'success' => true,
                'message' => 'Conexão com SED estabelecida com sucesso',
                'data' => [
                    'token_length' => strlen($token),
                    'authenticated' => true,
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ]
            ];
        } catch (SedApiException $e) {
            Log::error('SED API: Connection test failed', [
                'error' => $e->getMessage(),
                'endpoint' => '/Usuario/ValidarUsuario'
            ]);
            
            return [
                'success' => false,
                'message' => 'Falha na conexão com SED: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('SED API: Connection test failed with unexpected error', [
                'error' => $e->getMessage(),
                'endpoint' => '/Usuario/ValidarUsuario'
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro inesperado na conexão com SED',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get the configured diretoria ID
     * 
     * @return int
     */
    public function getDiretoriaId(): int
    {
        return $this->diretoriaId;
    }

    /**
     * Get the configured municipio ID
     * 
     * @return int
     */
    public function getMunicipioId(): int
    {
        return $this->municipioId;
    }

    /**
     * Get the configured rede ensino code
     * 
     * @return int
     */
    public function getRedeEnsinoCod(): int
    {
        return $this->redeEnsinoCod;
    }

    // /**
    //  * Buscar classes de uma escola por ano letivo
    //  * Baseado na documentação RelacaoClasses da API SED
    //  * 
    //  * @param string $anoLetivo Ano letivo da pesquisa (4 dígitos)
    //  * @param string $codEscola Código da escola (7 dígitos)
    //  * @param string|null $codTipoEnsino Código do tipo de ensino (opcional)
    //  * @param string|null $codSerieAno Código da série/ano (opcional)
    //  * @param string|null $codTurno Código do turno (opcional)
    //  * @param string|null $semestre Semestre (opcional)
    //  * @return array
    //  * @throws SedApiException
    //  */
    // public function getRelacaoClasses(
    //     string $anoLetivo, 
    //     string $codEscola, 
    //     ?string $codTipoEnsino = null,
    //     ?string $codSerieAno = null,
    //     ?string $codTurno = null,
    //     ?string $semestre = null
    // ): array | string {
    //     try {
    //         // Validar parâmetros obrigatórios
    //         if (empty($anoLetivo) || strlen($anoLetivo) !== 4) {
    //             throw SedApiException::invalidParameter('Ano letivo deve ter 4 dígitos');
    //         }
            
    //         if (empty($codEscola)) {
    //             throw SedApiException::invalidParameter('Código da escola é obrigatório');
    //         }
            
    //         // Obter token de autenticação
    //         $token = $this->getToken();
            
    //         // Preparar parâmetros da query string (apenas não vazios)
    //         $queryParams = [
    //             'inAnoLetivo' => $anoLetivo,
    //             'inCodEscola' => $codEscola
    //         ];
            
    //         // Adicionar parâmetros opcionais apenas se não estiverem vazios
    //         if (!empty($codTipoEnsino)) {
    //             $queryParams['inCodTipoEnsino'] = $codTipoEnsino;
    //         }
    //         if (!empty($codSerieAno)) {
    //             $queryParams['inCodSerieAno'] = $codSerieAno;
    //         }
    //         // if (!empty($codTurno)) {
    //         //     $queryParams['inCodTurno'] = $codTurno;
    //         // }
    //         // if (!empty($semestre)) {
    //         //     $queryParams['inSemestre'] = $semestre;
    //         // }
            
    //         // Preparar body da requisição
    //         $requestBody = [
    //             'inAnoLetivo' => $anoLetivo,
    //             'inCodEscola' => $codEscola,
    //             // 'inCodTipoEnsino' => $codTipoEnsino ?? '',
    //             // 'inCodSerieAno' => $codSerieAno ?? '',
    //             // 'inCodTurno' => $codTurno ?? '',
    //             // 'inSemestre' => $semestre ?? ''
    //         ];
            
    //         // Construir URL com query parameters
    //         $url = $this->baseUrl . '/RelacaoAlunosClasse/RelacaoClasses?' . http_build_query($queryParams);
            
    //         // Fazer requisição POST para a API
    //         $response = Http::timeout(config('sed.api.timeout', 30))
    //             ->withHeaders([
    //                 'Authorization' => 'Bearer ' . $token,
    //                 'Content-Type' => 'application/json; charset=UTF-8'
    //             ])
    //             ->post($url, $requestBody);

    //         if (!$response->successful()) {
    //             throw SedApiException::requestFailed(
    //                 'Falha na requisição de classes da escola',
    //                 $response->status(),
    //                 $response->body()
    //             );
    //         }
            
    //         $data = $response->json();
            
    //         // Verificar se há erro de negócio
    //         if (!empty($data['outErro'])) {
    //             throw SedApiException::businessError($data['outErro']);
    //         }
            
    //         // Log da requisição bem-sucedida
    //         Log::info('SED API: Classes da escola obtidas com sucesso', [
    //             'ano_letivo' => $anoLetivo,
    //             'cod_escola' => $codEscola,
    //             'cod_tipo_ensino' => $codTipoEnsino,
    //             'cod_serie_ano' => $codSerieAno,
    //             'cod_turno' => $codTurno,
    //             'semestre' => $semestre,
    //             'total_classes' => count($data['outClasses'] ?? []),
    //             'processo_id' => $data['outProcessoID'] ?? null
    //         ]);
            
    //         return $data;
            
    //     } catch (SedApiException $e) {
    //         Log::error('SED API: Erro ao buscar classes da escola', [
    //             'error' => $e->getMessage(),
    //             'ano_letivo' => $anoLetivo,
    //             'cod_escola' => $codEscola,
    //             'endpoint' => '/RelacaoAlunosClasse/RelacaoClasses'
    //         ]);
    //         throw $e;
    //     } catch (\Exception $e) {
    //         Log::error('SED API: Erro inesperado ao buscar classes da escola', [
    //             'error' => $e->getMessage(),
    //             'ano_letivo' => $anoLetivo,
    //             'cod_escola' => $codEscola,
    //             'endpoint' => '/RelacaoAlunosClasse/RelacaoClasses'
    //         ]);
    //         throw SedApiException::unexpectedError('Erro inesperado ao buscar classes: ' . $e->getMessage());
    //     }
    // }


    /**
     * Clear all SED API related caches
     */
    public function clearAllCaches(): void
    {
        try {
            $cacheKeys = [
                $this->tokenCacheKey,
                'sed_api_diretorias',
                'sed_api_tipos_ensino',
                'sed_api_escolas_municipio',
                'sed_api_classes_' . $this->municipioId,
                'sed_api_students_' . $this->municipioId
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info('SED API: All caches cleared successfully');
        } catch (\Exception $e) {
            Log::warning('SED API: Failed to clear caches: ' . $e->getMessage());
        }
    }

    /**
     * Clear user-specific SED API caches
     */
    public function clearUserCaches(?int $userId = null): void
    {
        try {
            $userId = $userId ?? auth()->id();
            
            if (!$userId) {
                return;
            }
            
            $userCacheKeys = [
                "sed_api_user_token_{$userId}",
                "sed_api_user_data_{$userId}",
                "sed_api_user_schools_{$userId}",
                "sed_api_user_classes_{$userId}",
                "sed_api_user_students_{$userId}"
            ];
            
            foreach ($userCacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info('SED API: User specific caches cleared', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::warning('SED API: Failed to clear user caches: ' . $e->getMessage());
        }
    }

}