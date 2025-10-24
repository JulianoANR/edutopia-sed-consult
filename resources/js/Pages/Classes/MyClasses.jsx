import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function MyClasses({ schools }) {
    const [activeSchool, setActiveSchool] = useState(0);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear().toString());
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);


    // Reset activeSchool quando schools mudar
    useEffect(() => {
        if (schools.length > 0 && activeSchool >= schools.length) {
            setActiveSchool(0);
        }
    }, [schools, activeSchool]);

    // Função para converter objeto com índices numéricos em array
    const convertToArray = (obj) => {
        if (Array.isArray(obj)) return obj;
        if (obj && typeof obj === 'object') {
            return Object.values(obj);
        }
        return [];
    };

    // Função para filtrar dados
    const filteredData = (data, searchFields) => {
        if (!searchTerm.trim()) return data;
        
        return data.filter(item => 
            searchFields.some(field => 
                item[field]?.toString().toLowerCase().includes(searchTerm.toLowerCase())
            )
        );
    };

    // Função para renderizar as turmas de uma escola
    const renderSchoolClasses = (school) => {
        const classesArray = convertToArray(school.outClasses);
        
        if (classesArray.length === 0) {
            return (
                <div className="text-center py-8">
                    <div className="text-gray-400 text-4xl mb-2">📚</div>
                    <p className="text-gray-500">Nenhuma turma encontrada para esta escola no ano {selectedYear}</p>
                </div>
            );
        }

        const formattedClasses = classesArray.map((classItem, index) => ({
            id: classItem.outNumClasse,
            name: classItem.nome_turma ?? ' - ',
            grade: `${classItem.outDescTipoEnsino}`,
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

        const filteredClasses = filteredData(formattedClasses, ['name', 'grade', 'shift', 'type', 'teaching_type']);

        return (
            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <h3 className="text-lg font-medium text-gray-900">
                        Turmas
                    </h3>
                    <input
                        type="text"
                        placeholder="Buscar turma..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
                
                {filteredClasses.length === 0 ? (
                    <div className="text-center py-8">
                        <div className="text-gray-400 text-4xl mb-2">🔍</div>
                        <p className="text-gray-500">Nenhuma turma encontrada com o termo de busca</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredClasses.map((classItem, index) => (
                            <div key={classItem.id || index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 min-h-[420px] flex flex-col">
                                <div className="flex items-start mb-4">
                                    <div className="text-2xl mr-3 flex-shrink-0">📚</div>
                                    <div className="flex-1 min-h-0">
                                        <h4 className="text-lg font-medium text-gray-900 line-clamp-2 leading-tight">{classItem.name || 'N/A'}</h4>
                                        <p className="text-sm text-gray-500 line-clamp-2 mt-1">{classItem.grade || 'N/A'}</p>
                                    </div>
                                </div>
                                <div className="space-y-2 text-sm flex-1 overflow-hidden">
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
                                
                                <div className="mt-auto pt-4">
                                    {classItem.stats && (
                                        <div className="mb-3">
                                            <h5 className="text-xs font-medium text-gray-700 mb-2">Estatísticas</h5>
                                            <div className="grid grid-cols-2 gap-1 text-xs">
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
                                    
                                    <div className="border-t border-gray-200 pt-3">
                                        <Link
                                            href={route('classes.attendance.show', { classCode: classItem.id })}
                                            className="inline-flex items-center justify-center w-full px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Acessar Turma
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    };

    // Função para renderizar informações da escola
    const renderSchoolInfo = (school) => {
        return (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <span className="text-2xl mr-2">🏫</span>
                    {school.outDescNomeAbrevEscola || 'Escola'}
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div className="space-y-2">
                        <div className="flex justify-between">
                            <span className="text-gray-500">Código da Escola:</span>
                            <span className="font-medium">{school.outCodEscola || 'N/A'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-500">Total de Turmas:</span>
                            <span className="font-medium">{convertToArray(school.outClasses).length}</span>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <div className="flex justify-between">
                            <span className="text-gray-500">Total de Alunos:</span>
                            <span className="font-medium">
                                {convertToArray(school.outClasses).reduce((total, classItem) => total + (classItem.outQtdAtual || 0), 0)}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // Função para renderizar navegação entre escolas
    const renderSchoolNavigation = () => {
        if (schools.length <= 1) return null;

        return (
            <div className="mb-6">
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8 overflow-x-auto">
                        {schools.map((school, index) => (
                            <button
                                key={index}
                                onClick={() => setActiveSchool(index)}
                                className={`py-2 px-1 border-b-2 font-medium text-sm whitespace-nowrap ${
                                    safeActiveSchool === index
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                <span className="mr-2">🏫</span>
                                {school.outDescNomeAbrevEscola || `Escola ${index + 1}`}
                            </button>
                        ))}
                    </nav>
                </div>
            </div>
        );
    };

    // Verificação de segurança para activeSchool
    const safeActiveSchool = activeSchool >= schools.length ? 0 : activeSchool;
    const currentSchool = schools[safeActiveSchool];


    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center space-x-4">
                        {/* <Link
                            href="/dashboard"
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar ao Dashboard
                        </Link> */}
                        <div>
                            <h2 className="text-xl font-semibold leading-tight text-gray-800">
                                Minhas Turmas
                            </h2>
                            <p className="text-sm text-gray-600">
                                {schools.length > 1 
                                    ? `${schools.length} escolas` 
                                    : currentSchool?.outDescNomeAbrevEscola || 'Escola'
                                }
                            </p>
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
                                {/* {Array.from({ length: 10 }, (_, i) => {
                                    const year = new Date().getFullYear() - i;
                                    return (
                                        <option key={year} value={year.toString()}>
                                            {year}
                                        </option>
                                    );
                                })} */}
                                <option selected disabled value={new Date().getFullYear().toString()}>{new Date().getFullYear()}</option>
                            </select>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Minhas Turmas - Sistema Educacional" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Navegação entre escolas */}
                    {renderSchoolNavigation()}

                    {/* Conteúdo principal */}
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {currentSchool ? (
                                <div className="space-y-6">
                                    
                                    {/* Informações da escola */}
                                    {renderSchoolInfo(currentSchool)}
                                    
                                    {/* Lista de turmas */}
                                    {renderSchoolClasses(currentSchool)}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <div className="text-gray-400 text-4xl mb-2">📚</div>
                                    <p className="text-gray-500">Nenhuma escola encontrada</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
