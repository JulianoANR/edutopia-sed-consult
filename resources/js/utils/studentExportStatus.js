/**
 * Status armazenados no backend (student_export_requests.status).
 * Exibição em português para a UI.
 */
export const STUDENT_EXPORT_STATUS_LABELS = {
    pending: 'Pendente',
    processing: 'Em processamento',
    done: 'Concluída',
    failed: 'Falhou',
    cancelled: 'Cancelada',
};

/**
 * @param {string|null|undefined} status
 * @param {{ fallback?: string }} [options]
 * @returns {string}
 */
export function formatStudentExportStatusPt(status, { fallback = '—' } = {}) {
    if (status == null || status === '') {
        return fallback;
    }
    return STUDENT_EXPORT_STATUS_LABELS[status] ?? status;
}
