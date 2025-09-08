import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function StudentShow({ studentRa, studentData: initialStudentData, selectedSchool, error: initialError }) {
    const [studentData, setStudentData] = useState(initialStudentData);
    const [loading, setLoading] = useState(!initialStudentData && !initialError);
    const [error, setError] = useState(initialError);
    const [activeTab, setActiveTab] = useState('personal-data');

    const tabs = [
        { id: 'personal-data', name: 'Dados Pessoais', icon: '👤' },
        { id: 'documents', name: 'Documentos', icon: '📄' },
        { id: 'address', name: 'Endereços', icon: '🏠' },
        { id: 'family', name: 'Família', icon: '👨‍👩‍👧‍👦' },
        { id: 'special-needs', name: 'Necessidades Especiais', icon: '♿' },
        { id: 'additional-data', name: 'Informações Adicionais', icon: '📋' }
    ];

    useEffect(() => {
        if (!studentData && !error) {
            loadStudentData();
        }
    }, []);

    const loadStudentData = async () => {
        setLoading(true);
        setError(null);
        try {
            // Se não temos dados iniciais, podemos tentar recarregar via API
            // Por enquanto, vamos apenas definir loading como false
            setLoading(false);
        } catch (error) {
            console.error('Erro ao carregar dados do aluno:', error);
            setError('Não foi possível carregar os dados do aluno.');
            setLoading(false);
        }
    };

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
    };

    const renderPersonalData = () => {
        if (!studentData?.outDadosPessoais) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">👤</div>
                    <p className="text-gray-500">Dados pessoais não disponíveis</p>
                </div>
            );
        }

        const data = studentData.outDadosPessoais;

        return (
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">👤 Identificação</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nome Completo:</span>
                                <span className="font-medium text-right">{data.outNomeAluno || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nome Social:</span>
                                <span className="font-medium text-right">{data.outNomeSocial || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nome Afetivo:</span>
                                <span className="font-medium text-right">{data.outNomeAfetivo || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">RA:</span>
                                <span className="font-medium">{data.outNumRA}{data.outDigitoRA ? `-${data.outDigitoRA}` : ''}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF do RA:</span>
                                <span className="font-medium">{data.outSiglaUFRA || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📅 Dados Pessoais</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Data de Nascimento:</span>
                                <span className="font-medium">{data.outDataNascimento || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Sexo:</span>
                                <span className="font-medium">{data.outSexo || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Cor/Raça:</span>
                                <span className="font-medium">{data.outDescCorRaca || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nacionalidade:</span>
                                <span className="font-medium">{data.outDescNacionalidade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">País de Origem:</span>
                                <span className="font-medium">{data.outNomePaisOrigem || 'N/A'}</span>
                            </div>
                            {data.outDataEntradaPais && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Data de Entrada no País:</span>
                                    <span className="font-medium">{data.outDataEntradaPais}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">👨‍👩‍👧‍👦 Filiação</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex flex-col space-y-1">
                                <span className="text-gray-500">Nome da Mãe:</span>
                                <span className="font-medium">{data.outNomeMae || 'N/A'}</span>
                            </div>
                            <div className="flex flex-col space-y-1">
                                <span className="text-gray-500">Nome do Pai:</span>
                                <span className="font-medium">{data.outNomePai || 'N/A'}</span>
                            </div>
                            {data.outFiliacao3 && (
                                <div className="flex flex-col space-y-1">
                                    <span className="text-gray-500">Terceira Filiação:</span>
                                    <span className="font-medium">{data.outFiliacao3}</span>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🏥 Informações Médicas</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Tipo Sanguíneo:</span>
                                <span className="font-medium">{data.outTipoSanguineo || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Doador de Órgãos:</span>
                                <span className="font-medium">{data.outDoadorOrgaos || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Status:</span>
                                <span className={`font-medium ${
                                    data.outAlunoFalecido === 'SIM' ? 'text-red-600' : 'text-green-600'
                                }`}>
                                    {data.outAlunoFalecido === 'SIM' ? 'Falecido' : 'Ativo'}
                                </span>
                            </div>
                            {data.outDataFalecimento && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Data do Falecimento:</span>
                                    <span className="font-medium text-red-600">{data.outDataFalecimento}</span>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-500">Gêmeo:</span>
                                <span className="font-medium">{data.outGemeo || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🌍 Origem</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Município de Nascimento:</span>
                                <span className="font-medium">{data.outNomeMunNascto || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF de Nascimento:</span>
                                <span className="font-medium">{data.outUFMunNascto || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📧 E-mails</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">E-mail Principal:</span>
                                <span className="font-medium">{data.outEmail || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">E-mail Google:</span>
                                <span className="font-medium">{data.outEmailGoogle || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">E-mail Microsoft:</span>
                                <span className="font-medium">{data.outEmailMicrosoft || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {/* Seção de Telefones */}
                {studentData?.outTelefones && studentData.outTelefones.length > 0 && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📞 Telefones</h4>
                        <div className="space-y-4">
                            {studentData.outTelefones.map((telefone, index) => (
                                <div key={index} className="border-l-4 border-blue-500 pl-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Número:</span>
                                            <span className="font-medium">
                                                {telefone.outDDDNumero ? `(${telefone.outDDDNumero}) ` : ''}
                                                {telefone.outNumero || 'N/A'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Tipo:</span>
                                            <span className="font-medium">{telefone.outDescTipoTelefone || 'N/A'}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Complemento:</span>
                                            <span className="font-medium">{telefone.outComplemento || 'N/A'}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">SMS:</span>
                                            <span className="font-medium">{telefone.outSMS || 'N/A'}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const renderDocuments = () => {
        if (!studentData?.outDocumentos) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">📄</div>
                    <p className="text-gray-500">Documentos não disponíveis</p>
                </div>
            );
        }

        const docs = studentData.outDocumentos;
        const certNova = studentData.outCertidaoNova;
        const certAntiga = studentData.outCertidaoAntiga;

        return (
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📄 Documentos Principais</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">CPF:</span>
                                <span className="font-medium">{docs.outCPF || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Código INEP:</span>
                                <span className="font-medium">{docs.outCodINEP || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">NIS:</span>
                                <span className="font-medium">{docs.outNumNIS || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">CNS:</span>
                                <span className="font-medium">{docs.outNumeroCNS || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">CIN:</span>
                                <span className="font-medium">{docs.outCIN || 'N/A'}</span>
                            </div>
                            {docs.outDataEmissaoCIN && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Data Emissão CIN:</span>
                                    <span className="font-medium">{docs.outDataEmissaoCIN}</span>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📋 Documento Civil</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Número:</span>
                                <span className="font-medium">{docs.outNumDoctoCivil || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Dígito:</span>
                                <span className="font-medium">{docs.outDigitoDoctoCivil || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF:</span>
                                <span className="font-medium">{docs.outUFDoctoCivil || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Data de Emissão:</span>
                                <span className="font-medium">{docs.outDataEmissaoDoctoCivil || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Data Emissão Certidão:</span>
                                <span className="font-medium">{docs.outDataEmissaoCertidao || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {certNova && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📜 Certidão Nova (Matrícula)</h4>
                        <div className="grid grid-cols-3 gap-4 text-sm">
                            {Object.entries(certNova).map(([key, value]) => {
                                if (value) {
                                    const fieldName = key.replace('outCertMatr', 'Campo ');
                                    return (
                                        <div key={key} className="flex justify-between">
                                            <span className="text-gray-500">{fieldName}:</span>
                                            <span className="font-medium">{value}</span>
                                        </div>
                                    );
                                }
                                return null;
                            })}
                        </div>
                    </div>
                )}
                
                {certAntiga && Object.values(certAntiga).some(v => v) && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📜 Certidão Antiga</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Número da Certidão:</span>
                                <span className="font-medium">{certAntiga.outNumCertidao || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Livro de Registro:</span>
                                <span className="font-medium">{certAntiga.outNumLivroReg || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Folha:</span>
                                <span className="font-medium">{certAntiga.outFolhaRegNum || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Município/Comarca:</span>
                                <span className="font-medium">{certAntiga.outNomeMunComarca || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF Comarca:</span>
                                <span className="font-medium">{certAntiga.outUFComarca || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Distrito:</span>
                                <span className="font-medium">{certAntiga.outDistritoNasc || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                )}
                
                {studentData.outJustificativaDocumentos && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📝 Justificativa de Documentos</h4>
                        <p className="text-sm text-gray-700">{studentData.outJustificativaDocumentos}</p>
                    </div>
                )}
            </div>
        );
    };

    const renderAddress = () => {
        const endResidencial = studentData?.outEnderecoResidencial;
        const endIndicativo = studentData?.outEnderecoIndicativo;

        if (!endResidencial && !endIndicativo) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">🏠</div>
                    <p className="text-gray-500">Endereços não disponíveis</p>
                </div>
            );
        }

        return (
            <div className="space-y-6">
                {endResidencial && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🏠 Endereço Residencial</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Logradouro:</span>
                                <span className="font-medium">{endResidencial.outLogradouro || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Número:</span>
                                <span className="font-medium">{endResidencial.outNumero || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Complemento:</span>
                                <span className="font-medium">{endResidencial.outComplemento || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Bairro:</span>
                                <span className="font-medium">{endResidencial.outBairro || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Cidade:</span>
                                <span className="font-medium">{endResidencial.outNomeCidade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF:</span>
                                <span className="font-medium">{endResidencial.outUFCidade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">CEP:</span>
                                <span className="font-medium">{endResidencial.outCep || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Área do Logradouro:</span>
                                <span className="font-medium">{endResidencial.outAreaLogradouro || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Localização Diferenciada:</span>
                                <span className="font-medium">{endResidencial.outLocalizacaoDiferenciada || 'N/A'}</span>
                            </div>
                            {(endResidencial.outLatitude || endResidencial.outLongitude) && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Coordenadas:</span>
                                    <span className="font-medium">
                                        {endResidencial.outLatitude || 'N/A'}, {endResidencial.outLongitude || 'N/A'}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                )}
                
                {endIndicativo && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📍 Endereço Indicativo</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Logradouro:</span>
                                <span className="font-medium">{endIndicativo.outLogradouro || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Número:</span>
                                <span className="font-medium">{endIndicativo.outNumero || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Bairro:</span>
                                <span className="font-medium">{endIndicativo.outBairro || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Cidade:</span>
                                <span className="font-medium">{endIndicativo.outNomeCidade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">UF:</span>
                                <span className="font-medium">{endIndicativo.outUFCidade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">CEP:</span>
                                <span className="font-medium">{endIndicativo.outCep || 'N/A'}</span>
                            </div>
                            {(endIndicativo.outLatitude || endIndicativo.outLongitude) && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Coordenadas:</span>
                                    <span className="font-medium">
                                        {endIndicativo.outLatitude || 'N/A'}, {endIndicativo.outLongitude || 'N/A'}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const renderFamily = () => {
        const irmaos = studentData?.outIrmaos;

        if (!irmaos || irmaos.length === 0) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">👨‍👩‍👧‍👦</div>
                    <p className="text-gray-500">Informações familiares não disponíveis</p>
                </div>
            );
        }

        return (
            <div className="space-y-6">
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h4 className="text-lg font-medium text-gray-900 mb-4">👨‍👩‍👧‍👦 Irmãos</h4>
                    <div className="space-y-4">
                        {irmaos.map((irmao, index) => (
                            <div key={index} className="border-l-4 border-green-500 pl-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Nome:</span>
                                        <span className="font-medium">{irmao.outNomeAluno || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Data de Nascimento:</span>
                                        <span className="font-medium">{irmao.outDataNascimento || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">RA:</span>
                                        <span className="font-medium">
                                            {irmao.outNumRA}{irmao.outDigitoRA ? `-${irmao.outDigitoRA}` : ''}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">UF do RA:</span>
                                        <span className="font-medium">{irmao.outSiglaUFRA || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Gêmeo:</span>
                                        <span className="font-medium">{irmao.outGemeo || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    };

    const renderSpecialNeeds = () => {
        const deficiencia = studentData?.outDeficiencia;
        const necessidades = studentData?.outListaNecessidadesEspeciais;
        const recursos = studentData?.outRecursoAvaliacao;

        if (!deficiencia && (!necessidades || necessidades.length === 0) && !recursos) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">♿</div>
                    <p className="text-gray-500">Informações sobre necessidades especiais não disponíveis</p>
                </div>
            );
        }

        return (
            <div className="space-y-6">
                {deficiencia && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">♿ Deficiência e Mobilidade</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Mobilidade Reduzida:</span>
                                <span className="font-medium">{deficiencia.outMobilidadeReduzida || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Tipo:</span>
                                <span className="font-medium">{deficiencia.outTipoMobilidadeReduzida || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Data Início:</span>
                                <span className="font-medium">{deficiencia.outDataInicioMobilidadeReduzida || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Data Fim:</span>
                                <span className="font-medium">{deficiencia.outDataFimMobilidadeReduzida || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nível de Suporte:</span>
                                <span className="font-medium">{deficiencia.outCodigoNivelSuporte || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Transtorno de Aprendizagem:</span>
                                <span className="font-medium">{deficiencia.outFlTranstornoAprendizagem || 'N/A'}</span>
                            </div>
                        </div>
                        
                        {deficiencia.outProfissionalApoioEscolarResponse && (
                            <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                <h5 className="font-medium text-gray-900 mb-3">👨‍⚕️ Profissional de Apoio Escolar</h5>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Profissional:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outProfissionalApoioEscolar || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Atividade Diária:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outAtividadeDiaria || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Atividade Escolar:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outAtividadeEscolar || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Apoio Higiene:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outApoioHigiene || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Apoio Locomoção:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outApoioLocomocao || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Apoio Alimentação:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outApoioAlimentacao || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Apoio Banheiro:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outApoioBanheiro || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Apoio Medicamento:</span>
                                        <span className="font-medium">{deficiencia.outProfissionalApoioEscolarResponse.outApoioMedicamento || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}
                
                {necessidades && necessidades.length > 0 && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📋 Necessidades Especiais</h4>
                        <div className="space-y-3">
                            {necessidades.map((necessidade, index) => (
                                <div key={index} className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span className="text-gray-700">{necessidade.outNomeNecesEspecial || 'N/A'}</span>
                                    <span className="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                                        Código: {necessidade.outCodNecesEspecial || 'N/A'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
                
                {recursos && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🎯 Recursos de Avaliação</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Guia Intérprete:</span>
                                <span className="font-medium">{recursos.outGuiaInterprete || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Intérprete Libras:</span>
                                <span className="font-medium">{recursos.outInterpreteLibras || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Leitura Labial:</span>
                                <span className="font-medium">{recursos.outLeituraLabial || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Prova Ampliada:</span>
                                <span className="font-medium">{recursos.outProvaAmpliada || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Tamanho da Fonte:</span>
                                <span className="font-medium">{recursos.outTamanhoFonte || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Prova Braile:</span>
                                <span className="font-medium">{recursos.outProvaBraile || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Auxílio Transcrição:</span>
                                <span className="font-medium">{recursos.outAuxilioTranscricao || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Auxílio Leitor:</span>
                                <span className="font-medium">{recursos.outAuxilioLeitor || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Prova Vídeo Libras:</span>
                                <span className="font-medium">{recursos.outProvaVideoLibras || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">CD Áudio Def. Visual:</span>
                                <span className="font-medium">{recursos.outCdAudioDefVisual || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Prova Língua Portuguesa:</span>
                                <span className="font-medium">{recursos.outProvaLinguaPortuguesa || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nenhum:</span>
                                <span className="font-medium">{recursos.outNenhum || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    };



    const renderAdditionalData = () => {
        if (!studentData?.outDadosPessoais) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">📋</div>
                    <p className="text-gray-500">Informações adicionais não disponíveis</p>
                </div>
            );
        }

        const data = studentData.outDadosPessoais;

        return (
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">💰 Programas Sociais</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Bolsa Família:</span>
                                <span className="font-medium">{data.outBolsaFamilia || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Quilombola:</span>
                                <span className="font-medium">{data.outQuilombola || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">💻 Acesso Digital</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Possui Internet:</span>
                                <span className="font-medium">{data.outPossuiInternet || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Possui Dispositivos:</span>
                                <span className="font-medium">{data.outPossuiNotebookSmartphoneTablet || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {studentData?.outIndigena && Object.values(studentData.outIndigena).some(v => v) && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🏹 Informações Indígenas</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Tipo Indígena:</span>
                                <span className="font-medium">{studentData.outIndigena.outDescricaoTipoIndigena || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const renderTabContent = () => {
        switch (activeTab) {
            case 'personal-data':
                return renderPersonalData();
            case 'documents':
                return renderDocuments();
            case 'address':
                return renderAddress();
            case 'family':
                return renderFamily();
            case 'special-needs':
                return renderSpecialNeeds();
            case 'additional-data':
                return renderAdditionalData();
            default:
                return renderPersonalData();
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout
                header={
                    <div className="flex items-center space-x-4">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Carregando Ficha do Aluno...
                        </h2>
                    </div>
                }
            >
                <Head title="Ficha do Aluno" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="flex items-center justify-center py-16">
                            <div className="text-center">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                <p className="text-gray-500">Carregando dados do aluno...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (error) {
        return (
            <AuthenticatedLayout
                header={
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('dashboard')}
                            className="text-gray-500 hover:text-gray-700 transition-colors duration-200"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Erro ao Carregar Ficha do Aluno
                        </h2>
                    </div>
                }
            >
                <Head title="Erro - Ficha do Aluno" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center">
                                <div className="text-red-500 text-6xl mb-4">⚠️</div>
                                <h3 className="text-xl font-medium text-gray-900 mb-2">Erro ao Carregar Dados</h3>
                                <p className="text-gray-500 mb-4">{error}</p>
                                <button
                                    onClick={loadStudentData}
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Tentar Novamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('dashboard')}
                            className="text-gray-500 hover:text-gray-700 transition-colors duration-200"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Ficha do Aluno
                            </h2>
                            <p className="text-sm text-gray-600">
                                {studentData?.outDadosPessoais?.outNomeAluno || `RA: ${studentRa}`}
                            </p>
                        </div>
                    </div>
                    <div className="text-sm text-gray-500">
                        {selectedSchool?.name || 'Escola não selecionada'}
                    </div>
                </div>
            }
        >
            <Head title={`Ficha do Aluno - ${studentData?.outDadosPessoais?.outNomeAluno || studentRa}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex flex-wrap gap-x-8 gap-y-2">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => handleTabChange(tab.id)}
                                        className={`py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 ${
                                            activeTab === tab.id
                                                ? 'border-indigo-500 text-indigo-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        <span className="mr-2">{tab.icon}</span>
                                        {tab.name}
                                    </button>
                                ))}
                            </nav>
                        </div>
                    </div>

                    {/* Tab Content */}
                    <div className="space-y-6">
                        {renderTabContent()}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}