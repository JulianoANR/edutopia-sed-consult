<?php

namespace App\Mappers;

class StudentExportMapper
{
    /**
     * Definição dos cabeçalhos das colunas do Excel
     * 
     * @return array
     */
    public static function getHeaders(): array
    {
        return [
            'RA',
            'Nome',
            'Data Nascimento',
            // 'CPF',
            // 'RG',
            'Sexo',
            'Cor/Raça',
            // 'Endereço Completo',
            // 'Telefones',
            // 'Email',
            'Nome da Mãe',
            'Nome do Pai',
            // 'Situação Matrícula',
            'Turma',
            'Turno',
            'Tipo Ensino',
            'Tipo Classe',
            'Escola',
            // 'Código Escola',
            'Data Início Matrícula',
            'Data Fim Matrícula',
            'UF RA'
        ];
    }

    /**
     * Mapeia os dados do aluno para o formato de exportação
     * 
     * @param array $studentData Dados completos do aluno da API SED
     * @param array $additionalData Dados adicionais como turma, escola, etc.
     * @return array
     */
    public static function mapStudentData(array $studentData, array $additionalData = []): array
    {
        $dadosPessoais = $studentData['outDadosPessoais'] ?? [];
        $endereco = $studentData['outEndereco'] ?? [];
        $telefones = $studentData['outTelefones'] ?? [];
        $emails = $studentData['outEmails'] ?? [];
        
        return [
            // RA completo com dígito
            ($dadosPessoais['outNumRA'] ?? '') . '-' . ($dadosPessoais['outDigitoRA'] ?? ''),
            
            // Nome do aluno
            $dadosPessoais['outNomeAluno'] ?? '',
            
            // Data de nascimento
            self::formatDate($dadosPessoais['outDataNascimento'] ?? ''),
            
            // CPF
            // self::formatCpf($dadosPessoais['outNrCpf'] ?? ''),
            
            // RG
            // $dadosPessoais['outNrRg'] ?? '',
            
            // Sexo
            $dadosPessoais['outSexo'] ?? '',
            
            // Cor/Raça
            $dadosPessoais['outDescCorRaca'] ?? '',
            
            // Endereço completo
            // self::formatAddress($endereco),
            
            // Telefones
            // self::formatPhones($telefones),
            
            // Email
            // self::formatEmails($emails),
            
            // Nome da mãe
            $dadosPessoais['outNomeMae'] ?? '',
            
            // Nome do pai
            $dadosPessoais['outNomePai'] ?? '',
            
            // Situação da matrícula
            $dadosPessoais['outDescSituacaoMatricula'] ?? '',
            
            // Turma (vem dos dados adicionais)
            $additionalData['turma'] ?? '',

            // Turno (vem dos dados adicionais)
            $additionalData['turno'] ?? '',
            
            // Tipo Ensino (vem dos dados adicionais)
            $additionalData['tipo_ensino'] ?? '',
            
            // Tipo Classe (vem dos dados adicionais)
            $additionalData['tipo_classe'] ?? '',
            
            // Escola (vem dos dados adicionais)
            $additionalData['escola'] ?? '',
            
            // Código da escola (vem dos dados adicionais)
            // $additionalData['codigo_escola'] ?? '',
            
            // Data início matrícula (vem dos dados adicionais)
            self::formatDate($additionalData['data_inicio_matricula'] ?? ''),
            
            // Data fim matrícula (vem dos dados adicionais)
            self::formatDate($additionalData['data_fim_matricula'] ?? ''),
            
            // UF do RA
            // $dadosPessoais['outSiglaUFRA'] ?? 'SP'
        ];
    }

    /**
     * Formatar endereço completo
     * 
     * @param array $endereco
     * @return string
     */
    private static function formatAddress(array $endereco): string
    {
        $parts = [];
        
        if (!empty($endereco['outLogradouro'])) {
            $parts[] = $endereco['outLogradouro'];
        }
        
        if (!empty($endereco['outNumero'])) {
            $parts[] = 'nº ' . $endereco['outNumero'];
        }
        
        if (!empty($endereco['outComplemento'])) {
            $parts[] = $endereco['outComplemento'];
        }
        
        if (!empty($endereco['outBairro'])) {
            $parts[] = $endereco['outBairro'];
        }
        
        if (!empty($endereco['outCidade'])) {
            $parts[] = $endereco['outCidade'];
        }
        
        // if (!empty($endereco['outUF'])) {
        //     $parts[] = $endereco['outUF'];
        // }
        
        // if (!empty($endereco['outCEP'])) {
        //     $parts[] = 'CEP: ' . $endereco['outCEP'];
        // }
        
        return implode(', ', $parts);
    }

    /**
     * Formatar telefones
     * 
     * @param array $telefones
     * @return string
     */
    private static function formatPhones(array $telefones): string
    {
        if (empty($telefones)) {
            return '';
        }
        
        $phones = [];
        foreach ($telefones as $telefone) {
            if (!empty($telefone['outTelefone'])) {
                $phone = $telefone['outTelefone'];
                if (!empty($telefone['outTipoTelefone'])) {
                    $phone .= ' (' . $telefone['outTipoTelefone'] . ')';
                }
                $phones[] = $phone;
            }
        }
        
        return implode(', ', $phones);
    }

    /**
     * Formatar emails
     * 
     * @param array $emails
     * @return string
     */
    private static function formatEmails(array $emails): string
    {
        if (empty($emails)) {
            return '';
        }
        
        $emailList = [];
        foreach ($emails as $email) {
            if (!empty($email['outEmail'])) {
                $emailList[] = $email['outEmail'];
            }
        }
        
        return implode(', ', $emailList);
    }

    /**
     * Formatar data para exibição
     * 
     * @param string $date
     * @return string
     */
    private static function formatDate(string $date): string
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            // Tentar diferentes formatos de data
            $formats = ['Y-m-d', 'd/m/Y', 'Y-m-d H:i:s'];
            
            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj !== false) {
                    return $dateObj->format('d/m/Y');
                }
            }
            
            // Se não conseguir formatar, retorna a data original
            return $date;
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Formatar CPF
     * 
     * @param string $cpf
     * @return string
     */
    private static function formatCpf(string $cpf): string
    {
        if (empty($cpf)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Aplica máscara se tiver 11 dígitos
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        
        return $cpf;
    }

    /**
     * Obter cabeçalhos simplificados (versão atual do sistema)
     * 
     * @return array
     */
    public static function getSimplifiedHeaders(): array
    {
        return [
            'RA',
            'Nome',
            'Data Nascimento',
            'Sexo',
            'Cor/Raça',
            'Nome da Mãe',
            'Nome do Pai'
        ];
    }

    /**
     * Mapear dados do aluno para formato simplificado (versão atual do sistema)
     * 
     * @param array $studentData
     * @param array $additionalData
     * @return array
     */
    public static function mapStudentDataSimplified(array $studentData, array $additionalData = []): array
    {
        $dadosPessoais = $studentData['outDadosPessoais'] ?? [];
        
        return [
            ($dadosPessoais['outNumRA'] ?? '') . ($dadosPessoais['outDigitoRA'] ?? ''),
            $dadosPessoais['outNomeAluno'] ?? '',
            $dadosPessoais['outDataNascimento'] ?? '',
            $dadosPessoais['outSexo'] ?? '',
            $dadosPessoais['outDescCorRaca'] ?? '',
            $dadosPessoais['outNomeMae'] ?? '',
            $dadosPessoais['outNomePai'] ?? ''
        ];
    }
}