<?php

use App\Helpers\TipoEnsinoHelper;

if (!function_exists('getNomeTurma')) {
    /**
     * Função global para buscar o nome da turma (outDescSerieAno)
     * baseado nos códigos de tipo de ensino e série/ano
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @param string|int $codSerieAno Código da série/ano
     * @return string|null Nome da turma ou null se não encontrado
     */
    function getNomeTurma($codTipoEnsino, $codSerieAno): ?string
    {
        return TipoEnsinoHelper::getNomeTurma($codTipoEnsino, $codSerieAno);
    }
}

if (!function_exists('getDescricaoTipoEnsino')) {
    /**
     * Função global para buscar a descrição do tipo de ensino
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @return string|null Descrição do tipo de ensino ou null se não encontrado
     */
    function getDescricaoTipoEnsino($codTipoEnsino): ?string
    {
        return TipoEnsinoHelper::getDescricaoTipoEnsino($codTipoEnsino);
    }
}

if (!function_exists('getInformacoesCompletasTurma')) {
    /**
     * Função global para buscar informações completas da turma
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @param string|int $codSerieAno Código da série/ano
     * @return array|null Array com informações completas ou null se não encontrado
     */
    function getInformacoesCompletasTurma($codTipoEnsino, $codSerieAno): ?array
    {
        return TipoEnsinoHelper::getInformacoesCompletas($codTipoEnsino, $codSerieAno);
    }
}

if (!function_exists('getSeriesPorTipoEnsino')) {
    /**
     * Função global para buscar todas as séries/anos de um tipo de ensino
     * 
     * @param string|int $codTipoEnsino Código do tipo de ensino
     * @return array Array com códigos e descrições das séries/anos
     */
    function getSeriesPorTipoEnsino($codTipoEnsino): array
    {
        return TipoEnsinoHelper::getSeriesPorTipoEnsino($codTipoEnsino);
    }
}

if (!function_exists('getTodosTiposEnsino')) {
    /**
     * Função global para listar todos os tipos de ensino disponíveis
     * 
     * @return array Array com todos os tipos de ensino
     */
    function getTodosTiposEnsino(): array
    {
        return TipoEnsinoHelper::getTodosTiposEnsino();
    }
}