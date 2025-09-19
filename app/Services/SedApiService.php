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
        $this->username = config('sed.api.username');
        $this->password = config('sed.api.password');
        $this->diretoriaId = config('sed.api.diretoria_id');
        $this->municipioId = config('sed.api.municipio_id');
        $this->redeEnsinoCod = config('sed.api.rede_ensino_cod');
        
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
     * Calculate token expiration time
     * 
     * @param array $authResponse
     * @return Carbon
     */
    private function calculateTokenExpiration(array $authResponse): Carbon
    {
        // If API provides expires_in (seconds)
        if (isset($authResponse['expires_in'])) {
            return Carbon::now()->addSeconds($authResponse['expires_in']);
        }
        
        // If API provides expires_at timestamp
        if (isset($authResponse['expires_at'])) {
            return Carbon::parse($authResponse['expires_at']);
        }
        
        // Default to 1 hour if no expiration info provided
        return Carbon::now()->addHour();
    }

    /**
     * Get cache duration in minutes (with buffer)
     * 
     * @param array $tokenData
     * @return int
     */
    private function getTokenCacheDuration(array $tokenData): int
    {
        $expiresAt = Carbon::parse($tokenData['expires_at']);
        $now = Carbon::now();
        
        $totalMinutes = $now->diffInMinutes($expiresAt);
        $bufferMinutes = intval($this->tokenExpirationBuffer / 60);
        
        return max(1, $totalMinutes - $bufferMinutes);
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

            return $response->json();

            if (!$response->successful()) {
                // If unauthorized, clear token cache and retry once
                if ($response->status() === 401) {
                    Log::warning('SED API: Token expired, clearing cache and retrying');
                    Cache::forget($this->tokenCacheKey);
                    
                    // Retry with new token (only once to avoid infinite loop)
                    $newToken = $this->getToken();
                    $response = $request->withToken($newToken)->send($method, $url, $data);
                    
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
     * Check if service is properly configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->username) && !empty($this->password);
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

    /**
     * Buscar classes de uma escola por ano letivo
     * Baseado na documentação RelacaoClasses da API SED
     * 
     * @param string $anoLetivo Ano letivo da pesquisa (4 dígitos)
     * @param string $codEscola Código da escola (7 dígitos)
     * @param string|null $codTipoEnsino Código do tipo de ensino (opcional)
     * @param string|null $codSerieAno Código da série/ano (opcional)
     * @param string|null $codTurno Código do turno (opcional)
     * @param string|null $semestre Semestre (opcional)
     * @return array
     * @throws SedApiException
     */
    public function getRelacaoClasses(
        string $anoLetivo, 
        string $codEscola, 
        ?string $codTipoEnsino = null,
        ?string $codSerieAno = null,
        ?string $codTurno = null,
        ?string $semestre = null
    ): array | string {
        try {
            // Validar parâmetros obrigatórios
            if (empty($anoLetivo) || strlen($anoLetivo) !== 4) {
                throw SedApiException::invalidParameter('Ano letivo deve ter 4 dígitos');
            }
            
            if (empty($codEscola)) {
                throw SedApiException::invalidParameter('Código da escola é obrigatório');
            }
            
            // Obter token de autenticação
            $token = $this->getToken();
            
            // Preparar parâmetros da query string (apenas não vazios)
            $queryParams = [
                'inAnoLetivo' => $anoLetivo,
                'inCodEscola' => $codEscola
            ];
            
            // Adicionar parâmetros opcionais apenas se não estiverem vazios
            if (!empty($codTipoEnsino)) {
                $queryParams['inCodTipoEnsino'] = $codTipoEnsino;
            }
            if (!empty($codSerieAno)) {
                $queryParams['inCodSerieAno'] = $codSerieAno;
            }
            // if (!empty($codTurno)) {
            //     $queryParams['inCodTurno'] = $codTurno;
            // }
            // if (!empty($semestre)) {
            //     $queryParams['inSemestre'] = $semestre;
            // }
            
            // Preparar body da requisição
            $requestBody = [
                'inAnoLetivo' => $anoLetivo,
                'inCodEscola' => $codEscola,
                // 'inCodTipoEnsino' => $codTipoEnsino ?? '',
                // 'inCodSerieAno' => $codSerieAno ?? '',
                // 'inCodTurno' => $codTurno ?? '',
                // 'inSemestre' => $semestre ?? ''
            ];
            
            // Construir URL com query parameters
            $url = $this->baseUrl . '/RelacaoAlunosClasse/RelacaoClasses?' . http_build_query($queryParams);
            
            // Fazer requisição POST para a API
            $response = Http::timeout(config('sed.api.timeout', 30))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json; charset=UTF-8'
                ])
                ->post($url, $requestBody);

            if (!$response->successful()) {
                throw SedApiException::requestFailed(
                    'Falha na requisição de classes da escola',
                    $response->status(),
                    $response->body()
                );
            }
            
            $data = $response->json();
            
            // Verificar se há erro de negócio
            if (!empty($data['outErro'])) {
                throw SedApiException::businessError($data['outErro']);
            }
            
            // Log da requisição bem-sucedida
            Log::info('SED API: Classes da escola obtidas com sucesso', [
                'ano_letivo' => $anoLetivo,
                'cod_escola' => $codEscola,
                'cod_tipo_ensino' => $codTipoEnsino,
                'cod_serie_ano' => $codSerieAno,
                'cod_turno' => $codTurno,
                'semestre' => $semestre,
                'total_classes' => count($data['outClasses'] ?? []),
                'processo_id' => $data['outProcessoID'] ?? null
            ]);
            
            return $data;
            
        } catch (SedApiException $e) {
            Log::error('SED API: Erro ao buscar classes da escola', [
                'error' => $e->getMessage(),
                'ano_letivo' => $anoLetivo,
                'cod_escola' => $codEscola,
                'endpoint' => '/RelacaoAlunosClasse/RelacaoClasses'
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SED API: Erro inesperado ao buscar classes da escola', [
                'error' => $e->getMessage(),
                'ano_letivo' => $anoLetivo,
                'cod_escola' => $codEscola,
                'endpoint' => '/RelacaoAlunosClasse/RelacaoClasses'
            ]);
            throw SedApiException::unexpectedError('Erro inesperado ao buscar classes: ' . $e->getMessage());
        }
    }

    /**
     * Buscar escolas por município e rede de ensino
     * Baseado na documentação NCA102 - EscolasPorMunicipio v1.1
     * 
     * @param int|null $codDiretoria Código da diretoria (opcional)
     * @param int|null $codMunicipio Código DNE do município
     * @param int $codRedeEnsino Código da rede de ensino (1-Estadual, 2-Municipal, 3-Privada, 4-Federal, 5-Estadual Outros)
     * @return array
     * @throws SedApiException
     */
    public function getEscolasPorMunicipio(?int $codDiretoria = null, ?int $codMunicipio = null, ?int $codRedeEnsino = null): array
    {
        try {
            // Usar valores padrão se não fornecidos
            $codDiretoria = $codDiretoria ?? $this->diretoriaId;
            $codMunicipio = $codMunicipio ?? $this->municipioId;
            $codRedeEnsino = $codRedeEnsino ?? $this->redeEnsinoCod;
            
            // Validar parâmetros obrigatórios
            if (empty($codMunicipio)) {
                throw SedApiException::invalidParameter('Código do município é obrigatório');
            }
            
            if (!in_array($codRedeEnsino, [1, 2, 3, 4, 5])) {
                throw SedApiException::invalidParameter('Código da rede de ensino deve ser 1-5');
            }
            
            // Obter token de autenticação
            $token = $this->getToken();
            
            // Preparar parâmetros da query
            $queryParams = [
                'inCodMunicipio' => $codMunicipio,
                'inCodRedeEnsino' => $codRedeEnsino
            ];
            
            // Adicionar diretoria se fornecida
            if (!empty($codDiretoria)) {
                $queryParams['inCodDiretoria'] = $codDiretoria;
            }
            
            // Fazer requisição para a API
            $response = Http::timeout(config('sed.api.timeout', 30))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json; charset=UTF-8'
                ])
                ->get($this->baseUrl . '/DadosBasicos/EscolasPorMunicipio', $queryParams);
            
            if (!$response->successful()) {
                throw SedApiException::requestFailed(
                    'Falha na requisição de escolas por município',
                    $response->status(),
                    $response->body()
                );
            }
            
            $data = $response->json();
            
            // Verificar se há erro de negócio
            if (!empty($data['outErro'])) {
                throw SedApiException::businessError($data['outErro']);
            }
            
            // Log da requisição bem-sucedida
            Log::info('SED API: Escolas por município obtidas com sucesso', [
                'municipio' => $codMunicipio,
                'diretoria' => $codDiretoria,
                'rede_ensino' => $codRedeEnsino,
                'total_escolas' => count($data['outEscolas'] ?? []),
                'processo_id' => $data['outProcessoID'] ?? null
            ]);
            
            return $data;
            
        } catch (SedApiException $e) {
            Log::error('SED API: Erro ao buscar escolas por município', [
                'error' => $e->getMessage(),
                'municipio' => $codMunicipio ?? null,
                'diretoria' => $codDiretoria ?? null,
                'rede_ensino' => $codRedeEnsino,
                'endpoint' => '/DadosBasicos/EscolasPorMunicipio'
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SED API: Erro inesperado ao buscar escolas por município', [
                'error' => $e->getMessage(),
                'municipio' => $codMunicipio ?? null,
                'diretoria' => $codDiretoria ?? null,
                'rede_ensino' => $codRedeEnsino,
                'endpoint' => '/DadosBasicos/EscolasPorMunicipio'
            ]);
            throw SedApiException::unexpectedError('Erro inesperado ao buscar escolas: ' . $e->getMessage());
        }
    }

    /**
     * Consultar informações de uma turma específica
     * 
     * @param string $inNumClasse Número da classe/turma
     * @return array
     * @throws SedApiException
     */
    public function consultarTurma(string $inNumClasse): array | string
    {
        try {
            // Validar parâmetro obrigatório
            if (empty($inNumClasse)) {
                throw SedApiException::invalidParameter('Número da classe é obrigatório');
            }
            
            // Obter token de autenticação
            $token = $this->getToken();
                
            // Preparar dados da requisição
            $requestData = [
                'inNumClasse' => $inNumClasse
            ];
            
            // Fazer requisição POST para a API
            $response = Http::timeout(config('sed.api.timeout', 30))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json; charset=UTF-8'
                ])
                ->post($this->baseUrl . '/RelacaoAlunosClasse/FormacaoClasse', $requestData);
            
            if (!$response->successful()) {
                throw SedApiException::requestFailed(
                    'Falha na requisição de consulta de turma',
                    $response->status(),
                    $response->body()
                );
            }
            
            $data = $response->json();
            
            // Verificar se há erro de negócio
            if (!empty($data['outErro'])) {
                throw SedApiException::businessError($data['outErro']);
            }
            
            // Log da requisição bem-sucedida
            Log::info('SED API: Turma consultada com sucesso', [
                'classe' => $inNumClasse,
                'escola' => $data['outCodEscola'] ?? null,
                'ano_letivo' => $data['outAnoLetivo'] ?? null,
                'processo_id' => $data['outProcessoID'] ?? null
            ]);
            
            return $data;
            
        } catch (SedApiException $e) {
            Log::error('SED API: Erro ao consultar turma', [
                'error' => $e->getMessage(),
                'classe' => $inNumClasse,
                'endpoint' => '/RelacaoAlunosClasse/FormacaoClasse'
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SED API: Erro inesperado ao consultar turma', [
                'error' => $e->getMessage(),
                'classe' => $inNumClasse,
                'endpoint' => '/RelacaoAlunosClasse/FormacaoClasse'
            ]);
            throw SedApiException::unexpectedError('Erro inesperado ao consultar turma: ' . $e->getMessage());
        }
    }

    /**
     * Busca a ficha completa de um aluno pelo RA
     *
     * @param array $raData Dados do RA contendo inNumRA, inDigitoRA e inSiglaUFRA
     * @return array Dados da ficha do aluno
     * @throws SedApiException
     */
    public function getStudentProfile(array $raData)
    {
        try {
            // Validar parâmetros obrigatórios
            if (empty($raData['inNumRA'])) {
                throw SedApiException::invalidParameter('Número do RA é obrigatório');
            }
            
            if (empty($raData['inSiglaUFRA'])) {
                throw SedApiException::invalidParameter('Sigla UF do RA é obrigatória');
            }

            // Obter token de autenticação
            $token = $this->getToken();

            // Preparar parâmetros como query parameters simples
            $queryParams = [
                'inNumRA' => $raData['inNumRA'],
                'inSiglaUFRA' => $raData['inSiglaUFRA'],
            ];
            
            // Adicionar dígito do RA apenas se não estiver vazio
            if (!empty($raData['inDigitoRA'])) {
                $queryParams['inDigitoRA'] = $raData['inDigitoRA'];
            }     

            Log::info('SED API: Buscando ficha do aluno', [
                'ra' => $raData['inNumRA'],
                'digito' => $raData['inDigitoRA'] ?? 'não informado',
                'uf' => $raData['inSiglaUFRA'],
                'endpoint' => '/Aluno/ExibirFichaAluno',
                'query_params' => $queryParams
            ]);

             // Fazer requisição POST para a API
             $response = Http::timeout(config('sed.api.timeout', 30))
                 ->withHeaders([
                     'Authorization' => 'Bearer ' . $token,
                     'Content-Type' => 'application/json; charset=UTF-8'
                 ])
                 ->post($this->baseUrl . '/Aluno/ExibirFichaAluno', [
                     'inAluno' => $queryParams
                 ]);
                // ->get($this->baseUrl . '/Aluno/ExibirFichaAluno', [
                //     $queryParams
                // ]);

            return $response->json();

            if (!$response->successful()) {
                throw SedApiException::requestFailed(
                    'Falha na requisição de ficha do aluno',
                    $response->status(),
                    $response->body()
                );
            }
            
            $data = $response->json();
            
            // Verificar se há erro de negócio
            if (!empty($data['outErro'])) {
                throw SedApiException::businessError($data['outErro']);
            }
            
            // Log da requisição bem-sucedida
            Log::info('SED API: Ficha do aluno obtida com sucesso', [
                'ra' => $raData['inNumRA'],
                'digito' => $raData['inDigitoRA'] ?? 'não informado',
                'uf' => $raData['inSiglaUFRA'],
                'processo_id' => $data['outProcessoID'] ?? null
            ]);
            
            return $data;
            
        } catch (SedApiException $e) {
            Log::error('SED API: Erro ao buscar ficha do aluno', [
                'error' => $e->getMessage(),
                'ra' => $raData['inNumRA'] ?? 'não informado',
                'endpoint' => '/Aluno/ExibirFichaAluno'
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SED API: Erro inesperado ao buscar ficha do aluno', [
                'error' => $e->getMessage(),
                'ra' => $raData['inNumRA'] ?? 'não informado',
                'endpoint' => '/Aluno/ExibirFichaAluno'
            ]);
            throw SedApiException::unexpectedError('Erro inesperado ao buscar ficha do aluno: ' . $e->getMessage());
        }
    }
}