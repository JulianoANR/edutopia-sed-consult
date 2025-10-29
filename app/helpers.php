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

// -------------------------------------------------------------
// Helpers de Roles (multi-role)
// -------------------------------------------------------------
use App\Models\User as UserModel;

if (!function_exists('user_has_role')) {
    /**
     * Verifica se o usuário possui uma role específica.
     * Faz fallback para o campo legado users.role se necessário.
     */
    function user_has_role($user, string $role): bool
    {
        if (!$user) {
            return false;
        }
        // Se for instância do nosso modelo, usa o helper do model
        if ($user instanceof UserModel) {
            return $user->hasRole($role);
        }
        // Fallback robusto: recarrega o usuário como Eloquent e verifica
        $eloquentUser = UserModel::with('roleLinks')->find(optional($user)->id);
        if ($eloquentUser) {
            return $eloquentUser->hasRole($role);
        }
        // Último recurso: checa propriedade legado 'role'
        return (optional($user)->role) === $role;
    }
}

if (!function_exists('user_has_any_role')) {
    /**
     * Verifica se o usuário possui qualquer role da lista.
     */
    function user_has_any_role($user, array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : array_map('trim', explode(',', $roles));
        foreach ($roles as $role) {
            if (user_has_role($user, $role)) {
                return true;
            }
        }
        return false;
    }
}