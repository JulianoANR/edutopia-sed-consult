<?php

namespace App\Services;

use App\Exceptions\SedApiException;
use Illuminate\Support\Facades\Log;

class SedAlunosService
{
    private SedApiService $api;

    public function __construct(SedApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Busca a ficha completa de um aluno pelo RA
     *
     * @param array $raData Dados do RA contendo inNumRA, inDigitoRA e inSiglaUFRA
     * @return array Dados da ficha do aluno
     * @throws SedApiException
     */
    public function getStudentProfile(array $raData): array
    {
        if (empty($raData['inNumRA'])) {
            throw SedApiException::invalidParameter('Número do RA é obrigatório');
        }
        if (empty($raData['inSiglaUFRA'])) {
            throw SedApiException::invalidParameter('Sigla UF do RA é obrigatória');
        }

        $queryParams = [
            'inNumRA' => $raData['inNumRA'],
            'inSiglaUFRA' => $raData['inSiglaUFRA'],
        ];
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

        $data = $this->api->post('Aluno/ExibirFichaAluno', [
            'inAluno' => $queryParams
        ]);

        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        Log::info('SED API: Ficha do aluno obtida com sucesso', [
            'ra' => $raData['inNumRA'],
            'digito' => $raData['inDigitoRA'] ?? 'não informado',
            'uf' => $raData['inSiglaUFRA'],
            'processo_id' => $data['outProcessoID'] ?? null
        ]);

        return $data;
    }
}