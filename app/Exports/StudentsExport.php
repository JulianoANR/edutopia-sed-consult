<?php

namespace App\Exports;

use App\Mappers\StudentExportMapper;

class StudentsExport
{
    protected $studentsData;
    protected $additionalData;
    protected $useSimplified;
    protected $selectedFields;

    public function __construct($studentsData, $additionalData = [], $useSimplified = true, $selectedFields = [])
    {
        $this->studentsData = $studentsData;
        $this->additionalData = $additionalData;
        $this->useSimplified = $useSimplified;
        $this->selectedFields = $selectedFields;
    }

    public function exportCsv()
    {
        // Se campos específicos foram selecionados, usar apenas eles
        if (!empty($this->selectedFields)) {
            $fieldsConfig = $this->getFieldsConfig();
            $headers = [];
            $csvData = [];
            
            // Construir cabeçalhos baseados nos campos selecionados
            foreach ($this->selectedFields as $fieldKey) {
                if (isset($fieldsConfig[$fieldKey])) {
                    $headers[] = $fieldsConfig[$fieldKey]['label'];
                } else {
                    // Fallback para campos não encontrados
                    $headers[] = ucfirst(str_replace('_', ' ', $fieldKey));
                }
            }
            
            $csvData[] = $headers;
            
            foreach ($this->studentsData as $index => $student) {
                $studentAdditionalData = $this->additionalData[$index] ?? [];
                $row = [];
                
                foreach ($this->selectedFields as $fieldKey) {
                    if (isset($fieldsConfig[$fieldKey])) {
                        $fieldConfig = $fieldsConfig[$fieldKey];
                        $value = $this->getFieldValue($student, $studentAdditionalData, $fieldConfig);
                        $row[] = $value;
                    } else {
                        // Fallback para campos não encontrados
                        $row[] = '';
                    }
                }
                
                $csvData[] = $row;
            }
        } else {
            // Usar o mapeador padrão quando nenhum campo específico foi selecionado
            $headers = $this->useSimplified 
                ? StudentExportMapper::getSimplifiedHeaders()
                : StudentExportMapper::getHeaders();
            
            $csvData = [];
            $csvData[] = $headers;
            
            foreach ($this->studentsData as $index => $student) {
                $studentAdditionalData = $this->additionalData[$index] ?? [];
                
                $mappedData = $this->useSimplified 
                    ? StudentExportMapper::mapStudentDataSimplified($student, $studentAdditionalData)
                    : StudentExportMapper::mapStudentData($student, $studentAdditionalData);
                
                $csvData[] = $mappedData;
            }
        }
        
        return $csvData;
    }

    private function getFieldsConfig()
    {
        return [
            'ra' => [
                'label' => 'RA',
                'path' => 'outDadosPessoais.outNumRA+outDadosPessoais.outDigitoRA',
                'type' => 'string',
                'formatter' => 'ra'
            ],
            'nome' => [
                'label' => 'Nome',
                'path' => 'outDadosPessoais.outNomeAluno',
                'type' => 'string'
            ],
            'data_nascimento' => [
                'label' => 'Data Nascimento',
                'path' => 'outDadosPessoais.outDataNascimento',
                'type' => 'date'
            ],
            'sexo' => [
                'label' => 'Sexo',
                'path' => 'outDadosPessoais.outSexo',
                'type' => 'string'
            ],
            'cor_raca' => [
                'label' => 'Cor/Raça',
                'path' => 'outDadosPessoais.outDescCorRaca',
                'type' => 'string'
            ],
            'nome_mae' => [
                'label' => 'Nome da Mãe',
                'path' => 'outDadosPessoais.outNomeMae',
                'type' => 'string'
            ],
            'nome_pai' => [
                'label' => 'Nome do Pai',
                'path' => 'outDadosPessoais.outNomePai',
                'type' => 'string'
            ],
            'situacao_matricula' => [
                'label' => 'Situação Matrícula',
                'path' => 'additionalData.situacao_matricula',
                'type' => 'string'
            ],
            'turma' => [
                'label' => 'Turma',
                'path' => 'additionalData.turma',
                'type' => 'string'
            ],
            'turno' => [
                'label' => 'Turno',
                'path' => 'additionalData.turno',
                'type' => 'string'
            ],
            'tipo_ensino' => [
                'label' => 'Tipo Ensino',
                'path' => 'additionalData.tipo_ensino',
                'type' => 'string'
            ],
            'cod_tipo_ensino' => [
                'label' => 'Código Tipo Ensino',
                'path' => 'additionalData.cod_tipo_ensino',
                'type' => 'string'
            ],
            'tipo_classe' => [
                'label' => 'Tipo Classe',
                'path' => 'additionalData.tipo_classe',
                'type' => 'string'
            ],
            'cod_tipo_classe' => [
                'label' => 'Código Tipo Classe',
                'path' => 'additionalData.cod_tipo_classe',
                'type' => 'string'
            ],
            'escola' => [
                'label' => 'Escola',
                'path' => 'additionalData.escola',
                'type' => 'string'
            ],
            'codigo_escola' => [
                'label' => 'Código Escola',
                'path' => 'additionalData.codigo_escola',
                'type' => 'string'
            ],
            'data_inicio_matricula' => [
                'label' => 'Data Início Matrícula',
                'path' => 'additionalData.data_inicio_matricula',
                'type' => 'date'
            ],
            'data_fim_matricula' => [
                'label' => 'Data Fim Matrícula',
                'path' => 'additionalData.data_fim_matricula',
                'type' => 'date'
            ],
            'cpf' => [
                'label' => 'CPF',
                'path' => 'outDadosPessoais.outNrCpf',
                'type' => 'string',
                'formatter' => 'cpf'
            ],
            'rg' => [
                'label' => 'RG',
                'path' => 'outDadosPessoais.outNrRg',
                'type' => 'string'
            ],
            'uf_ra' => [
                'label' => 'UF RA',
                'path' => 'outDadosPessoais.outSiglaUFRA',
                'type' => 'string'
            ],
            'telefones' => [
                'label' => 'Telefones',
                'path' => 'outTelefones',
                'type' => 'array',
                'formatter' => 'telefones'
            ],
            'emails' => [
                'label' => 'E-mails',
                'path' => 'outEmails',
                'type' => 'array',
                'formatter' => 'emails'
            ],
            'endereco_completo' => [
                'label' => 'Endereço Completo',
                'path' => 'outEndereco',
                'type' => 'object',
                'formatter' => 'endereco'
            ]
        ];
    }

