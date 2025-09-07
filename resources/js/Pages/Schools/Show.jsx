import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';
export default function SchoolShow({ school, selectedSchool }) {
    const [activeTab, setActiveTab] = useState('school-data');

    const [classes, setClasses] = useState([]);
    const [error, setError] = useState(null);

    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString());
    const [schoolData, setSchoolData] = useState(null);

    const tabs = [
        { id: 'school-data', name: 'Dados da Escola', icon: '📋' },
        { id: 'classes', name: 'Turmas', icon: '📚' }
    ];

    const loadTabData = async (tabId) => {
        if (tabId === 'classes') {
            await loadClasses();
        }
    };

    const loadClasses = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get('/sed-api/classes', {
                params: {
                    ano_letivo: selectedYear,
                    cod_escola: school.outCodEscola,
                }
            });
            
            if (response.data.success && response.data.data.outClasses) {
                const formattedClasses = response.data.data.outClasses.map((classItem, index) => ({
                    id: `${classItem.outNumClasse}-${index}`,
                    name: `${classItem.outCodSerieAno}º ${classItem.outTurma}`,
                    grade: `${classItem.outCodSerieAno}º Ano`,
                    shift: classItem.outDescricaoTurno,
                    students_count: classItem.outQtdAtual,

                    room: classItem.outNumSala,
                    schedule: `${classItem.outHorarioInicio} - ${classItem.outHorarioFim}`,
                    type: classItem.outDescTipoClasse,
                    teaching_type: classItem.outDescTipoEnsino,
                    semester: classItem.outSemestre === '0' ? 'Anual' : 
                             classItem.outSemestre === '1' ? '1º Semestre' : '2º Semestre',
                    capacity: classItem.outCapacidadeFisicaMax,
                    stats: {
                        active: classItem.outQtdAtual,
                        total: classItem.outQtdDigitados,
                        dropped: classItem.outQtdEvadidos,
                        transferred: classItem.outQtdTransferidos,
                        absent: classItem.outQtdNCom
                    }
                }));
                setClasses(formattedClasses);
            } else {
                setClasses([]);
                if (!response.data.success) {
                    setError({
                        type: 'api_error',
                        title: 'Erro na API',
                        message: response.data.message || 'Não foi possível carregar as turmas.',
                        details: 'Verifique se os parâmetros estão corretos e tente novamente.'
                    });
                }
            }
        } catch (error) {
            console.error('Erro ao carregar turmas:', error);
            setClasses([]);
            
            let errorInfo = {
                type: 'network_error',
                title: 'Erro de Conexão',
                message: 'Não foi possível conectar com o servidor.',
                details: 'Verifique sua conexão com a internet e tente novamente.'
            };

            if (error.response) {
                // Erro de resposta do servidor
                errorInfo = {
                    type: 'server_error',
                    title: `Erro ${error.response.status}`,
                    message: error.response.data?.message || 'Erro interno do servidor.',
                    details: error.response.status === 500 
                        ? 'Ocorreu um erro interno. Tente novamente em alguns minutos.'
                        : 'Verifique os dados e tente novamente.'
                };
            } else if (error.request) {
                // Erro de rede
                errorInfo = {
                    type: 'network_error',
                    title: 'Erro de Rede',
                    message: 'Não foi possível conectar com o servidor.',
                    details: 'Verifique sua conexão com a internet e tente novamente.'
                };
            }
            
            setError(errorInfo);
        }
        setLoading(false);
    };

    // Recarrega as classes quando o ano letivo muda
    useEffect(() => {
        if (activeTab === 'classes') {
            loadClasses();
        }
    }, [selectedYear]);

    useEffect(() => {
        loadTabData(activeTab);
    }, [activeTab]);

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
        setSearchTerm('');
        setError(null);
    };

    const filteredData = (data, searchFields) => {
        if (!searchTerm.trim()) return data;
        
        return data.filter(item => 
            searchFields.some(field => 
                item[field]?.toString().toLowerCase().includes(searchTerm.toLowerCase())
            )
        );
    };

    const renderSchoolData = () => {
        return (
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🏫 Informações da Escola</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Código da Escola:</span>
                                <span className="font-medium">{school?.outCodEscola || 'N/A'}</span>
                            </div>
                            <div className="flex flex-col space-y-1">
                                <span className="text-gray-500">Nome da Escola:</span>
                                <span className="font-medium text-wrap">{school?.outDescNomeEscola || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Total de Unidades:</span>
                                <span className="font-medium">{school?.outUnidades?.length || 0}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📊 Estatísticas</h4>
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Total de Alunos:</span>
                                <span className="font-medium">{school?.students_count || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Total de Turmas:</span>
                                <span className="font-medium">{school?.classes_count || 0}</span>
                            </div>

                        </div>
                    </div>
                </div>
                
                {school?.outUnidades && school.outUnidades.length > 0 && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">🏢 Unidades da Escola</h4>
                        <div className="space-y-4">
                            {school.outUnidades.map((unidade, index) => {
                                // Extrair informações do endereço da unidade
                                const enderecoCompleto = unidade.outDescNomeUnidade || '';
                                const partes = enderecoCompleto.split(', ');
                                
                                return (
                                    <div key={unidade.outCodUnidade || index} className="border border-gray-100 rounded-lg p-4 bg-gray-50">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Código da Unidade:</span>
                                                <span className="font-medium">{unidade.outCodUnidade}</span>
                                            </div>
                                            <div className="flex flex-col space-y-1">
                                                <span className="text-gray-500">Endereço Completo:</span>
                                                <span className="font-medium text-wrap">{enderecoCompleto}</span>
                                            </div>
                                            {partes.length >= 4 && (
                                                <>
                                                    <div className="flex justify-between">
                                                        <span className="text-gray-500">Logradouro:</span>
                                                        <span className="font-medium">{partes[0]} {partes[1]} {partes[2]}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-gray-500">CEP:</span>
                                                        <span className="font-medium">{partes[3]}</span>
                                                    </div>
                                                    {partes[4] && (
                                                        <div className="flex justify-between">
                                                            <span className="text-gray-500">Bairro:</span>
                                                            <span className="font-medium">{partes[4]}</span>
                                                        </div>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        );
    };

    const renderClasses = () => {
        const filteredClasses = filteredData(classes, ['name', 'grade', 'shift', 'type', 'teaching_type']);
        
        return (
            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <h3 className="text-lg font-medium text-gray-900">Turmas - Ano Letivo {selectedYear}</h3>
                    <input
                        type="text"
                        placeholder="Buscar turma..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
                
                {loading ? (
                    <div className="text-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                        <p className="mt-2 text-sm text-gray-500">Carregando turmas...</p>
                    </div>
                ) : error ? (
                    <div className="max-w-md mx-auto">
                        <div className="bg-red-50 border border-red-200 rounded-lg p-6 shadow-sm">
                            <div className="flex items-start">
                                <div className="flex-shrink-0">
                                    {error.type === 'network_error' && (
                                        <div className="text-3xl">🌐</div>
                                    )}
                                    {error.type === 'server_error' && (
                                        <div className="text-3xl">⚠️</div>
                                    )}
                                    {error.type === 'api_error' && (
                                        <div className="text-3xl">🔧</div>
                                    )}
                                </div>
                                <div className="ml-4 flex-1">
                                    <h3 className="text-lg font-medium text-red-800 mb-2">
                                        {error.title}
                                    </h3>
                                    <p className="text-red-700 mb-3">
                                        {error.message}
                                    </p>
                                    <p className="text-sm text-red-600 mb-4">
                                        {error.details}
                                    </p>
                                    <button
                                        onClick={() => {
                                            setError(null);
                                            loadClasses();
                                        }}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Tentar Novamente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : filteredClasses.length === 0 ? (
                    <div className="text-center py-8">
                        <div className="text-gray-400 text-4xl mb-2">📚</div>
                        <p className="text-gray-500">Nenhuma turma encontrada para o ano {selectedYear}</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredClasses.map((classItem, index) => (
                            <div key={classItem.id || index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center mb-4">
                                    <div className="text-2xl mr-3">📚</div>
                                    <div>
                                        <h4 className="text-lg font-medium text-gray-900">{classItem.name || 'N/A'}</h4>
                                        <p className="text-sm text-gray-500">{classItem.grade || 'N/A'}</p>
                                    </div>
                                </div>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Turno:</span>
                                        <span className="font-medium">{classItem.shift || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Alunos Ativos:</span>
                                        <span className="font-medium">{classItem.students_count || 0}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Sala:</span>
                                        <span className="font-medium">{classItem.room || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Horário:</span>
                                        <span className="font-medium">{classItem.schedule || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Tipo:</span>
                                        <span className="font-medium">{classItem.type || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Período:</span>
                                        <span className="font-medium">{classItem.semester || 'N/A'}</span>
                                    </div>
                                </div>
                                
                                {classItem.stats && (
                                    <div className="mt-4 pt-4 border-t border-gray-200">
                                        <h5 className="text-xs font-medium text-gray-700 mb-2">Estatísticas de Matrícula</h5>
                                        <div className="grid grid-cols-2 gap-2 text-xs">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Total:</span>
                                                <span className="font-medium">{classItem.stats.total || 0}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Evadidos:</span>
                                                <span className="font-medium text-red-600">{classItem.stats.dropped || 0}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Transferidos:</span>
                                                <span className="font-medium text-yellow-600">{classItem.stats.transferred || 0}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Não Compareceu:</span>
                                                <span className="font-medium text-orange-600">{classItem.stats.absent || 0}</span>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const renderContent = () => {
        switch (activeTab) {
            case 'school-data':
                return renderSchoolData();
            case 'classes':
                return renderClasses();
            default:
                return null;
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/schools"
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar às Escolas
                        </Link>
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                {school?.outDescNomeEscola || 'Escola'}
                            </h2>
                            <p className="text-sm text-gray-600">Código: {school.outCodEscola || 'N/A'}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        <div className="flex items-center space-x-2">
                            <label htmlFor="year-select" className="text-sm font-medium text-gray-700">
                                Ano Letivo:
                            </label>
                            <select
                                id="year-select"
                                value={selectedYear}
                                onChange={(e) => setSelectedYear(e.target.value)}
                                className="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                {Array.from({ length: 10 }, (_, i) => {
                                    const year = new Date().getFullYear() - i;
                                    return (
                                        <option key={year} value={year.toString()}>
                                            {year}
                                        </option>
                                    );
                                })}
                            </select>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`${school?.outCodEscola || 'Escola'} - Sistema Educacional`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Navegação por Abas */}
                    <div className="mb-8">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => handleTabChange(tab.id)}
                                        className={`py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap ${
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

                    {/* Conteúdo da Aba */}
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {renderContent()}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}