/**
 * Configuração dos campos disponíveis para exportação de alunos
 * 
 * Cada campo possui:
 * - key: identificador único do campo
 * - label: texto exibido para o usuário
 * - path: caminho relativo ao objeto de dados do aluno da API
 * - type: tipo de dado (string, date, etc.)
 * - default: se deve estar selecionado por padrão
 * - category: categoria do campo para organização
 */

export const EXPORT_FIELD_CATEGORIES = {
    BASIC: 'Dados Básicos',
    PERSONAL: 'Dados Pessoais', 
    ACADEMIC: 'Dados Acadêmicos',
    CONTACT: 'Contato',
    ADDRESS_RESIDENTIAL: 'Endereço Residencial',
    ADDRESS_INDICATIVE: 'Endereço Indicativo',
    SOCIAL: 'Dados Sociais',
    DEFICIENT: 'Deficiência',
};

export const EXPORT_FIELDS = [
    // Dados Básicos (campos atuais do sistema)
    {
        key: 'ra',
        label: 'RA',
        path: 'outDadosPessoais.outNumRA+outDadosPessoais.outDigitoRA',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC,
        formatter: (data) => {
            const dadosPessoais = data.outDadosPessoais || {};
            return (dadosPessoais.outNumRA || '') + '-' + (dadosPessoais.outDigitoRA || '');
        }
    },
    {
        key: 'nome',
        label: 'Nome',
        path: 'outDadosPessoais.outNomeAluno',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'data_nascimento',
        label: 'Data Nascimento',
        path: 'outDadosPessoais.outDataNascimento',
        type: 'date',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'sexo',
        label: 'Sexo',
        path: 'outDadosPessoais.outSexo',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    // {
    //     key: 'cor_raca_cod',
    //     label: 'Código da Cor/Raça',
    //     path: 'outDadosPessoais.outCorRaca',
    //     type: 'string',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.BASIC
    // }, 

    {
        key: 'cor_raca',
        label: 'Cor/Raça',
        path: 'outDadosPessoais.outDescCorRaca',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nome_mae',
        label: 'Nome da Mãe',
        path: 'outDadosPessoais.outNomeMae',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nome_pai',
        label: 'Nome do Pai',
        path: 'outDadosPessoais.outNomePai',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nome_social',
        label: 'Nome Social',
        path: 'outDadosPessoais.outNomeSocial',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nome_afetivo',
        label: 'Nome Afetivo',
        path: 'outDadosPessoais.outNomeAfetivo',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nacionalidade',
        label: 'Nacionalidade',
        path: 'outDadosPessoais.outDescNacionalidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    // {
    //     key: 'nacionalidade_cod',
    //     label: 'Código da Nacionalidade',
    //     path: 'outDadosPessoais.outCodPaisOrigem',
    //     type: 'string',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.BASIC
    // },
    {
        key: 'nacionalidade_nome',
        label: 'País de Origem',
        path: 'outDadosPessoais.outNomePaisOrigem',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'data_entrada_pais',
        label: 'Data Entrada País',
        path: 'outDadosPessoais.outDataEntradaPais',
        type: 'date',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'nome_municipio_nascimento',
        label: 'Nome Município Nascimento',
        path: 'outDadosPessoais.outNomeMunNascto',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    {
        key: 'uf_municipio_nascimento',
        label: 'UF Município Nascimento',
        path: 'outDadosPessoais.outUFMunNascto',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.BASIC
    },
    

    // Dados Acadêmicos (campos adicionais disponíveis)
    {
        key: 'situacao_matricula',
        label: 'Situação Matrícula',
        path: 'additionalData.situacao_matricula',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'turma',
        label: 'Turma',
        path: 'additionalData.turma',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'turno',
        label: 'Turno',
        path: 'additionalData.turno',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'tipo_ensino',
        label: 'Tipo Ensino',
        path: 'additionalData.tipo_ensino',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'cod_tipo_ensino',
        label: 'Código Tipo Ensino',
        path: 'additionalData.cod_tipo_ensino',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'tipo_classe',
        label: 'Tipo Classe',
        path: 'additionalData.tipo_classe',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'cod_tipo_classe',
        label: 'Código Tipo Classe',
        path: 'additionalData.cod_tipo_classe',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'escola',
        label: 'Escola',
        path: 'additionalData.escola',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'codigo_escola',
        label: 'Código Escola',
        path: 'additionalData.codigo_escola',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'data_inicio_matricula',
        label: 'Data Início Matrícula',
        path: 'additionalData.data_inicio_matricula',
        type: 'date',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'data_fim_matricula',
        label: 'Data Fim Matrícula',
        path: 'additionalData.data_fim_matricula',
        type: 'date',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'aluno_falecido',
        label: 'Aluno Falecido',
        path: 'outDadosPessoais.outAlunoFalecido',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },
    {
        key: 'data_falecimento',
        label: 'Data Falecimento',
        path: 'outDadosPessoais.outDataFalecimento',
        type: 'date',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ACADEMIC
    },

    // Dados Pessoais Adicionais (campos comentados no mapper original)
    {
        key: 'cpf',
        label: 'CPF',
        path: 'outDocumentos.outCPF',
        type: 'string',
        default: true,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
    },
    {
        key: 'codinep',
        label: 'Código INEP/MEC',
        path: 'outDocumentos.outCodINEP',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
    },
    {
        key: 'num_nis',
        label: 'Número NIS',
        path: 'outDocumentos.outNumNIS',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
    },
    {
        key: 'num_docto_civil',
        label: 'Número Documento Civil (RG ou RNE)',
        path: 'outDocumentos.outNumDoctoCivil',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
        formatter: (data) => {
            const num = data.outNumDoctoCivil || '';
            const dig = data.outDigitoDoctoCivil || '';
            return num + (dig ? '-' + dig : '');
        }
    },
    {
        key: 'num_cns',
        label: 'Nº Carteira Nacional de Saúde',
        path: 'outDocumentos.outNumeroCNS',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
    },
    {
        key: 'num_cin',
        label: 'N° Carteira de Identificação Nacional',
        path: 'outDocumentos.outNumCartaoIdentidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.PERSONAL,
    },

    // {
    //     key: 'uf_ra',
    //     label: 'UF RA',
    //     path: 'outDadosPessoais.outSiglaUFRA',
    //     type: 'string',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.PERSONAL
    // },

    // Contato
    {
        key: 'telefones',
        label: 'Telefones',
        path: 'outTelefones',
        type: 'array',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.CONTACT,
        formatter: (data) => {
            const telefones = data.outTelefones || [];
            if (!Array.isArray(telefones) || telefones.length === 0) return '';
            
            const phones = telefones.map(telefone => {
                let phone = telefone.outTelefone || '';
                let ddd = telefone.outDDDNumero || '';
                if (ddd) {
                    phone = '(' + ddd + ')' + phone;
                }
                if (telefone.outTipoTelefone) {
                    phone += ' (' + telefone.outTipoTelefone + ')';
                }
                return phone;
            }).filter(phone => phone.trim() !== '');
            
            return phones.join(', ');
        }
    },
    {
        key: 'email',
        label: 'E-mail',
        path: 'outDadosPessoais.outEmail',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.CONTACT
    },
    // {
    //     key: 'emails',
    //     label: 'E-mails',
    //     path: 'outEmails',
    //     type: 'array',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.CONTACT,
    //     formatter: (data) => {
    //         const emails = data.outEmails || [];
    //         if (!Array.isArray(emails) || emails.length === 0) return '';
            
    //         const emailList = emails.map(email => email.outEmail || '').filter(email => email.trim() !== '');
    //         return emailList.join(', ');
    //     }
    // },

    // Endereço Residencial
    // {
    //     key: 'endereco_completo',
    //     label: 'Endereço Completo',
    //     path: 'outEndereco',
    //     type: 'object',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.ADDRESS,
    //     formatter: (data) => {
    //         const endereco = data.outEndereco || {};
    //         const parts = [];
            
    //         if (endereco.outLogradouro) parts.push(endereco.outLogradouro);
    //         if (endereco.outNumero) parts.push('nº ' + endereco.outNumero);
    //         if (endereco.outComplemento) parts.push(endereco.outComplemento);
    //         if (endereco.outBairro) parts.push(endereco.outBairro);
    //         if (endereco.outCidade) parts.push(endereco.outCidade);
            
    //         return parts.join(', ');
    //     }
    // },
    {
        key: 'logradouro',
        label: 'Logradouro',
        path: 'outEnderecoResidencial.outLogradouro',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'numero',
        label: 'Número',
        path: 'outEnderecoResidencial.outNumero',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'cod_area',
        label: 'Código Área',
        path: 'outEnderecoResidencial.outCodArea',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'area_logradouro',
        label: 'Área Logradouro (Urbana/Rural)',
        path: 'outEnderecoResidencial.outAreaLogradouro',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'complemento',
        label: 'Complemento',
        path: 'outEnderecoResidencial.outComplemento',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'bairro',
        label: 'Bairro',
        path: 'outEnderecoResidencial.outBairro',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'cep',
        label: 'CEP',
        path: 'outEnderecoResidencial.outCep',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'cidade',
        label: 'Cidade',
        path: 'outEnderecoResidencial.outNomeCidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'uf_cidade',
        label: 'UF Cidade',
        path: 'outEnderecoResidencial.outUFCidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    // {
    //     key: 'cod_municipio_dne',
    //     label: 'Código Município DNE',
    //     path: 'outEnderecoResidencial.outCodMunicipioDNE',
    //     type: 'string',
    //     default: false,
    //     category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    // },
    {
        key: 'latitude',
        label: 'Latitude',
        path: 'outEnderecoResidencial.outLatitude',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'longitude',
        label: 'Longitude',
        path: 'outEnderecoResidencial.outLongitude',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },
    {
        key: 'localizacao_diferenciada',
        label: 'Localização Diferenciada',
        path: 'outEnderecoResidencial.outLocalizacaoDiferenciada',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_RESIDENTIAL,
    },

    // Endereço Indicativo
    {
        key: 'cep',
        label: 'CEP',
        path: 'outEnderecoIndicativo.outCep',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'logradouro',
        label: 'Logradouro',
        path: 'outEnderecoIndicativo.outLogradouro',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'numero',
        label: 'Número',
        path: 'outEnderecoIndicativo.outNumero',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },  
    {
        key: 'bairro',
        label: 'Bairro',
        path: 'outEnderecoIndicativo.outBairro',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'cidade',
        label: 'Cidade',
        path: 'outEnderecoIndicativo.outNomeCidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'uf_cidade',
        label: 'UF Cidade',
        path: 'outEnderecoIndicativo.outUFCidade',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'latitude',
        label: 'Latitude',
        path: 'outEnderecoIndicativo.outLatitude',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },
    {
        key: 'longitude',
        label: 'Longitude',
        path: 'outEnderecoIndicativo.outLongitude',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.ADDRESS_INDICATIVE,
    },

    // Dados Sociais
    {
        key: 'bolsa_familia',
        label: 'Código Bolsa Família',
        path: 'outDadosPessoais.outCodBolsaFamilia',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
        formatter: (data) => {
            const bolsaFamilia = data.outDadosPessoais?.outCodBolsaFamilia || '';
            return bolsaFamilia === '1' ? 'SIM' : 'NÃO';
        }
    },
    {
        key: 'bolsa_familia',
        label: 'Bolsa Família',
        path: 'outDadosPessoais.outBolsaFamilia',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },
    {
        key: 'possui_internet',
        label: 'Possui Internet',
        path: 'outDadosPessoais.outPossuiInternet',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },
    {
        key: 'possui_notebook_smartphone_tablet',
        label: 'Possui Notebook/Smartphone/Tablet',
        path: 'outDadosPessoais.outPossuiNotebookSmartphoneTablet',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },
    {
        key: 'quilombola',
        label: 'Quilombola',
        path: 'outDadosPessoais.outQuilombola',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },
    {
        key: 'tipo_sanguineo',
        label: 'Tipo Sanguíneo',
        path: 'outDadosPessoais.outTipoSanguineo',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },
    {
        key: 'doador_orgaos',
        label: 'Doador de Orgãos',
        path: 'outDadosPessoais.outDoadorOrgaos',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.SOCIAL,
    },

    // Deficiência
    {
        key: 'mobilidade_reduzida',
        label: 'Mobilidade Reduzida',
        path: 'outDeficiencia.outMobilidadeReduzida',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const mobilidadeReduzida = data.outDeficiencia?.outMobilidadeReduzida || '';
            return mobilidadeReduzida ? 'SIM' : 'NÃO';
        }
    },
    {
        key: 'tipo_mobilidade_reduzida',
        label: 'Tipo Mobilidade Reduzida',
        path: 'outDadosPessoais.outTipoMobilidadeReduzida',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const tipoMobilidadeReduzida = data.outDeficiencia?.outTipoMobilidadeReduzida || '';
            return tipoMobilidadeReduzida === 'T' ? 'TEMPORÁRIO' : (tipoMobilidadeReduzida === 'P' ? 'PERMANENTE' : '');
        }
    },
    {
        key: 'nivel_suporte',
        label: 'Nível Suporte',
        path: 'outDeficiencia.outCodigoNivelSuporte',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const nivelSuporte = data.outDeficiencia?.outCodigoNivelSuporte || '';
            return nivelSuporte === '0' ? 'NÃO POSSUI' : (nivelSuporte === '1' ? 'NÍVEL 1' : (nivelSuporte === '2' ? 'NÍVEL 2' : 'NÍVEL 3'));
        }
    },
    {
        key: 'profissional_apoio_escolar',
        label: 'Profissional Apoio Escolar',
        path: 'outDeficiencia.outProfissionalApoioEscolar',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const profissionalApoioEscolar = data.outDeficiencia?.outProfissionalApoioEscolar || '';
            return profissionalApoioEscolar === '0' ? 'NÃO NECESSITA' : (profissionalApoioEscolar === '1' ? 'NECESSITA' : '');
        }
    },
    {
        key: 'transtorno_deficiencia',
        label: 'Transtorno Deficiência',
        path: 'outDeficiencia.outFlTranstornoDeficiencia',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const transtornoDeficiencia = data.outDeficiencia?.outFlTranstornoDeficiencia || '';
            return transtornoDeficiencia === 'S' ? 'SIM' : 'NÃO';
        }
    },
    {
        key: 'transtorno_aprendizagem',
        label: 'Transtorno Aprendizagem',
        path: 'outDeficiencia.outFlTranstornoAprendizagem',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const transtornoAprendizagem = data.outDeficiencia?.outFlTranstornoAprendizagem || '';
            return transtornoAprendizagem === 'S' ? 'SIM' : 'NÃO';
        }
    },
    {
        key: 'investigacao_deficiencia',
        label: 'Investigação Deficiência',
        path: 'outDeficiencia.outFlInvestigacaoDeficiencia',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const investigacaoDeficiencia = data.outDeficiencia?.outFlInvestigacaoDeficiencia || '';
            return investigacaoDeficiencia === 'S' ? 'SIM' : 'NÃO';
        }
    },
    {
        key: 'lista_necessidades_especiais',
        label: 'Lista Necessidades Especiais',
        path: 'outListaNecessidadesEspeciais',
        type: 'string',
        default: false,
        category: EXPORT_FIELD_CATEGORIES.DEFICIENT,
        formatter: (data) => {
            const listaNecessidadesEspeciais = data.outListaNecessidadesEspeciais || [];
            return listaNecessidadesEspeciais.map(item => item.outNomeNecesEspecial).join(', ');
        }
    },
];

