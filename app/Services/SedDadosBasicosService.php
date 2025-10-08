<?php

namespace App\Services;

use App\Exceptions\SedApiException;
use Illuminate\Support\Facades\Log;

class SedDadosBasicosService
{
    private SedApiService $api;

    public function __construct(SedApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Buscar lista de diretorias de ensino estaduais
     * Baseado na documentação NCA101 – INTEGRAÇÃO – NCAAPI – Diretorias
     *
     * @return array
     * @throws SedApiException
     */
    public function getDiretorias(): array
    {
        Log::info('SED API: Buscando lista de diretorias', [
            'endpoint' => '/DadosBasicos/Diretorias'
        ]);

        $data = $this->api->get('DadosBasicos/Diretorias');

        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        Log::info('SED API: Diretorias obtidas com sucesso', [
            'total_diretorias' => count($data['outDiretorias'] ?? []),
            'processo_id' => $data['outProcessoID'] ?? null
        ]);

        return $data;
    }

    /**
     * Buscar tipos de ensino
     * Baseado no endpoint /DadosBasicos/TipoEnsino
     *
     * @return array
     * @throws SedApiException
     */
    public function getTipoEnsino(): array
    {
        Log::info('SED API: Buscando tipos de ensino', [
            'endpoint' => '/DadosBasicos/TipoEnsino'
        ]);

        $data = $this->api->get('DadosBasicos/TipoEnsino');

        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        Log::info('SED API: Tipos de ensino obtidos com sucesso', [
            'total_tipos' => count($data['outTipoEnsino'] ?? []),
            'processo_id' => $data['outProcessoID'] ?? null
        ]);

        return $data;
    }
}