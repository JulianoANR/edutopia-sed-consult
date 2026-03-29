import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import axios from 'axios';
import { formatStudentExportStatusPt } from '@/utils/studentExportStatus';
import ExportFieldsModal from '@/Components/ExportFieldsModal';

function formatSchools(schoolCodes) {
    if (!Array.isArray(schoolCodes) || schoolCodes.length === 0) return '—';
    const names = schoolCodes.map(s => s?.name || s?.code).filter(Boolean);
    if (names.length <= 2) return names.join(', ');
    return `${names.slice(0, 2).join(', ')} +${names.length - 2}`;
}

export default function StudentExportsIndex({ requests, active, latest_done, has_latest_file }) {
    const [schoolsModal, setSchoolsModal] = useState(null);
    const [fieldsModal, setFieldsModal] = useState(null); // { id, selected_fields }
    const [reloading, setReloading] = useState(false);
    const [cancellingId, setCancellingId] = useState(null);
    const [cancelModal, setCancelModal] = useState(null); // { row }
    const [uiModal, setUiModal] = useState(null); // { title, body }

    const latestStatusText = useMemo(() => {
        if (!active?.status) return '—';
        return formatStudentExportStatusPt(active.status);
    }, [active]);

    const handleReloadStatus = () => {
        router.reload({
            only: ['requests', 'active', 'latest_done', 'has_latest_file'],
            preserveScroll: true,
            onStart: () => setReloading(true),
            onFinish: () => setReloading(false),
        });
    };

    const handleCancelExport = (row) => {
        if (!['pending', 'processing'].includes(row.status)) {
            return;
        }
        setCancelModal({ row });
    };

    const confirmCancelExport = async () => {
        const row = cancelModal?.row;
        if (!row) return;

        setCancellingId(row.id);
        try {
            await axios.post(route('student-exports.cancel', row.id));
            setCancelModal(null);
            setUiModal({
                title: 'Exportação cancelada',
                body: 'O processo foi interrompido e o status foi marcado como cancelado.',
            });
            router.reload({
                only: ['requests', 'active', 'latest_done', 'has_latest_file'],
                preserveScroll: true,
            });
        } catch (e) {
            if (e.response?.status === 419) {
                return;
            }
            setCancelModal(null);
            setUiModal({
                title: 'Não foi possível cancelar',
                body: e.response?.data?.message || e.message || 'Erro desconhecido.',
            });
        } finally {
            setCancellingId(null);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Exportações de Alunos
                    </h2>
                    <div className="flex items-center gap-3">
                        <Link
                            href="/schools"
                            className="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-white border border-gray-200 text-gray-700 hover:bg-gray-50"
                        >
                            Voltar para escolas
                        </Link>
                        <a
                            href="/student-exports/download-latest"
                            className={`inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white ${
                                has_latest_file ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'
                            }`}
                            onClick={(e) => {
                                if (!has_latest_file) e.preventDefault();
                            }}
                        >
                            Baixar último CSV
                        </a>
                    </div>
                </div>
            }
        >
            <Head title="Exportações de Alunos" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex flex-wrap items-center justify-between gap-3 w-full">
                                <h3 className="text-lg font-medium text-gray-900 text-left">Status atual</h3>
                                <button
                                    type="button"
                                    onClick={handleReloadStatus}
                                    disabled={reloading}
                                    className="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                                >
                                    {reloading ? (
                                        <svg className="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                    ) : (
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    )}
                                    Recarregar
                                </button>
                            </div>
                            <p className="mt-1 text-sm text-gray-600">
                                As exportações são processadas em segundo plano. Apenas o último arquivo fica disponível para download. Use{' '}
                                <span className="font-medium text-gray-800">Recarregar</span> para atualizar status, histórico e disponibilidade do CSV.
                            </p>

                            <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="rounded-lg border border-gray-200 p-4">
                                    <div className="text-xs text-gray-500">Exportação em andamento</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">
                                        {active ? latestStatusText : 'Nenhuma'}
                                    </div>
                                    {active && (
                                        <div className="mt-2 text-xs text-gray-600 space-y-1">
                                            <div><span className="text-gray-500">Ano:</span> {active.ano_letivo}</div>
                                            <div><span className="text-gray-500">Escolas:</span> {formatSchools(active.school_codes)}</div>
                                            <div><span className="text-gray-500">Progresso:</span> {active.progress_current ?? 0} alunos</div>
                                            {active.error_message && (
                                                <div className="text-red-700">{active.error_message}</div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="rounded-lg border border-gray-200 p-4">
                                    <div className="text-xs text-gray-500">Último arquivo</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">
                                        {has_latest_file ? 'Disponível' : 'Ainda não disponível'}
                                    </div>
                                    <div className="mt-2 text-xs text-gray-600">
                                        {latest_done?.created_at ? `Gerado em ${latest_done.created_at}` : '—'}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-200 p-4">
                                    <div className="text-xs text-gray-500">Regra de concorrência</div>
                                    <div className="mt-1 text-sm font-medium text-gray-900">1 exportação por vez</div>
                                    <div className="mt-2 text-xs text-gray-600">
                                        Enquanto uma exportação estiver pendente ou processando, não é possível iniciar outra.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900">Histórico</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                O histórico mantém apenas os registros. O arquivo sempre é sobrescrito pelo último resultado.
                            </p>

                            <div className="mt-4 overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escolas</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos (parcial)</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criado em</th>
                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-100">
                                        {(requests || []).length === 0 ? (
                                            <tr>
                                                <td className="px-4 py-4 text-sm text-gray-500" colSpan={7}>Nenhuma exportação registrada ainda.</td>
                                            </tr>
                                        ) : (
                                            (requests || []).map(r => (
                                                <tr key={r.id} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 text-sm text-gray-900">{r.id}</td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                                            r.status === 'done' ? 'bg-green-100 text-green-800'
                                                            : r.status === 'failed' ? 'bg-red-100 text-red-800'
                                                            : r.status === 'processing' ? 'bg-blue-100 text-blue-800'
                                                            : r.status === 'cancelled' ? 'bg-amber-100 text-amber-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {formatStudentExportStatusPt(r.status)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-700">{r.ano_letivo}</td>
                                                    <td className="px-4 py-3 text-sm text-gray-700">
                                                        <div className="flex items-center justify-between gap-3">
                                                            <div className="text-left tabular-nums">
                                                                {Array.isArray(r.school_codes)
                                                                    ? `${r.school_codes.length} escola${r.school_codes.length === 1 ? '' : 's'}`
                                                                    : '—'}
                                                            </div>
                                                            <div className="flex justify-end">
                                                                <div className="flex items-center gap-2">
                                                                    {Array.isArray(r.school_codes) && r.school_codes.length > 0 ? (
                                                                        <button
                                                                            type="button"
                                                                            className="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                                                            onClick={() => setSchoolsModal({ id: r.id, schools: r.school_codes })}
                                                                        >
                                                                            Ver escolas
                                                                        </button>
                                                                    ) : (
                                                                        <span className="text-xs text-gray-400">—</span>
                                                                    )}
                                                                    {Array.isArray(r.selected_fields) && r.selected_fields.length > 0 && (
                                                                        <button
                                                                            type="button"
                                                                            className="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                                                                            onClick={() => setFieldsModal({ id: r.id, selected_fields: r.selected_fields })}
                                                                        >
                                                                            Ver campos ({r.selected_fields.length})
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-700">{r.progress_current ?? 0}</td>
                                                    <td className="px-4 py-3 text-sm text-gray-500">{r.created_at}</td>
                                                    <td className="px-4 py-3 text-sm text-right">
                                                        {['pending', 'processing'].includes(r.status) ? (
                                                            <button
                                                                type="button"
                                                                disabled={cancellingId === r.id}
                                                                onClick={() => handleCancelExport(r)}
                                                                className="inline-flex items-center justify-center gap-1.5 px-2 py-1 text-xs font-medium rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                                            >
                                                                {cancellingId === r.id ? (
                                                                    <>
                                                                        <svg className="animate-spin h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                                        </svg>
                                                                        Cancelando…
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                            <rect x="7" y="7" width="10" height="10" rx="1.5" />
                                                                        </svg>
                                                                        Parar
                                                                    </>
                                                                )}
                                                            </button>
                                                        ) : (
                                                            <span className="text-xs text-gray-300">—</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {schoolsModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="schools-modal-title"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) setSchoolsModal(null);
                    }}
                >
                    <div className="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h3 id="schools-modal-title" className="text-lg font-semibold text-gray-900">
                                    Escolas executadas
                                </h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Exportação #{schoolsModal.id} · {schoolsModal.schools.length} escola{schoolsModal.schools.length === 1 ? '' : 's'}
                                </p>
                            </div>
                            <button
                                type="button"
                                className="text-gray-400 hover:text-gray-600"
                                onClick={() => setSchoolsModal(null)}
                                aria-label="Fechar"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="mt-4 max-h-80 overflow-auto rounded border border-gray-200">
                            <ul className="divide-y divide-gray-100">
                                {schoolsModal.schools.map((s, idx) => (
                                    <li key={`${s?.code || 'code'}-${idx}`} className="px-4 py-3 text-sm">
                                        <div className="font-medium text-gray-900">{s?.name || '—'}</div>
                                        <div className="text-xs text-gray-500">Código: {s?.code || '—'}</div>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <button
                                type="button"
                                onClick={() => setSchoolsModal(null)}
                                className="inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            )}
            <ExportFieldsModal
                open={!!fieldsModal}
                title="Campos selecionados"
                subtitle={
                    fieldsModal
                        ? `Exportação #${fieldsModal.id} · ${fieldsModal.selected_fields.length} campo${fieldsModal.selected_fields.length === 1 ? '' : 's'}`
                        : ''
                }
                selectedFields={fieldsModal?.selected_fields || []}
                onSelectedFieldsChange={null}
                onClose={() => setFieldsModal(null)}
                onConfirm={null}
                readOnly
            />
            {cancelModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="cancel-modal-title"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) setCancelModal(null);
                    }}
                >
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 id="cancel-modal-title" className="text-lg font-semibold text-gray-900">
                            Parar exportação?
                        </h3>
                        <p className="mt-2 text-sm text-gray-600">
                            Isso vai cancelar o processamento e marcar a execução como <span className="font-medium">cancelada</span>.
                        </p>
                        <div className="mt-2 text-xs text-gray-500">
                            Exportação #{cancelModal.row?.id} · Status atual: {formatStudentExportStatusPt(cancelModal.row?.status)}
                        </div>

                        <div className="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={() => setCancelModal(null)}
                                className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Voltar
                            </button>
                            <button
                                type="button"
                                disabled={cancellingId === cancelModal.row?.id}
                                onClick={confirmCancelExport}
                                className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {cancellingId === cancelModal.row?.id ? 'Cancelando…' : 'Parar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
            {uiModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="ui-modal-title"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) setUiModal(null);
                    }}
                >
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 id="ui-modal-title" className="text-lg font-semibold text-gray-900">
                            {uiModal.title}
                        </h3>
                        <p className="mt-2 text-sm text-gray-600 whitespace-pre-line">{uiModal.body}</p>
                        <div className="mt-6 flex justify-end">
                            <button
                                type="button"
                                onClick={() => setUiModal(null)}
                                className="inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