/**
 * Obter campos padrão (campos que estão selecionados por padrão)
 */
export const getDefaultFields = () => {
    return EXPORT_FIELDS.filter(field => field.default);
};

/**
 * Obter campos por categoria
 */
export const getFieldsByCategory = () => {
    const categories = {};
    
    EXPORT_FIELDS.forEach(field => {
        if (!categories[field.category]) {
            categories[field.category] = [];
        }
        categories[field.category].push(field);
    });
    
    return categories;
};

/**
 * Obter campo por key
 */
export const getFieldByKey = (key) => {
    return EXPORT_FIELDS.find(field => field.key === key);
};

/**
 * Extrair valor do campo usando o path
 */
export const extractFieldValue = (data, field) => {
    if (field.formatter) {
        return field.formatter(data);
    }
    
    // Para campos simples, usar o path
    const pathParts = field.path.split('.');
    let value = data;
    
    for (const part of pathParts) {
        if (value && typeof value === 'object') {
            value = value[part];
        } else {
            return '';
        }
    }
    
    // Formatação de data
    if (field.type === 'date' && value) {
        try {
            const date = new Date(value);
            if (!isNaN(date.getTime())) {
                return date.toLocaleDateString('pt-BR');
            }
        } catch (e) {
            // Se não conseguir formatar, retorna o valor original
        }
    }
    
    return value || '';
};