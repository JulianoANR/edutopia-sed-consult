import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function ClassShow({ classCode, selectedSchool }) {
    const [activeTab, setActiveTab] = useState('class-data');
    const [classData, setClassData] = useState(null);
    const [students, setStudents] = useState([]);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [exportLoading, setExportLoading] = useState(false);
    // Ano letivo é fixo para a turma específica

    const tabs = [
        { id: 'class-data', name: 'Dados da Turma', icon: '📚' },
        { id: 'students', name: 'Alunos', icon: '👥' }
    ];

    const loadTabData = async (tabId) => {
        if (tabId === 'class-data') {
            await loadClassData();
        } else if (tabId === 'students') {
            await loadStudents();
        }
    };

    const loadClassData = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.post('/sed-api/consultar-turma', {
                inNumClasse: classCode
            });
            
            // Os dados estão dentro de response.data.data
            const data = response.data.data;
            
            if (!response.data.success) {
                throw new Error(response.data.message || 'Erro ao consultar turma');
            }
            
            if (data.outErro) {
                throw new Error(data.outErro);
            }
            
            // Mapear os dados da API para o formato esperado pelo componente
            const mappedClassData = {
                code: data.outNumClasse,
                name: `${data.outCodSerieAno}º${data.outTurma?.toUpperCase()}`,
                grade: `${data.outCodSerieAno}º Ano`,
                shift: data.outDescricaoTurno,
                room: data.outNumSala,
                schedule: `${data.outHorarioInicio} - ${data.outHorarioFim}`,
                type: data.outDescTipoEnsino,
                teaching_type: data.outDescTipoEnsino,
                semester: 'Anual',
                capacity: parseInt(data.outCapacidadeFisicaMax) || 0,
                teacher: 'N/A', // Não disponível na API
                year: data.outAnoLetivo,
                school: data.outDescNomeAbrevEscola,
                startDate: data.outDataInicioAula,
                endDate: data.outDataFimAula,
                stats: {
                    active: parseInt(data.outQtdAtual) || 0,
                    total: parseInt(data.outQtdDigitados) || 0,
                    dropped: parseInt(data.outQtdEvadidos) || 0,
                    transferred: parseInt(data.outQtdTransferidos) || 0,
                    absent: parseInt(data.outQtdNCom) || 0,
                    rearranged: parseInt(data.outQtdRemanejados) || 0,
                    ceased: parseInt(data.outQtdCessados) || 0,
                    reclassified: parseInt(data.outQtdReclassificados) || 0,
                    others: parseInt(data.outQtdOutros) || 0
                }
            };
            
            // Mapear os dados dos alunos
            const mappedStudents = data.outAlunos?.map(aluno => ({
                ra: `${aluno.outNumRA}-${aluno.outDigitoRA}`,
                name: aluno.outNomeAluno,
                number: aluno.outNumAluno,
                birthDate: aluno.outDataNascimento,
                status: aluno.outDescSitMatricula,
                enrollmentStart: aluno.outDataInicioMatricula,
                enrollmentEnd: aluno.outDataFimMatricula,
                uf: aluno.outSiglaUFRA
            })) || [];
            
            setClassData(mappedClassData);
            setStudents(mappedStudents);
        } catch (error) {
            console.error('Erro ao carregar dados da turma:', error);
            setError({
                type: 'network_error',
                title: 'Erro de Conexão',
                message: 'Não foi possível carregar os dados da turma.',
                details: error.response?.data?.message || error.message || 'Verifique sua conexão com a internet e tente novamente.'
            });
        }
        setLoading(false);
    };

    const loadStudents = async () => {
        // Os dados dos alunos já são carregados junto com os dados da turma
        // Esta função existe para manter a consistência da interface
        if (!classData) {
            await loadClassData();
        }
    };

    const handleExportExcel = async () => {
        if (!students || students.length === 0) {
            alert('Não há alunos para exportar.');
            return;
        }

        setExportLoading(true);
        try {
            // Criar FormData para enviar os dados
            const formData = new FormData();
            formData.append('classCode', classCode);
            
            // Adicionar cada aluno como array
            students.forEach((student, index) => {
                formData.append(`students[${index}][ra]`, student.ra);
                formData.append(`students[${index}][name]`, student.name);
            });
            
            // Criar um link para download do arquivo CSV
            const response = await axios.post('/classes/export-excel', formData, {
                responseType: 'blob',
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });

            // Criar URL do blob e fazer download
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `alunos_turma_${classCode}_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Erro ao exportar CSV:', error);
            alert('Erro ao exportar arquivo CSV. Tente novamente.');
        }
        setExportLoading(false);
    };

    useEffect(() => {
        loadTabData(activeTab);
    }, [activeTab]);

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
        setError(null);
        loadTabData(tabId);
    };

    const renderClassData = () => {
        if (loading) {
            return (
                <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                    <p className="mt-2 text-sm text-gray-500">Carregando dados da turma...</p>
                </div>
            );
        }

        if (error) {
            return (
                <div className="max-w-md mx-auto">
                    <div className="bg-red-50 border border-red-200 rounded-lg p-6 shadow-sm">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <div className="text-3xl">⚠️</div>
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
                                        loadClassData();
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
            );
        }

        if (!classData) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">📚</div>
                    <p className="text-gray-500">Nenhum dado encontrado para esta turma</p>
                </div>
            );
        }

        return (
            <div className="space-y-6">
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h4 className="text-lg font-medium text-gray-900 mb-4">📚 Informações da Turma</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div className="space-y-4">
                            <h5 className="font-medium text-gray-900 border-b pb-2">Informações Básicas</h5>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Código:</span>
                                <span className="font-medium">{classData.code || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Nome:</span>
                                <span className="font-medium">{classData.name || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Série:</span>
                                <span className="font-medium">{classData.grade || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Turno:</span>
                                <span className="font-medium">{classData.shift || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Sala:</span>
                                <span className="font-medium">{classData.room || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Horário:</span>
                                <span className="font-medium">{classData.schedule || 'N/A'}</span>
                            </div>
                        </div>
                        <div className="space-y-4">
                            <h5 className="font-medium text-gray-900 border-b pb-2">Detalhes Acadêmicos</h5>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Ano Letivo:</span>
                                <span className="font-medium">{classData.year || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Escola:</span>
                                <span className="font-medium">{classData.school || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Tipo de Ensino:</span>
                                <span className="font-medium">{classData.teaching_type || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Capacidade:</span>
                                <span className="font-medium">{classData.capacity || 0} alunos</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Início das Aulas:</span>
                                <span className="font-medium">{classData.startDate || 'N/A'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Fim das Aulas:</span>
                                <span className="font-medium">{classData.endDate || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {classData.stats && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 className="text-lg font-medium text-gray-900 mb-4">📊 Estatísticas de Matrícula</h4>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div className="text-center p-3 bg-green-50 rounded-lg">
                                <div className="text-2xl font-bold text-green-600">{classData.stats.active}</div>
                                <div className="text-sm text-gray-600">Ativos</div>
                            </div>
                            <div className="text-center p-3 bg-blue-50 rounded-lg">
                                <div className="text-2xl font-bold text-blue-600">{classData.stats.total}</div>
                                <div className="text-sm text-gray-600">Total Matriculados</div>
                            </div>
                            <div className="text-center p-3 bg-red-50 rounded-lg">
                                <div className="text-2xl font-bold text-red-600">{classData.stats.dropped}</div>
                                <div className="text-sm text-gray-600">Evadidos</div>
                            </div>
                            <div className="text-center p-3 bg-orange-50 rounded-lg">
                                <div className="text-2xl font-bold text-orange-600">{classData.stats.absent}</div>
                                <div className="text-sm text-gray-600">Não Compareceram</div>
                            </div>
                            <div className="text-center p-3 bg-yellow-50 rounded-lg">
                                <div className="text-2xl font-bold text-yellow-600">{classData.stats.transferred}</div>
                                <div className="text-sm text-gray-600">Transferidos</div>
                            </div>
                            <div className="text-center p-3 bg-purple-50 rounded-lg">
                                <div className="text-2xl font-bold text-purple-600">{classData.stats.rearranged}</div>
                                <div className="text-sm text-gray-600">Remanejados</div>
                            </div>
                            <div className="text-center p-3 bg-pink-50 rounded-lg">
                                <div className="text-2xl font-bold text-pink-600">{classData.stats.ceased}</div>
                                <div className="text-sm text-gray-600">Cessados</div>
                            </div>
                            <div className="text-center p-3 bg-indigo-50 rounded-lg">
                                <div className="text-2xl font-bold text-indigo-600">{classData.stats.reclassified}</div>
                                <div className="text-sm text-gray-600">Reclassificados</div>
                            </div>
                            <div className="text-center p-3 bg-gray-50 rounded-lg">
                                <div className="text-2xl font-bold text-gray-600">{classData.stats.others}</div>
                                <div className="text-sm text-gray-600">Outros</div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    // Filtrar alunos baseado no termo de pesquisa
    const filteredStudents = students.filter(student => {
        if (!searchTerm) return true;
        const searchLower = searchTerm.toLowerCase();
        return (
            student.name?.toLowerCase().includes(searchLower) ||
            student.ra?.toLowerCase().includes(searchLower) ||
            student.number?.toString().includes(searchTerm)
        );
    });

    const renderStudents = () => {
        if (loading) {
            return (
                <div className="flex items-center justify-center py-16">
                    <div className="text-center">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                        <p className="text-gray-500">Carregando alunos...</p>
                    </div>
                </div>
            );
        }

        if (!students || students.length === 0) {
            return (
                <div className="flex items-center justify-center py-16">
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 max-w-md text-center">
                        <div className="text-6xl mb-4">👥</div>
                        <h3 className="text-xl font-medium text-gray-900 mb-2">Nenhum Aluno Encontrado</h3>
                        <p className="text-gray-500">
                            Não há alunos matriculados nesta turma ou os dados ainda não foram carregados.
                        </p>
                    </div>
                </div>
            );
        }

        return (
            <div className="space-y-6">
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-medium text-gray-900">Lista de Alunos</h3>
                        <div className="flex items-center space-x-3">
                            <button
                                onClick={handleExportExcel}
                                disabled={!students || students.length === 0 || loading || exportLoading}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            >
                                {exportLoading ? (
                                    <svg className="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                ) : (
                                    <svg className="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                )}
                                {exportLoading ? 'Exportando...' : 'Exportar CSV'}
                            </button>
                            <span className="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                {filteredStudents.length} de {students.length} aluno{students.length !== 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    
                    {/* Barra de Pesquisa */}
                    <div className="mb-6">
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                placeholder="Pesquisar por nome, RA ou número do aluno..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                            {searchTerm && (
                                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button
                                        onClick={() => setSearchTerm('')}
                                        className="text-gray-400 hover:text-gray-600 focus:outline-none"
                                    >
                                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                    
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nº
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        RA
                                    </th>
                                    {/* <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data Nascimento
                                    </th> */}
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    {/* <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Matrícula
                                    </th> */}
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {filteredStudents.map((student, index) => (
                                    <tr key={student.ra} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {student.number || index + 1}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{student.name}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {student.ra}
                                        </td>
                                        {/* <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {student.birthDate || 'N/A'}
                                        </td> */}
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                {student.status || 'Ativo'}
                                            </span>
                                        </td>
                                        {/* <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {student.enrollmentStart ? `${student.enrollmentStart} - ${student.enrollmentEnd || 'Atual'}` : 'N/A'}
                                        </td> */}
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link
                                                href={route('students.show', student.ra)}
                                                className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                                title="Ficha Aluno"
                                            >
                                                <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Ver Ficha
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    
                    {/* Mensagem quando nenhum aluno é encontrado na pesquisa */}
                    {searchTerm && filteredStudents.length === 0 && (
                        <div className="text-center py-8">
                            <div className="text-4xl mb-2">🔍</div>
                            <h4 className="text-lg font-medium text-gray-900 mb-1">Nenhum aluno encontrado</h4>
                            <p className="text-gray-500 text-sm">
                                Não encontramos alunos que correspondam à sua pesquisa por "{searchTerm}"
                            </p>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderTabContent = () => {
        switch (activeTab) {
            case 'class-data':
                return renderClassData();
            case 'students':
                return renderStudents();
            default:
                return renderClassData();
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={selectedSchool?.outCodEscola ? route('schools.show', selectedSchool.outCodEscola) : route('schools.index')}
                            className="text-gray-500 hover:text-gray-700 transition-colors duration-200"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Detalhes da Turma {classCode}
                            </h2>
                            <p className="text-sm text-gray-600">
                                {classData?.school || 'Carregando informações da escola...'}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        {classData && (
                            <span className="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                Ano Letivo: {classData.year}
                            </span>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Turma ${classCode}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => handleTabChange(tab.id)}
                                        className={`${
                                            activeTab === tab.id
                                                ? 'border-indigo-500 text-indigo-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition-colors duration-200`}
                                    >
                                        <span>{tab.icon}</span>
                                        <span>{tab.name}</span>
                                    </button>
                                ))}
                            </nav>
                        </div>
                        
                        <div className="p-6">
                            {renderTabContent()}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}