<?php

namespace App\Helpers;

class TipoEnsinoHelper
{
    /**
     * Mapeamento completo dos tipos de ensino e suas séries/anos
     * Baseado no arquivo relação.js da API SED
     */
    private static array $tipoEnsinoMap = [
        "2" => [
            "desc" => "ENSINO MEDIO",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE",
                "4" => "4ª SERIE"
            ]
        ],
        "3" => [
            "desc" => "EJA FUNDAMENTAL - ANOS INICIAIS",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° TERMO",
                "2" => "2° TERMO",
                "3" => "3° TERMO",
                "4" => "4° TERMO",
                "5" => "5° TERMO",
                "9" => "série 9 - 1° termo/ 2° termo",
                "10" => "série 10 - 3° termo/4 termo"
            ]
        ],
        "4" => [
            "desc" => "EJA FUNDAMENTAL - ANOS FINAIS",
            "series" => [
                "0" => "MULTISSERIADA",
                "9" => "9° TERMO",
                "10" => "10° TERMO",
                "11" => "11° TERMO",
                "12" => "12° TERMO",
                "13" => "1° TERMO / 2° TERMO",
                "14" => "3° TERMO / 4° TERMO"
            ]
        ],
        "5" => [
            "desc" => "EJA ENSINO MEDIO",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° TERMO",
                "2" => "2° TERMO",
                "3" => "3° TERMO",
                "4" => "4° TERMO"
            ]
        ],
        "6" => [
            "desc" => "EDUCACAO INFANTIL",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª ETAPA PRÉ-ESCOLA",
                "2" => "2ª ETAPA PRÉ-ESCOLA",
                "4" => "BERÇÁRIO 1",
                "5" => "BERÇÁRIO 2",
                "6" => "MATERNAL 1",
                "7" => "MATERNAL 2"
            ]
        ],
        "9" => [
            "desc" => "EDUCACAO ESPECIAL - DI - CRPE",
            "series" => [
                "0" => "MULTISSERIADA"
            ]
        ],
        "13" => [
            "desc" => "CURSO NORMAL",
            "series" => [
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE",
                "4" => "4ª SERIE"
            ]
        ],
        "14" => [
            "desc" => "ENSINO FUNDAMENTAL DE 9 ANOS",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° ANO",
                "2" => "2° ANO",
                "3" => "3° ANO",
                "4" => "4° ANO",
                "5" => "5° ANO",
                "6" => "6° ANO",
                "7" => "7° ANO",
                "8" => "8° ANO",
                "9" => "9° ANO"
            ]
        ],
        "15" => [
            "desc" => "CEL - CENTRO DE ESTUDO DE LINGUAS",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° ESTAGIO DISCIPLINAS",
                "2" => "2° ESTAGIO DISCIPLINAS",
                "3" => "3° ESTAGIO DISCIPLINAS",
                "4" => "4° ESTAGIO DISCIPLINAS",
                "5" => "5° ESTAGIO DISCIPLINAS",
                "6" => "6° ESTAGIO DISCIPLINAS"
            ]
        ],
        "25" => [
            "desc" => "ENSINO MEDIO INTEGRADO A EDUCACAO PROFISSIONAL",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE",
                "4" => "4ª SERIE"
            ]
        ],
        "26" => [
            "desc" => "COMPLEMENTAÇÃO EDUCACIONAL",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° NIVEL",
                "2" => "2° NIVEL",
                "3" => "3° NIVEL",
                "4" => "4° NIVEL",
                "5" => "5° NIVEL"
            ]
        ],
        "30" => [
            "desc" => "ENSINO FUNDAMENTAL - N1 PRTE",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° ANO",
                "2" => "2° ANO",
                "3" => "3° ANO",
                "4" => "4° ANO",
                "5" => "5° ANO"
            ]
        ],
        "31" => [
            "desc" => "ATIVIDADES CURRICULARES DESPORTIVAS E ARTÍSTICAS (ACDA)",
            "series" => [
                "1" => "ACD"
            ]
        ],
        "32" => [
            "desc" => "ATENDIMENTO EDUCACIONAL ESPECIALIZADO",
            "series" => [
                "0" => "AEE"
            ]
        ],
        "33" => [
            "desc" => "EDUCACAO ESPECIAL EXCLUSIVA",
            "series" => [
                "0" => "EEE"
            ]
        ],
        "34" => [
            "desc" => "ESPANHOL",
            "series" => [
                "1" => "1ª SERIE"
            ]
        ],
        "35" => [
            "desc" => "EDUCACAO PROFISSIONAL",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1° MODULO",
                "2" => "2° MODULO",
                "3" => "3° MODULO",
                "4" => "4° MODULO",
                "5" => "5° MODULO",
                "6" => "6° MODULO",
                "7" => "7° MODULO",
                "8" => "8° MODULO",
                "9" => "9° MODULO",
                "10" => "10° MODULO"
            ]
        ],
        "36" => [
            "desc" => "PROEJA - ENSINO FUNDAMENTAL",
            "series" => [
                "9" => "9° TERMO",
                "10" => "10° TERMO",
                "11" => "11° TERMO",
                "12" => "12° TERMO"
            ]
        ],
        "37" => [
            "desc" => "PROEJA - ENSINO MÉDIO",
            "series" => [
                "9" => "9° TERMO",
                "10" => "10° TERMO",
                "11" => "11° TERMO"
            ]
        ],
        "39" => [
            "desc" => "EDUCACAO ESPECIAL - TEA - CRPE",
            "series" => [
                "0" => "MULTISSERIADA"
            ]
        ],
        "40" => [
            "desc" => "ENSINO FUNDAMENTAL - N2 PRTE",
            "series" => [
                "0" => "MULTISSERIADA",
                "6" => "6° ANO",
                "7" => "7° ANO",
                "8" => "8° ANO",
                "9" => "9° ANO"
            ]
        ],
        "45" => [
            "desc" => "EDUCACAO ESPECIAL - ALTAS HABILIDADES/SUPERDOTAÇÃO – SALA DE RECURSO",
            "series" => [
                "0" => "NÃO SERIADA"
            ]
        ],
        "46" => [
            "desc" => "EJA TÉCNICO INTEGRADO DE PROFISSIONAL - EF",
            "series" => [
                "9" => "9° TERMO",
                "10" => "10° TERMO",
                "11" => "11° TERMO",
                "12" => "12° TERMO"
            ]
        ],
        "47" => [
            "desc" => "EJA TECNICO INTEGRADO A EDUC PROF - EM",
            "series" => [
                "1" => "1° TERMO",
                "2" => "2° TERMO",
                "3" => "3° TERMO",
                "4" => "4° TERMO",
                "5" => "5° TERMO",
                "6" => "6° TERMO",
                "7" => "7° TERMO",
                "8" => "8° TERMO",
                "9" => "9° TERMO",
                "10" => "10° TERMO",
                "11" => "11° TERMO",
                "12" => "12° TERMO"
            ]
        ],
        "50" => [
            "desc" => "ENSINO MEDIO - N3 PRTE",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE"
            ]
        ],
        "101" => [
            "desc" => "NOVO ENSINO MÉDIO",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE",
                "4" => "4ª SERIE"
            ]
        ],
        "102" => [
            "desc" => "CORREÇÃO DE FLUXO - ANOS FINAIS",
            "series" => [
                "0" => "NÃO SERIADA"
            ]
        ],
        "103" => [
            "desc" => "CORREÇÃO DE FLUXO - ENSINO MÉDIO",
            "series" => [
                "0" => "NÃO SERIADA"
            ]
        ],
        "104" => [
            "desc" => "NOVO ENSINO MÉDIO COM HABILITAÇÃO PROFISSIONAL",
            "series" => [
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE"
            ]
        ],
        "105" => [
            "desc" => "NOVO ENSINO MEDIO - N3 PRTE",
            "series" => [
                "0" => "MULTISSERIADA",
                "1" => "1ª SERIE",
                "2" => "2ª SERIE",
                "3" => "3ª SERIE"
            ]
        ],
        "106" => [
            "desc" => "ALÉM DA ESCOLA",
            "series" => [
                "4" => "ENSINO FUNDAMENTAL - ANOS INICIAIS",
                "5" => "ENSINO FUNDAMENTAL - ANOS FINAIS",
                "6" => "ENSINO MÉDIO"
            ]
        ],
        "109" => [
            "desc" => "ITINERÁRIO FORMATIVO",
            "series" => [
                "0" => "Não seriado"
            ]
        ],
        "110" => [
            "desc" => "EXPANSÃO NOVO EM",
            "series" => [
                "0" => "Não seriado"
            ]
        ],
        "111" => [
            "desc" => "AULAS OLÍMPICAS",
            "series" => [
                "1" => "N1",
                "2" => "N2",
                "3" => "N3"
            ]
        ]
    ];

    /**
     * Busca o nome da turma (outDescSerieAno) baseado nos códigos
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @param string|int $codSerieAno Código da série/ano
     * @return string|null Nome da turma ou null se não encontrado
     */
    public static function getNomeTurma($codTipoEnsino, $codSerieAno): ?string
    {
        // Converter para string para garantir compatibilidade
        $codTipoEnsino = (string) $codTipoEnsino;
        $codSerieAno = (string) $codSerieAno;

        // Verificar se o tipo de ensino existe
        if (!isset(self::$tipoEnsinoMap[$codTipoEnsino])) {
            return null;
        }

        // Verificar se a série/ano existe para este tipo de ensino
        if (!isset(self::$tipoEnsinoMap[$codTipoEnsino]['series'][$codSerieAno])) {
            return null;
        }

        return self::$tipoEnsinoMap[$codTipoEnsino]['series'][$codSerieAno];
    }

    /**
     * Busca a descrição do tipo de ensino
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @return string|null Descrição do tipo de ensino ou null se não encontrado
     */
    public static function getDescricaoTipoEnsino($codTipoEnsino): ?string
    {
        $codTipoEnsino = (string) $codTipoEnsino;

        if (!isset(self::$tipoEnsinoMap[$codTipoEnsino])) {
            return null;
        }

        return self::$tipoEnsinoMap[$codTipoEnsino]['desc'];
    }

    /**
     * Busca todas as séries/anos disponíveis para um tipo de ensino
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @return array Array com códigos e descrições das séries/anos
     */
    public static function getSeriesPorTipoEnsino($codTipoEnsino): array
    {
        $codTipoEnsino = (string) $codTipoEnsino;

        if (!isset(self::$tipoEnsinoMap[$codTipoEnsino])) {
            return [];
        }

        return self::$tipoEnsinoMap[$codTipoEnsino]['series'];
    }

    /**
     * Busca informações completas (tipo de ensino + turma)
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @param string|int $codSerieAno Código da série/ano
     * @return array|null Array com informações completas ou null se não encontrado
     */
    public static function getInformacoesCompletas($codTipoEnsino, $codSerieAno): ?array
    {
        $nomeTurma = self::getNomeTurma($codTipoEnsino, $codSerieAno);
        $descTipoEnsino = self::getDescricaoTipoEnsino($codTipoEnsino);

        if ($nomeTurma === null || $descTipoEnsino === null) {
            return null;
        }

        return [
            'cod_tipo_ensino' => (string) $codTipoEnsino,
            'desc_tipo_ensino' => $descTipoEnsino,
            'cod_serie_ano' => (string) $codSerieAno,
            'nome_turma' => $nomeTurma,
            'descricao_completa' => $descTipoEnsino . ' - ' . $nomeTurma
        ];
    }

    /**
     * Lista todos os tipos de ensino disponíveis
     * 
     * @return array Array com todos os tipos de ensino
     */
    public static function getTodosTiposEnsino(): array
    {
        $tipos = [];
        foreach (self::$tipoEnsinoMap as $codigo => $dados) {
            $tipos[$codigo] = $dados['desc'];
        }
        return $tipos;
    }
}