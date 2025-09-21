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

    public function getFieldsConfig()
    {
        return [
            // Dados Básicos
            'ra' => [
                'label' => 'RA',
                'path' => 'outDadosPessoais.outNumRA+outDadosPessoais.outDigitoRA',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC',
                'formatter' => function($data) {
                    $num = $data['outDadosPessoais']['outNumRA'] ?? '';
                    $digit = $data['outDadosPessoais']['outDigitoRA'] ?? '';
                    return $num . ($digit ? '-' . $digit : '');
                }
            ],
            'nome' => [
                'label' => 'Nome',
                'path' => 'outDadosPessoais.outNomeAluno',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC'
            ],
            'data_nascimento' => [
                'label' => 'Data Nascimento',
                'path' => 'outDadosPessoais.outDataNascimento',
                'type' => 'date',
                'default' => true,
                'category' => 'BASIC'
            ],
            'sexo' => [
                'label' => 'Sexo',
                'path' => 'outDadosPessoais.outSexo',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC'
            ],
            'cor_raca' => [
                'label' => 'Cor/Raça',
                'path' => 'outDadosPessoais.outDescCorRaca',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC'
            ],
            'nome_mae' => [
                'label' => 'Nome da Mãe',
                'path' => 'outDadosPessoais.outNomeMae',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC'
            ],
            'nome_pai' => [
                'label' => 'Nome do Pai',
                'path' => 'outDadosPessoais.outNomePai',
                'type' => 'string',
                'default' => true,
                'category' => 'BASIC'
            ],
            'nome_social' => [
                'label' => 'Nome Social',
                'path' => 'outDadosPessoais.outNomeSocial',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],
            'nome_afetivo' => [
                'label' => 'Nome Afetivo',
                'path' => 'outDadosPessoais.outNomeAfetivo',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],
            'nacionalidade' => [
                'label' => 'Nacionalidade',
                'path' => 'outDadosPessoais.outDescNacionalidade',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],
            'nacionalidade_nome' => [
                'label' => 'País de Origem',
                'path' => 'outDadosPessoais.outNomePaisOrigem',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],
            'data_entrada_pais' => [
                'label' => 'Data Entrada País',
                'path' => 'outDadosPessoais.outDataEntradaPais',
                'type' => 'date',
                'default' => false,
                'category' => 'BASIC'
            ],
            'nome_municipio_nascimento' => [
                'label' => 'Nome Município Nascimento',
                'path' => 'outDadosPessoais.outNomeMunNascto',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],
            'uf_municipio_nascimento' => [
                'label' => 'UF Município Nascimento',
                'path' => 'outDadosPessoais.outUFMunNascto',
                'type' => 'string',
                'default' => false,
                'category' => 'BASIC'
            ],

            // Dados Acadêmicos
            'situacao_matricula' => [
                'label' => 'Situação Matrícula',
                'path' => 'additionalData.situacao_matricula',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'turma' => [
                'label' => 'Turma',
                'path' => 'additionalData.turma',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'turno' => [
                'label' => 'Turno',
                'path' => 'additionalData.turno',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'tipo_ensino' => [
                'label' => 'Tipo Ensino',
                'path' => 'additionalData.tipo_ensino',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'cod_tipo_ensino' => [
                'label' => 'Código Tipo Ensino',
                'path' => 'additionalData.cod_tipo_ensino',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'tipo_classe' => [
                'label' => 'Tipo Classe',
                'path' => 'additionalData.tipo_classe',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'cod_tipo_classe' => [
                'label' => 'Código Tipo Classe',
                'path' => 'additionalData.cod_tipo_classe',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'escola' => [
                'label' => 'Escola',
                'path' => 'additionalData.escola',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'codigo_escola' => [
                'label' => 'Código Escola',
                'path' => 'additionalData.codigo_escola',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'data_inicio_matricula' => [
                'label' => 'Data Início Matrícula',
                'path' => 'additionalData.data_inicio_matricula',
                'type' => 'date',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'data_fim_matricula' => [
                'label' => 'Data Fim Matrícula',
                'path' => 'additionalData.data_fim_matricula',
                'type' => 'date',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'aluno_falecido' => [
                'label' => 'Aluno Falecido',
                'path' => 'outDadosPessoais.outAlunoFalecido',
                'type' => 'string',
                'default' => false,
                'category' => 'ACADEMIC'
            ],
            'data_falecimento' => [
                'label' => 'Data Falecimento',
                'path' => 'outDadosPessoais.outDataFalecimento',
                'type' => 'date',
                'default' => false,
                'category' => 'ACADEMIC'
            ],

            // Dados Pessoais/Documentos
            'cpf' => [
                'label' => 'CPF',
                'path' => 'outDocumentos.outCPF',
                'type' => 'string',
                'default' => true,
                'category' => 'PERSONAL',
                'formatter' => 'formatCpf'
            ],
            'codinep' => [
                'label' => 'Código INEP/MEC',
                'path' => 'outDocumentos.outCodINEP',
                'type' => 'string',
                'default' => false,
                'category' => 'PERSONAL'
            ],
            'num_nis' => [
                'label' => 'Número NIS',
                'path' => 'outDocumentos.outNumNIS',
                'type' => 'string',
                'default' => false,
                'category' => 'PERSONAL'
            ],
            'num_docto_civil' => [
                'label' => 'Número Documento Civil (RG ou RNE)',
                'path' => 'outDocumentos.outNumDoctoCivil',
                'type' => 'string',
                'default' => false,
                'category' => 'PERSONAL',
                'formatter' => function($data) {
                    $num = $data['outDocumentos']['outNumDoctoCivil'] ?? '';
                    $dig = $data['outDocumentos']['outDigitoDoctoCivil'] ?? '';
                    return $num . ($dig ? '-' . $dig : '');
                }
            ],
            'num_cns' => [
                'label' => 'Nº Carteira Nacional de Saúde',
                'path' => 'outDocumentos.outNumeroCNS',
                'type' => 'string',
                'default' => false,
                'category' => 'PERSONAL'
            ],
            'num_cin' => [
                'label' => 'N° Carteira de Identificação Nacional',
                'path' => 'outDocumentos.outNumCartaoIdentidade',
                'type' => 'string',
                'default' => false,
                'category' => 'PERSONAL'
            ],

            // Contato
            'telefones' => [
                'label' => 'Telefones',
                'path' => 'outTelefones',
                'type' => 'array',
                'default' => false,
                'category' => 'CONTACT',
                'formatter' => function($data) {
                    if (!isset($data['outTelefones']) || !is_array($data['outTelefones'])) {
                        return '';
                    }
                    
                    $telefones = [];
                    foreach ($data['outTelefones'] as $telefone) {
                        if (isset($telefone['outNumTelefone']) && !empty($telefone['outNumTelefone'])) {
                            $telefones[] = $telefone['outNumTelefone'];
                        }
                    }
                    
                    return implode(', ', $telefones);
                }
            ],
            'email' => [
                'label' => 'E-mail',
                'path' => 'outDadosPessoais.outEmail',
                'type' => 'string',
                'default' => false,
                'category' => 'CONTACT'
            ],

            // Endereço Residencial
            'logradouro' => [
                'label' => 'Logradouro',
                'path' => 'outEnderecoResidencial.outLogradouro',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'numero' => [
                'label' => 'Número',
                'path' => 'outEnderecoResidencial.outNumero',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'cod_area' => [
                'label' => 'Código Área',
                'path' => 'outEnderecoResidencial.outCodArea',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'area_logradouro' => [
                'label' => 'Área Logradouro (Urbana/Rural)',
                'path' => 'outEnderecoResidencial.outAreaLogradouro',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'complemento' => [
                'label' => 'Complemento',
                'path' => 'outEnderecoResidencial.outComplemento',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'bairro' => [
                'label' => 'Bairro',
                'path' => 'outEnderecoResidencial.outBairro',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'cep' => [
                'label' => 'CEP',
                'path' => 'outEnderecoResidencial.outCep',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'cidade' => [
                'label' => 'Cidade',
                'path' => 'outEnderecoResidencial.outNomeCidade',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'uf_cidade' => [
                'label' => 'UF Cidade',
                'path' => 'outEnderecoResidencial.outUFCidade',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'latitude' => [
                'label' => 'Latitude',
                'path' => 'outEnderecoResidencial.outLatitude',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'longitude' => [
                'label' => 'Longitude',
                'path' => 'outEnderecoResidencial.outLongitude',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],
            'localizacao_diferenciada' => [
                'label' => 'Localização Diferenciada',
                'path' => 'outEnderecoResidencial.outLocalizacaoDiferenciada',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_RESIDENTIAL'
            ],

            // Endereço Indicativo
            'cep_indicativo' => [
                'label' => 'CEP Indicativo',
                'path' => 'outEnderecoIndicativo.outCep',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'logradouro_indicativo' => [
                'label' => 'Logradouro Indicativo',
                'path' => 'outEnderecoIndicativo.outLogradouro',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'numero_indicativo' => [
                'label' => 'Número Indicativo',
                'path' => 'outEnderecoIndicativo.outNumero',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'bairro_indicativo' => [
                'label' => 'Bairro Indicativo',
                'path' => 'outEnderecoIndicativo.outBairro',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'cidade_indicativo' => [
                'label' => 'Cidade Indicativo',
                'path' => 'outEnderecoIndicativo.outNomeCidade',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'uf_cidade_indicativo' => [
                'label' => 'UF Cidade Indicativo',
                'path' => 'outEnderecoIndicativo.outUFCidade',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'latitude_indicativo' => [
                'label' => 'Latitude Indicativo',
                'path' => 'outEnderecoIndicativo.outLatitude',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],
            'longitude_indicativo' => [
                'label' => 'Longitude Indicativo',
                'path' => 'outEnderecoIndicativo.outLongitude',
                'type' => 'string',
                'default' => false,
                'category' => 'ADDRESS_INDICATIVE'
            ],

            // Dados Sociais
            'bolsa_familia_codigo' => [
                'label' => 'Código Bolsa Família',
                'path' => 'outDadosPessoais.outCodBolsaFamilia',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL',
                'formatter' => function($data) {
                    return $data['outDadosPessoais']['outCodBolsaFamilia'] ?? '';
                }
            ],
            'bolsa_familia' => [
                'label' => 'Bolsa Família',
                'path' => 'outDadosPessoais.outBolsaFamilia',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],
            'possui_internet' => [
                'label' => 'Possui Internet',
                'path' => 'outDadosPessoais.outPossuiInternet',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],
            'possui_notebook_smartphone_tablet' => [
                'label' => 'Possui Notebook/Smartphone/Tablet',
                'path' => 'outDadosPessoais.outPossuiNotebookSmartphoneTablet',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],
            'quilombola' => [
                'label' => 'Quilombola',
                'path' => 'outDadosPessoais.outQuilombola',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],
            'tipo_sanguineo' => [
                'label' => 'Tipo Sanguíneo',
                'path' => 'outDadosPessoais.outTipoSanguineo',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],
            'doador_orgaos' => [
                'label' => 'Doador de Orgãos',
                'path' => 'outDadosPessoais.outDoadorOrgaos',
                'type' => 'string',
                'default' => false,
                'category' => 'SOCIAL'
            ],

            // Deficiência
            'mobilidade_reduzida' => [
                'label' => 'Mobilidade Reduzida',
                'path' => 'outDeficiencia.outMobilidadeReduzida',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outMobilidadeReduzida'] ?? '';
                }
            ],
            'tipo_mobilidade_reduzida' => [
                'label' => 'Tipo Mobilidade Reduzida',
                'path' => 'outDadosPessoais.outTipoMobilidadeReduzida',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDadosPessoais']['outTipoMobilidadeReduzida'] ?? '';
                }
            ],
            'nivel_suporte' => [
                'label' => 'Nível Suporte',
                'path' => 'outDeficiencia.outCodigoNivelSuporte',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outCodigoNivelSuporte'] ?? '';
                }
            ],
            'profissional_apoio_escolar' => [
                'label' => 'Profissional Apoio Escolar',
                'path' => 'outDeficiencia.outProfissionalApoioEscolar',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outProfissionalApoioEscolar'] ?? '';
                }
            ],
            'transtorno_deficiencia' => [
                'label' => 'Transtorno Deficiência',
                'path' => 'outDeficiencia.outFlTranstornoDeficiencia',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outFlTranstornoDeficiencia'] ?? '';
                }
            ],
            'transtorno_aprendizagem' => [
                'label' => 'Transtorno Aprendizagem',
                'path' => 'outDeficiencia.outFlTranstornoAprendizagem',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outFlTranstornoAprendizagem'] ?? '';
                }
            ],
            'investigacao_deficiencia' => [
                'label' => 'Investigação Deficiência',
                'path' => 'outDeficiencia.outFlInvestigacaoDeficiencia',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    return $data['outDeficiencia']['outFlInvestigacaoDeficiencia'] ?? '';
                }
            ],
            'lista_necessidades_especiais' => [
                'label' => 'Lista Necessidades Especiais',
                'path' => 'outListaNecessidadesEspeciais',
                'type' => 'string',
                'default' => false,
                'category' => 'DEFICIENT',
                'formatter' => function($data) {
                    if (isset($data['outListaNecessidadesEspeciais']) && is_array($data['outListaNecessidadesEspeciais'])) {
                        return implode(', ', array_map(function($item) {
                            return $item['outNomeNecesEspecial'] ?? '';
                        }, $data['outListaNecessidadesEspeciais']));
                    }
                    return '';
                }
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
        // Se o formatter é uma função anônima, executá-la
        if (is_callable($formatter)) {
            return $formatter(array_merge($student, ['additionalData' => $additionalData]));
        }
        
        switch ($formatter) {
            case 'formatCpf':
                return $this->formatCpf($value);
                
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