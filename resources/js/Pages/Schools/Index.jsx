import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { getDefaultFields } from '@/config/export-fields.js';
import ExportFieldsModal from '@/Components/ExportFieldsModal';

export default function SchoolsIndex({ schools, selectedSchool, connectionStatus, redeEnsinoId }) {
    const [loadingSchool, setLoadingSchool] = useState(null);
    const [testingConnection, setTestingConnection] = useState(false);
    const [connectionResult, setConnectionResult] = useState(connectionStatus);
    const [searchTerm, setSearchTerm] = useState('');
    const [filteredSchools, setFilteredSchools] = useState(schools);
    const [selectedRedeEnsino, setSelectedRedeEnsino] = useState(redeEnsinoId || 2);
    const [loadingRedeEnsino, setLoadingRedeEnsino] = useState(false);
    const [selectedForExport, setSelectedForExport] = useState(new Set());
    const [exportModal, setExportModal] = useState(null);
    const [exportSubmitting, setExportSubmitting] = useState(false);
    const [showFieldsModal, setShowFieldsModal] = useState(false);
    const [selectedFields, setSelectedFields] = useState(() => getDefaultFields().map(f => f.key));

    const redesEnsino = [
        { value: 1, label: 'Estadual', icon: '🏛️' },
        { value: 2, label: 'Municipal', icon: '🏢' },
        { value: 3, label: 'Privada', icon: '🏫' },
        { value: 4, label: 'Federal', icon: '🏛️' },
        { value: 5, label: 'Estadual Outros (Centro Paula Souza)', icon: '🎓' }
    ];
    
    // Atualiza filteredSchools quando schools muda
    useEffect(() => {
        setFilteredSchools(schools);
    }, [schools]);

    const handleSelectSchool = async (school) => {
        setLoadingSchool(school.outCodEscola);
        try {
            await axios.post('/schools/select', {
                school_id: school.outCodEscola,
                school_name: school.outDescNomeEscola
            });
            router.visit(`/schools/view/${school.outCodEscola}/${selectedRedeEnsino}`);
        } catch (error) {
            console.error('Erro ao selecionar escola:', error);
            if (error.response?.status !== 419) {
                alert('Erro ao selecionar escola: ' + (error.response?.data?.message || error.message));
            }
        } finally {
            setLoadingSchool(null);
        }
    };

    const testConnection = async () => {
        setTestingConnection(true);
        try {
            const response = await axios.get('/sed-api/test-connection');
            setConnectionResult(response.data);
        } catch (error) {
            setConnectionResult({
                success: false,
                message: 'Erro ao testar conexão',
                error: error.response?.data?.error || error.message
            });
        }
        setTestingConnection(false);
    };

    const handleSearch = (term) => {
        setSearchTerm(term);
        if (!term.trim()) {
            setFilteredSchools(schools);
        } else {
            const filtered = schools.filter(school => 
                school.outDescNomeEscola?.toLowerCase().includes(term.toLowerCase()) ||
                school.outCodEscola?.toString().toLowerCase().includes(term.toLowerCase())
            );
            setFilteredSchools(filtered);
        }
    };

    const toggleExportSelection = (e, school) => {
        e.stopPropagation();
        setSelectedForExport(prev => {
            const next = new Set(prev);
            if (next.has(school.outCodEscola)) {
                next.delete(school.outCodEscola);
            } else {
                next.add(school.outCodEscola);
            }
            return next;
        });
    };

    const goToStudentExports = () => {
        setExportModal(null);
        router.visit(route('student-exports.index'));
    };

    const buildExportPayload = () => {
        const schools = filteredSchools
            .filter(s => selectedForExport.has(s.outCodEscola))
            .map(s => ({
                code: String(s.outCodEscola),
                name: s.outDescNomeEscola || String(s.outCodEscola),
            }));
        return {
            ano_letivo: new Date().getFullYear().toString(),
            schools,
            selected_fields: selectedFields,
        };
    };

    const handleStartAsyncExport = async () => {
        if (selectedForExport.size === 0) {
            return;
        }

        setShowFieldsModal(true);
    };

    const confirmStartAsyncExport = async () => {
        if (selectedForExport.size === 0) return;
        if (!Array.isArray(selectedFields) || selectedFields.length === 0) return;

        const payload = buildExportPayload();
        setExportSubmitting(true);
        try {
            await axios.post(route('student-exports.start'), payload);

            setExportModal({
                kind: 'started',
                title: 'Exportação iniciada',
                body: 'O processamento pode levar vários minutos. Clique em OK para abrir a tela de exportações e acompanhar o andamento.',
            });
            setShowFieldsModal(false);
        } catch (error) {
            if (error.response?.status === 409) {
                setExportModal({
                    kind: 'conflict',
                    title: 'Exportação em andamento',
                    body:
                        error.response?.data?.message ||
                        'Já existe uma exportação em andamento. Se continuar, a exportação atual será cancelada e uma nova será iniciada.',
                    pendingPayload: payload,
                });
                setShowFieldsModal(false);
            } else if (error.response?.status !== 419) {
                setExportModal({
                    kind: 'error',
                    title: 'Não foi possível iniciar',
                    body: error.response?.data?.message || error.message || 'Erro desconhecido.',
                });
            }
        } finally {
            setExportSubmitting(false);
        }
    };

    const handleForceReplaceExport = async () => {
        if (exportModal?.kind !== 'conflict' || !exportModal.pendingPayload) {
            return;
        }
        setExportSubmitting(true);
        try {
            await axios.post(route('student-exports.start'), {
                ...exportModal.pendingPayload,
                force: true,
            });
            setExportModal({
                kind: 'started',
                title: 'Nova exportação iniciada',
                body: 'A exportação anterior foi cancelada. Clique em OK para abrir a tela de exportações.',
            });
        } catch (forceErr) {
            if (forceErr.response?.status !== 419) {
                setExportModal({
                    kind: 'error',
                    title: 'Erro ao substituir exportação',
                    body: forceErr.response?.data?.message || forceErr.message || 'Erro desconhecido.',
                });
            }
        } finally {
            setExportSubmitting(false);
        }
    };

    const handleRedeEnsinoChange = (value) => {
        setSelectedRedeEnsino(value);

        if (value != selectedRedeEnsino) {
            router.visit(`/schools/${value}`, {
                preserveState: true,
                preserveScroll: true,
                replace: true
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        🏫 Sistema Educacional Municipal
                    </h2>
                    <div className="flex items-center space-x-4">
                        {selectedSchool && (
                            <div className="text-sm text-gray-600">
                                Escola Ativa: <span className="font-medium text-indigo-600">{selectedSchool.outDescNomeEscola || selectedSchool.name}</span>
                            </div>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Escolas - Sistema Educacional" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Header com teste de conexão */}
                    <div className="mb-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        Selecione uma Escola para Gerenciar
                                    </h3>
                                    <p className="text-sm text-gray-600">
                                        Escolha a escola que deseja administrar através do sistema SED
                                    </p>
                                </div>
                                <div className="mt-4 md:mt-0">
                                    <button
                                        onClick={testConnection}
                                        disabled={testingConnection}
                                        className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white ${
                                            connectionResult?.success 
                                                ? 'bg-green-600 hover:bg-green-700' 
                                                : 'bg-red-600 hover:bg-red-700'
                                        } disabled:opacity-50 transition-colors`}
                                    >
                                        {testingConnection ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Testando...
                                            </>
                                        ) : (
                                            <>
                                                {connectionResult?.success ? '✅' : '❌'}
                                                <span className="ml-2">Testar Conexão SED</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mt-3">
                                {redesEnsino.map((rede) => (
                                    <button
                                        key={rede.value}
                                        onClick={() => handleRedeEnsinoChange(rede.value)}
                                        disabled={loadingRedeEnsino}
                                        className={`
                                            relative p-4 rounded-lg border-2 transition-all duration-200 text-left
                                            ${selectedRedeEnsino == rede.value 
                                                ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-md' 
                                                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                                            }
                                            ${loadingRedeEnsino ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                                        `}
                                    >
                                        <div className="flex items-center space-x-3">
                                            <span className="text-2xl">{rede.icon}</span>
                                            <div>
                                                <div className="font-medium text-sm">{rede.label}</div>
                                                <div className="text-xs text-gray-500">Código {rede.value}</div>
                                            </div>
                                        </div>
                                        {selectedRedeEnsino == rede.value && (
                                            <div className="absolute top-2 right-2">
                                                <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                                            </div>
                                        )}
                                    </button>
                                ))}
                            </div>

                            {/* Status da conexão */}
                            {connectionResult && (
                                <div className={`mt-4 p-3 rounded-md ${
                                    connectionResult.success 
                                        ? 'bg-green-50 border border-green-200' 
                                        : 'bg-red-50 border border-red-200'
                                }`}>
                                    <p className={`text-sm ${
                                        connectionResult.success ? 'text-green-700' : 'text-red-700'
                                    }`}>
                                        {connectionResult.message}
                                    </p>
                                    {connectionResult.error && (
                                        <details className="mt-2">
                                            <summary className="cursor-pointer text-xs font-medium">Ver detalhes</summary>
                                            <pre className="mt-1 text-xs bg-gray-100 p-2 rounded overflow-auto max-h-20">
                                                {connectionResult.error}
                                            </pre>
                                        </details>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Busca */}
                    <div className="mb-6 flex items-end justify-between gap-4">
                        <div className="max-w-md flex-1">
                            <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-2">
                                Buscar Escola
                            </label>
                            <input
                                type="text"
                                id="search"
                                placeholder="Digite o nome ou código da escola..."
                                value={searchTerm}
                                onChange={(e) => handleSearch(e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div className="flex flex-wrap items-center gap-2 shrink-0">
                            <Link
                                href={route('student-exports.index')}
                                className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Ver exportações
                            </Link>
                            <button
                                type="button"
                                disabled={selectedForExport.size === 0 || exportSubmitting}
                                onClick={handleStartAsyncExport}
                                className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors ${
                                    selectedForExport.size > 0 && !exportSubmitting
                                        ? 'bg-green-600 text-white hover:bg-green-700 cursor-pointer'
                                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                }`}
                            >
                                {exportSubmitting ? (
                                    <>
                                        <svg className="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Enviando…
                                    </>
                                ) : (
                                    <>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Exportar Alunos{selectedForExport.size > 0 && ` (${selectedForExport.size})`}
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Lista de Escolas */}
                    <div className="bg-white shadow-sm sm:rounded-lg pt-2">
                        {filteredSchools.length > 0 && (
                            <div className="flex items-center justify-between px-6 py-3 border-b border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => {
                                        const allSelected = filteredSchools.every(s => selectedForExport.has(s.outCodEscola));
                                        if (allSelected) {
                                            setSelectedForExport(new Set());
                                        } else {
                                            setSelectedForExport(new Set(filteredSchools.map(s => s.outCodEscola)));
                                        }
                                    }}
                                    className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
                                >
                                    <div className={`w-5 h-5 rounded border-2 flex items-center justify-center transition-colors ${
                                        filteredSchools.every(s => selectedForExport.has(s.outCodEscola))
                                            ? 'bg-green-500 border-green-500'
                                            : filteredSchools.some(s => selectedForExport.has(s.outCodEscola))
                                                ? 'bg-green-200 border-green-400'
                                                : 'bg-white border-gray-300'
                                    }`}>
                                        {filteredSchools.every(s => selectedForExport.has(s.outCodEscola)) && (
                                            <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                        {!filteredSchools.every(s => selectedForExport.has(s.outCodEscola)) && filteredSchools.some(s => selectedForExport.has(s.outCodEscola)) && (
                                            <div className="w-2 h-0.5 bg-green-600 rounded" />
                                        )}
                                    </div>
                                    {filteredSchools.every(s => selectedForExport.has(s.outCodEscola))
                                        ? 'Desmarcar todas'
                                        : 'Selecionar todas'}
                                </button>
                                <span className="text-xs text-gray-400">
                                    {filteredSchools.length} escola{filteredSchools.length !== 1 ? 's' : ''}
                                    {selectedForExport.size > 0 && ` · ${selectedForExport.size} selecionada${selectedForExport.size !== 1 ? 's' : ''}`}
                                </span>
                            </div>
                        )}
                        <div className="px-4 py-5 sm:p-6">
                            {filteredSchools.length === 0 ? (
                                <div className="text-center py-12">
                                    <div className="text-gray-400 text-6xl mb-4">🏫</div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        {schools.length === 0 ? 'Nenhuma escola encontrada' : 'Nenhuma escola corresponde à busca'}
                                    </h3>
                                    <p className="text-gray-500">
                                        {schools.length === 0 
                                            ? 'Verifique a conexão com a API SED ou entre em contato com o suporte.'
                                            : 'Tente ajustar os termos de busca.'
                                        }
                                    </p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {filteredSchools.map((school) => (
                                        <div
                                            key={school.outCodEscola}
                                            className={`relative rounded-lg border-2 p-6 cursor-pointer transition-all hover:shadow-md ${
                                                selectedForExport.has(school.outCodEscola)
                                                    ? 'border-green-500 bg-green-50'
                                                    : selectedSchool?.outCodEscola === school.outCodEscola
                                                        ? 'border-indigo-500 bg-indigo-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                            }`}
                                            onClick={(e) => toggleExportSelection(e, school)}
                                        >
                                            {/* Checkbox de seleção para exportação */}
                                            <div className="absolute top-3 right-3">
                                                <div className={`w-5 h-5 rounded border-2 flex items-center justify-center transition-colors ${
                                                    selectedForExport.has(school.outCodEscola)
                                                        ? 'bg-green-500 border-green-500'
                                                        : 'bg-white border-gray-300'
                                                }`}>
                                                    {selectedForExport.has(school.outCodEscola) && (
                                                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                                        </svg>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-start pr-6">
                                                <div className="flex-shrink-0">
                                                    <div className="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                        <span className="text-2xl">🏫</span>
                                                    </div>
                                                </div>
                                                <div className="ml-4 flex-1">
                                                    <h3 className="text-base font-semibold text-gray-900 mb-1">
                                                        {school.outDescNomeEscola || 'Nome não disponível'}
                                                    </h3>
                                                    <p className="text-sm text-gray-500 mb-1">
                                                        Código: {school.outCodEscola || 'N/A'}
                                                    </p>
                                                    {school.outUnidades && school.outUnidades[0] && (
                                                        <p className="text-xs text-gray-400">
                                                            📍 {school.outUnidades[0].outDescNomeUnidade}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                                <span className="text-xs text-gray-400">Escola Municipal</span>
                                                <button
                                                    type="button"
                                                    disabled={loadingSchool !== null}
                                                    onClick={(e) => { e.stopPropagation(); handleSelectSchool(school); }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                >
                                                    {loadingSchool === school.outCodEscola ? (
                                                        <>
                                                            <svg className="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                            </svg>
                                                            Abrindo...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                            Abrir escola
                                                        </>
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            <ExportFieldsModal
                open={showFieldsModal}
                title="Selecionar campos para exportação"
                subtitle={`Você selecionou ${selectedForExport.size} escola${selectedForExport.size === 1 ? '' : 's'}. Agora escolha quais campos deseja incluir no CSV.`}
                selectedFields={selectedFields}
                onSelectedFieldsChange={setSelectedFields}
                onClose={() => setShowFieldsModal(false)}
                onConfirm={confirmStartAsyncExport}
                confirmLabel={exportSubmitting ? 'Enviando…' : 'Iniciar exportação'}
                readOnly={false}
            />
            {exportModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="export-modal-title"
                >
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 id="export-modal-title" className="text-lg font-semibold text-gray-900">
                            {exportModal.title || 'Atenção'}
                        </h3>
                        <p className="mt-2 text-sm text-gray-600 whitespace-pre-line">{exportModal.body}</p>

                        <div className="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-2">
                            {exportModal.kind === 'conflict' && (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => setExportModal(null)}
                                        className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-center whitespace-normal"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        onClick={goToStudentExports}
                                        className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 text-center whitespace-normal"
                                    >
                                        Ir para exportações
                                    </button>
                                    <button
                                        type="button"
                                        disabled={exportSubmitting}
                                        onClick={handleForceReplaceExport}
                                        className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-white bg-amber-600 border border-transparent rounded-md hover:bg-amber-700 disabled:opacity-50 text-center whitespace-normal"
                                    >
                                        {exportSubmitting ? 'Substituindo…' : 'Continuar e substituir'}
                                    </button>
                                </>
                            )}

                            {exportModal.kind === 'started' && (
                                <button
                                    type="button"
                                    onClick={goToStudentExports}
                                    className="sm:col-span-3 w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700"
                                >
                                    OK
                                </button>
                            )}

                            {exportModal.kind === 'error' && (
                                <button
                                    type="button"
                                    onClick={() => setExportModal(null)}
                                    className="sm:col-span-3 w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Fechar
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}