<?php

namespace App\Exports;

use App\Mappers\StudentExportMapper;

class StudentsExport
{
    protected $studentsData;
    protected $additionalData;
    protected $useSimplified;

    public function __construct($studentsData, $additionalData = [], $useSimplified = true)
    {
        $this->studentsData = $studentsData;
        $this->additionalData = $additionalData;
        $this->useSimplified = $useSimplified;
    }

    public function exportCsv()
    {
        // Usar o mapeador para obter cabeçalhos
        $headers = $this->useSimplified 
            ? StudentExportMapper::getSimplifiedHeaders()
            : StudentExportMapper::getHeaders();
        
        $csvData = [];
        $csvData[] = $headers;
        
        foreach ($this->studentsData as $index => $student) {
            // Obter dados adicionais para este aluno específico
            $studentAdditionalData = $this->additionalData[$index] ?? [];
            
            // Usar o mapeador para formatar os dados
            $mappedData = $this->useSimplified 
                ? StudentExportMapper::mapStudentDataSimplified($student, $studentAdditionalData)
                : StudentExportMapper::mapStudentData($student, $studentAdditionalData);
            
            $csvData[] = $mappedData;
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