    private function getFieldValue($student, $additionalData, $fieldConfig)
    {
        $path = $fieldConfig['path'];
        $value = '';
        
        // Verificar se é um campo de additionalData
        if (strpos($path, 'additionalData.') === 0) {
            $fieldKey = str_replace('additionalData.', '', $path);
            $value = $additionalData[$fieldKey] ?? '';
        } else {
            // Navegar pelo caminho do objeto para obter o valor
            $value = $this->navigateObjectPath($student, $path);
        }
        
        // Aplicar formatação se especificada
        if (isset($fieldConfig['formatter'])) {
            $value = $this->applyFormatter($value, $fieldConfig['formatter'], $student, $additionalData);
        }
        
        // Aplicar formatação por tipo
        if (isset($fieldConfig['type']) && $fieldConfig['type'] === 'date') {
            $value = $this->formatDate($value);
        }
        
        return $value;
    }
    
    private function navigateObjectPath($data, $path)
    {
        // Tratar casos especiais como concatenação (ex: outNumRA+outDigitoRA)
        if (strpos($path, '+') !== false) {
            $parts = explode('+', $path);
            $result = '';
            foreach ($parts as $part) {
                $result .= $this->navigateObjectPath($data, trim($part));
            }
            return $result;
        }
        
        $keys = explode('.', $path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } elseif (is_object($current) && isset($current->$key)) {
                $current = $current->$key;
            } else {
                return '';
            }
        }
        
        return $current ?? '';
    }
    
    private function applyFormatter($value, $formatter, $student, $additionalData = [])
    {
        switch ($formatter) {
            case 'ra':
                // RA já é concatenado no path
                return $value;
                
            case 'cpf':
                return $this->formatCpf($value);
                
            case 'telefones':
                if (is_array($value)) {
                    $phones = [];
                    foreach ($value as $phone) {
                        if (isset($phone['outNumTelefone'])) {
                            $phones[] = $this->formatPhone($phone['outNumTelefone']);
                        }
                    }
                    return implode(', ', $phones);
                }
                return '';
                
            case 'emails':
                if (is_array($value)) {
                    $emails = [];
                    foreach ($value as $email) {
                        if (isset($email['outEmail'])) {
                            $emails[] = $email['outEmail'];
                        }
                    }
                    return implode(', ', $emails);
                }
                return '';
                
            case 'endereco':
                if (is_array($value)) {
                    return $this->formatAddress($value);
                }
                return '';
                
            default:
                return $value;
        }
    }

    private function formatDate($date)
    {
        if (empty($date)) return '';
        
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('d/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    private function formatCpf($cpf)
    {
        if (empty($cpf)) return '';
        
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        
        return $cpf;
    }

    private function formatPhone($phones)
    {
        if (empty($phones) || !is_array($phones)) return '';
        
        $formattedPhones = [];
        foreach ($phones as $phone) {
            if (!empty($phone['outNrTelefone'])) {
                $number = preg_replace('/\D/', '', $phone['outNrTelefone']);
                if (strlen($number) >= 10) {
                    $formattedPhones[] = $number;
                }
            }
        }
        
        return implode(', ', $formattedPhones);
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
}