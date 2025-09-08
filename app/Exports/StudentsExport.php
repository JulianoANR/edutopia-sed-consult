<?php

namespace App\Exports;

class StudentsExport
{
    protected $studentsData;

    public function __construct($studentsData)
    {
        $this->studentsData = $studentsData;
    }

    public function exportCsv()
    {
        $headers = [
            'RA', 
            'Nome', 
            'Data Nascimento', 
            // 'CPF', 
            // 'RG', 
            'Sexo', 
            'Cor/Raça',
            // 'Endereço', 
            // 'Telefones', 
            // 'Email', 
            'Nome da Mãe', 
            'Nome do Pai',
            // 'Situação', 
            // 'Turma', 
            // 'Escola'
        ];
        
        $csvData = [];
        $csvData[] = $headers;
        
        foreach ($this->studentsData as $student) {
            $dadosPessoais = $student['outDadosPessoais'] ?? [];
            
            $csvData[] = [
                ($dadosPessoais['outNumRA'] ?? '') . ($dadosPessoais['outDigitoRA'] ?? ''),
                $dadosPessoais['outNomeAluno'] ?? '',
                $dadosPessoais['outDataNascimento'] ?? '',
                // $dadosPessoais['outNrCpf'] ?? '',
                // $dadosPessoais['outNrRg'] ?? '',
                $dadosPessoais['outSexo'] ?? '',
                $dadosPessoais['outDescCorRaca'] ?? '',
                // $this->formatAddress($student),
                // $this->formatPhones($student),
                // $this->formatEmails($student),
                $dadosPessoais['outNomeMae'] ?? '',
                $dadosPessoais['outNomePai'] ?? '',
                // $dadosPessoais['outDescSituacaoMatricula'] ?? '',
                // $student['outNomeTurma'] ?? '',
                // $student['outNomeEscola'] ?? ''
            ];
        }
        
        return $csvData;
    }

    private function formatAddress($student)
    {
        $address = [];
        $enderecos = $student['outEnderecos'] ?? [];
        
        // Pega o primeiro endereço disponível
        if (!empty($enderecos) && is_array($enderecos)) {
            $endereco = $enderecos[0];
            
            if (!empty($endereco['outNmLogradouro'])) {
                $address[] = $endereco['outNmLogradouro'];
            }
            
            if (!empty($endereco['outNrEndereco'])) {
                $address[] = 'Nº ' . $endereco['outNrEndereco'];
            }
            
            if (!empty($endereco['outNmBairro'])) {
                $address[] = $endereco['outNmBairro'];
            }
            
            if (!empty($endereco['outNmMunicipio'])) {
                $address[] = $endereco['outNmMunicipio'];
            }
            
            if (!empty($endereco['outSgUf'])) {
                $address[] = $endereco['outSgUf'];
            }
            
            if (!empty($endereco['outNrCep'])) {
                $address[] = 'CEP: ' . $endereco['outNrCep'];
            }
        }
        
        return implode(', ', $address);
    }

    private function formatPhones($student)
    {
        $phones = [];
        $telefones = $student['outTelefones'] ?? [];
        
        if (!empty($telefones) && is_array($telefones)) {
            foreach ($telefones as $telefone) {
                if (!empty($telefone['outNrTelefone'])) {
                    $phones[] = $telefone['outNrTelefone'];
                }
            }
        }
        
        return implode(' / ', $phones);
    }
    
    private function formatEmails($student)
    {
        $emails = [];
        $emailsData = $student['outEmails'] ?? [];
        
        if (!empty($emailsData) && is_array($emailsData)) {
            foreach ($emailsData as $email) {
                if (!empty($email['outDsEmail'])) {
                    $emails[] = $email['outDsEmail'];
                }
            }
        }
        
        return implode(' / ', $emails);
    }
    
    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            // Tenta diferentes formatos de data
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('d/m/Y', $timestamp);
            }
            
            // Se não conseguir converter, retorna a data original
            return $date;
        } catch (\Exception $e) {
            return '';
        }
    }
}