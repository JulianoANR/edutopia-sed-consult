<?php

namespace App\Services;

use App\Exceptions\SedApiException;
use Illuminate\Support\Facades\Log;

class SedTurmasService
{
    private SedApiService $api;

    public function __construct(SedApiService $api)
    {
        $this->api = $api;
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
    ) {
        if (empty($anoLetivo) || strlen($anoLetivo) !== 4 || !ctype_digit($anoLetivo)) {
            throw SedApiException::invalidParameter('Ano letivo deve ter 4 dígitos numéricos');
        }
        if (empty($codEscola) || !ctype_digit($codEscola)) {
            throw SedApiException::invalidParameter('Código da escola é obrigatório e deve conter apenas dígitos');
        }

        // Sanitizar parâmetros opcionais: considerar apenas valores numéricos
        $codTipoEnsinoNum = (!empty($codTipoEnsino) && ctype_digit($codTipoEnsino)) ? $codTipoEnsino : null;
        $codSerieAnoNum = (!empty($codSerieAno) && ctype_digit($codSerieAno)) ? $codSerieAno : null;
        $codTurnoNum = (!empty($codTurno) && ctype_digit($codTurno)) ? $codTurno : null;
        $semestreNum = (!empty($semestre) && ctype_digit($semestre)) ? $semestre : null;

        $queryParams = [
            'inAnoLetivo' => $anoLetivo,
            'inCodEscola' => $codEscola
        ];
        if ($codTipoEnsinoNum !== null) {
            $queryParams['inCodTipoEnsino'] = $codTipoEnsinoNum;
        }
        if ($codSerieAnoNum !== null) {
            $queryParams['inCodSerieAno'] = $codSerieAnoNum;
        }

        $body = [
            'inAnoLetivo' => $anoLetivo,
            'inCodEscola' => $codEscola,
        ];
        if ($codTurnoNum !== null) {
            $body['inCodTurno'] = $codTurnoNum;
        }
        if ($semestreNum !== null) {
            $body['inSemestre'] = $semestreNum;
        }

        $endpoint = 'RelacaoAlunosClasse/RelacaoClasses?' . http_build_query($queryParams);

        $data = $this->api->post($endpoint, $body);

        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        // Padronizar nome da turma em cada item de outClasses
        if (isset($data['outClasses']) && is_array($data['outClasses'])) {
            $data['outClasses'] = array_map(function ($class) {
                $nome = \getNomeTurma($class['outCodTipoEnsino'] ?? null, $class['outCodSerieAno'] ?? null) ?? '';
                if (isset($class['outTurma']) && $class['outTurma'] !== '') {
                    $nome .= ' ' . strtoupper($class['outTurma']);
                }
                if (isset($class['outDescricaoTurno']) && $class['outDescricaoTurno'] !== '') {
                    $nome .= ' - ' . $class['outDescricaoTurno'];
                }
                $class['nome_turma'] = trim($nome);
                return $class;
            }, $data['outClasses']);
        }

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
    }

    /**
     * Consultar informações de uma turma específica
     *
     * @param string $inNumClasse Número da classe/turma
     * @return array
     * @throws SedApiException
     */
    public function consultarTurma(string $inNumClasse): array
    {
        if (empty($inNumClasse)) {
            throw SedApiException::invalidParameter('Número da classe é obrigatório');
        }

        $requestData = [
            'inNumClasse' => $inNumClasse
        ];

        $data = $this->api->post('RelacaoAlunosClasse/FormacaoClasse', $requestData);

        if (!empty($data['outErro'])) {
            throw SedApiException::businessError($data['outErro']);
        }

        Log::info('SED API: Turma consultada com sucesso', [
            'classe' => $inNumClasse,
            'escola' => $data['outCodEscola'] ?? null,
            'ano_letivo' => $data['outAnoLetivo'] ?? null,
            'processo_id' => $data['outProcessoID'] ?? null
        ]);

        return $data;
    }
}