<?php

namespace App\Services;

use App\Exceptions\SedApiException;
use Illuminate\Support\Facades\Log;

class SedEscolasService
{
    private SedApiService $api;

    public function __construct(SedApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Buscar escolas por município e rede de ensino
     * Baseado na documentação NCA102 - EscolasPorMunicipio v1.1
     *
     * @param int|null $codDiretoria Código da diretoria (opcional)
     * @param int|null $codMunicipio Código DNE do município
     * @param int|null $codRedeEnsino Código da rede de ensino (1-Estadual, 2-Municipal, 3-Privada, 4-Federal, 5-Estadual Outros)
     * @return array
     * @throws SedApiException
     */
    public function getEscolasPorMunicipio(?int $codDiretoria = null, ?int $codMunicipio = null, ?int $codRedeEnsino = null): array
    {
        // Usar valores padrão do usuário logado se não fornecidos
        $codDiretoria = $codDiretoria ?? $this->api->getDiretoriaId();
        $codMunicipio = $codMunicipio ?? $this->api->getMunicipioId();
        $codRedeEnsino = $codRedeEnsino ?? $this->api->getRedeEnsinoCod();

        // Validações
        if (empty($codMunicipio)) {
            throw SedApiException::invalidParameter('Código do município é obrigatório');
        }
        if (!in_array($codRedeEnsino, [1, 2, 3, 4, 5])) {
            throw SedApiException::invalidParameter('Código da rede de ensino deve ser 1-5');
        }

        $queryParams = [
            'inCodMunicipio' => $codMunicipio,
            'inCodRedeEnsino' => $codRedeEnsino
        ];
        if (!empty($codDiretoria)) {
            $queryParams['inCodDiretoria'] = $codDiretoria;
        }

        Log::info('SED API: Buscando escolas por município', [
            'municipio' => $codMunicipio,
            'diretoria' => $codDiretoria,
            'rede_ensino' => $codRedeEnsino,
        ]);

        $data = $this->api->get('DadosBasicos/EscolasPorMunicipio', $queryParams);

        // Verificar se há erro de negócio
        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        Log::info('SED API: Escolas por município obtidas com sucesso', [
            'total_escolas' => count($data['outEscolas'] ?? []),
            'processo_id' => $data['outProcessoID'] ?? null
        ]);

        return $data;
    }
}