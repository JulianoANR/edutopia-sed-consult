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
    const [exportLoading, setExportLoading] = useState(false);
    const [exportProgress, setExportProgress] = useState({ current: 0, total: 0, message: '' });
    const [showProgressModal, setShowProgressModal] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [failedClasses, setFailedClasses] = useState([]);

    const tabs = [
        { id: 'school-data', name: 'Dados da Escola', icon: '📋' },
        { id: 'classes', name: 'Turmas', icon: '📚' }
    ];

    // Nova função para abrir modal de confirmação
    const handleExportClick = () => {
        if (!school?.outCodEscola) {
            alert('Código da escola não encontrado');
            return;
        }
        setShowConfirmModal(true);
    };

    // Função para confirmar e iniciar exportação
    const confirmExport = () => {
        setShowConfirmModal(false);
        handleExportStudents();
    };

    // Nova função para exportar todos os alunos da escola em etapas
    const handleExportStudents = async () => {
        if (!school?.outCodEscola) {
            alert('Código da escola não encontrado');
            return;
        }

        setExportLoading(true);
        setShowProgressModal(true);
        setFailedClasses([]);
        setExportProgress({ current: 0, total: 0, message: 'Iniciando exportação...' });
        
        try {
            // Etapa 1: Buscar turmas da escola
            setExportProgress({ current: 0, total: 0, message: 'Buscando turmas da escola...' });
            
            const classesResponse = await axios.post('/schools/get-classes', {
                ano_letivo: selectedYear,
                cod_escola: school.outCodEscola
            });

            if (!classesResponse.data.success) {
                throw new Error(classesResponse.data.message || 'Erro ao buscar turmas');
            }

            const schoolClasses = classesResponse.data.data.classes;
            const totalClasses = schoolClasses.length;

            if (totalClasses === 0) {
                alert('Nenhuma turma encontrada para esta escola no ano letivo informado.');
                return;
            }

            setExportProgress({ current: 0, total: totalClasses, message: `Encontradas ${totalClasses} turmas. Processando alunos...` });

            // Etapa 2: Buscar alunos de cada turma
            let allStudentsData = [];
            let allAdditionalData = [];
            const failed = [];

            for (let i = 0; i < schoolClasses.length; i++) {
                const classItem = schoolClasses[i];
                
                setExportProgress({     
                    current: i + 1, 
                    total: totalClasses, 
                    message: `Processando turma ${classItem.nome_turma} (${i + 1}/${totalClasses})...` 
                });

                try {
                    const studentsResponse = await axios.post('/schools/get-class-students', {
                        cod_turma: classItem.cod_turma,
                        nome_turma: classItem.nome_turma,
                        nome_escola: school.outDescNomeEscola || 'Escola',
                        cod_escola: school.outCodEscola,
                        turno: classItem.turno,
                        tipo_ensino: classItem.tipo_ensino,
                        tipo_classe: classItem.tipo_classe,
                        cod_tipo_ensino: classItem.cod_tipo_ensino,
                        cod_tipo_classe: classItem.cod_tipo_classe,
                    });

                    if (studentsResponse.data.success) {
                        const classStudents = studentsResponse.data.data.students;
                        const classAdditionalData = studentsResponse.data.data.additional_data;
                        
                        allStudentsData = allStudentsData.concat(classStudents);
                        allAdditionalData = allAdditionalData.concat(classAdditionalData);
                    }
                } catch (classError) {
                    console.error(`Erro ao processar turma ${classItem.nome_turma}:`, classError);
                    
                    // Adiciona turma que falhou à lista em tempo real
                    const failedClass = {
                        ...classItem,
                        error: classError.response?.data?.message || classError.message || 'Timeout ou erro desconhecido'
                    };
                    failed.push(failedClass);
                    
                    // Atualiza a lista de turmas que falharam imediatamente
                    setFailedClasses(prev => [...prev, failedClass]);
                }
            }

            // Lista de turmas que falharam já foi atualizada em tempo real

            if (allStudentsData.length === 0) {
                alert('Nenhum aluno encontrado nas turmas desta escola.');
                return;
            }

            // Etapa 3: Gerar e baixar arquivo
            setExportProgress({ 
                current: totalClasses, 
                total: totalClasses, 
                message: `Gerando arquivo com ${allStudentsData.length} alunos...` 
            });

            const exportResponse = await axios.post('/schools/export-collected-students', {
                students_data: allStudentsData,
                additional_data: allAdditionalData,
                cod_escola: school.outCodEscola,
                ano_letivo: selectedYear
            }, {
                responseType: 'blob'
            });

            // Criar URL para download
            const url = window.URL.createObjectURL(new Blob([exportResponse.data]));
            const link = document.createElement('a');
            link.href = url;
            
            // Extrair nome do arquivo do cabeçalho ou usar nome padrão
            const contentDisposition = exportResponse.headers['content-disposition'];
            let filename = `alunos_escola_${school.outCodEscola}_${selectedYear}_${new Date().toISOString().slice(0, 10)}.csv`;
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="(.+)"/); 
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }
            
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            let successMessage = `Exportação concluída! ${allStudentsData.length} alunos exportados.`;
            if (failed.length > 0) {
                successMessage = `Exportação concluída! ${allStudentsData.length} alunos exportados. ${failed.length} turma(s) falharam e foram listadas abaixo.`;
            }

            setExportProgress({ 
                current: totalClasses, 
                total: totalClasses, 
                message: successMessage
            });
            
        } catch (error) {
            console.error('Erro ao exportar alunos:', error);
            
            let errorMessage = 'Erro ao exportar alunos da escola';
            
            if (error.response?.data) {
                try {
                    // Tentar ler a resposta de erro se for JSON
                    const reader = new FileReader();
                    reader.onload = function() {
                        try {
                            const errorData = JSON.parse(reader.result);
                            alert(errorData.message || errorMessage);
                        } catch {
                            alert(errorMessage);
                        }
                    };
                    reader.readAsText(error.response.data);
                } catch {
                    alert(errorMessage + ': ' + (error.message || 'Erro desconhecido'));
                }
            } else {
                alert(errorMessage + ': ' + (error.message || 'Erro desconhecido'));
            }
        } finally {
            setExportLoading(false);
        }
    };

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
                    id: classItem.outNumClasse,
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
                    <div>
                        <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <span className="text-2xl mr-2">🏢</span>
                            Unidades da Escola
                        </h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {school.outUnidades.map((unidade, index) => {
                                const enderecoCompleto = unidade.outDescNomeUnidade || '';
                                const partes = enderecoCompleto.split(', ');
                                
                                return (
                                    <div key={unidade.outCodUnidade || index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow duration-200">
                                        <div className="flex items-start mb-3">
                                            <div className="bg-blue-100 rounded-full p-2 mr-3">
                                                <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div className="flex-1">
                                                <h5 className="font-medium text-gray-900 text-sm mb-1">
                                                    Unidade {unidade.outCodUnidade}
                                                </h5>
                                                <p className="text-xs text-gray-500 leading-relaxed">
                                                    {enderecoCompleto}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        {partes.length >= 4 && (
                                            <div className="border-t border-gray-100 pt-3 mt-3">
                                                <div className="grid grid-cols-1 gap-2 text-xs">
                                                    <div className="flex items-center text-gray-600">
                                                        <svg className="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span className="truncate">{partes[0]} {partes[1]} {partes[2]}</span>
                                                    </div>
                                                    <div className="flex items-center text-gray-600">
                                                        <svg className="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        <span>CEP: {partes[3]}</span>
                                                    </div>
                                                    {partes[4] && (
                                                        <div className="flex items-center text-gray-600">
                                                            <svg className="w-3 h-3 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                            </svg>
                                                            <span>{partes[4]}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}
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
                                
                                <div className="mt-4 pt-4 border-t border-gray-200">
                                    <Link
                                        href={route('classes.show', classItem.id)}
                                        className="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver Detalhes
                                    </Link>
                                </div>
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
        <>
            {/* Modal de Confirmação da Exportação */}
            {showConfirmModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                    <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Confirmar Exportação
                                </h3>
                                <button
                                    onClick={() => setShowConfirmModal(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div className="mb-6">
                                <div className="flex items-center mb-4">
                                    <div className="flex-shrink-0">
                                        <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div className="ml-4">
                                        <h4 className="text-lg font-medium text-gray-900">
                                            Exportar Alunos da Escola
                                        </h4>
                                        <p className="text-sm text-gray-600 mt-1">
                                            Você está prestes a exportar todos os alunos da escola <strong>{school?.outDescNomeEscola}</strong> para o ano letivo <strong>{selectedYear}</strong>.
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <p className="text-sm text-blue-700">
                                                Este processo pode levar alguns minutos dependendo do número de turmas e alunos. 
                                                Você poderá acompanhar o progresso na próxima tela.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="flex justify-end space-x-3">
                                <button
                                    onClick={() => setShowConfirmModal(false)}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Cancelar
                                </button>
                                <button
                                    onClick={confirmExport}
                                    className="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    Confirmar Exportação
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal de Progresso da Exportação */}
            {showProgressModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                    <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Exportando Alunos
                                </h3>
                                {!exportLoading && (
                                    <button
                                        onClick={() => setShowProgressModal(false)}
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                )}
                            </div>
                            
                            <div className="mb-4">
                                <div className="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progresso</span>
                                    <span>{exportProgress.current}/{exportProgress.total}</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        className="bg-green-600 h-2 rounded-full transition-all duration-300"
                                        style={{ 
                                            width: exportProgress.total > 0 
                                                ? `${(exportProgress.current / exportProgress.total) * 100}%` 
                                                : '0%' 
                                        }}
                                    ></div>
                                </div>
                            </div>
                            
                            <div className="text-sm text-gray-600 mb-4">
                                {exportProgress.message}
                            </div>
                            
                            {/* Lista de turmas que falharam */}
                            {failedClasses.length > 0 && (
                                <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <h4 className="text-sm font-medium text-yellow-800 mb-2">
                                        ⚠️ Turmas com erro ({failedClasses.length})
                                    </h4>
                                    <div className="max-h-32 overflow-y-auto space-y-2">
                                        {failedClasses.map((failedClass, index) => (
                                            <div key={index} className="text-xs bg-white p-2 rounded border">
                                                <div className="flex justify-between items-start">
                                                    <div className="flex-1">
                                                        <span className="font-medium text-gray-900">
                                                             {failedClass.outDescTurma || failedClass.nome_turma}
                                                         </span>
                                                        <div className="text-gray-500 mt-1">
                                                            {failedClass.error}
                                                        </div>
                                                    </div>
                                                    <a
                                                         href={route('classes.show', failedClass.outCodTurma || failedClass.cod_turma)}
                                                         className="ml-2 text-blue-600 hover:text-blue-800 text-xs underline"
                                                         target="_blank"
                                                     >
                                                         Ver turma
                                                     </a>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                            
                            {exportLoading && (
                                <div className="flex flex-col items-center justify-center space-y-3">
                                    <div className="flex items-center">
                                        <svg className="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span className="ml-2 text-sm text-gray-600">Processando...</span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
            
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
                            
                            {/* Botão de Exportar Alunos */}
                            <button
                                onClick={handleExportClick}
                                disabled={exportLoading || !school?.outCodEscola}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                {exportLoading ? (
                                    <>
                                        <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Exportando...
                                    </>
                                ) : (
                                    <>
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Exportar Alunos
                                    </>
                                )}
                            </button>
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
        </>
    );
}