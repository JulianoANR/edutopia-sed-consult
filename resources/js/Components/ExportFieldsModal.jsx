import { EXPORT_FIELDS, getFieldByKey, getFieldsByCategory } from '@/config/export-fields.js';

export default function ExportFieldsModal({
    open,
    title,
    subtitle,
    selectedFields,
    onSelectedFieldsChange,
    onClose,
    onConfirm,
    confirmLabel = 'OK',
    readOnly = false,
    showSummary = true,
}) {
    if (!open) return null;

    const safeSelected = Array.isArray(selectedFields) ? selectedFields : [];

    const toggleField = (fieldKey) => {
        if (readOnly) return;
        onSelectedFieldsChange?.((prev) => {
            const arr = Array.isArray(prev) ? prev : [];
            if (arr.includes(fieldKey)) return arr.filter(k => k !== fieldKey);
            return [...arr, fieldKey];
        });
    };

    const toggleCategoryFields = (categoryFields, currentlyAllSelected) => {
        if (readOnly) return;
        const categoryKeys = categoryFields.map(f => f.key);
        onSelectedFieldsChange?.((prev) => {
            const arr = Array.isArray(prev) ? prev : [];
            if (currentlyAllSelected) return arr.filter(k => !categoryKeys.includes(k));
            const next = [...arr];
            categoryKeys.forEach(k => {
                if (!next.includes(k)) next.push(k);
            });
            return next;
        });
    };

    const toggleAllFields = () => {
        if (readOnly) return;
        const allKeys = EXPORT_FIELDS.map(f => f.key);
        const allSelected = safeSelected.length === allKeys.length;
        onSelectedFieldsChange?.(allSelected ? [] : allKeys);
    };

    const hasUnknownKeys = safeSelected.some(k => !getFieldByKey(k));

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            role="dialog"
            aria-modal="true"
            aria-labelledby="export-fields-modal-title"
            onMouseDown={(e) => {
                if (e.target === e.currentTarget) onClose?.();
            }}
        >
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="export-fields-modal-title" className="text-lg font-semibold text-gray-900">
                            {title || (readOnly ? 'Campos selecionados' : 'Selecionar campos para exportação')}
                        </h3>
                        {subtitle && <p className="mt-1 text-sm text-gray-600">{subtitle}</p>}
                    </div>
                    <button
                        type="button"
                        className="text-gray-400 hover:text-gray-600"
                        onClick={() => onClose?.()}
                        aria-label="Fechar"
                    >
                        ✕
                    </button>
                </div>

                <div className="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-4">
                        <div className="text-sm text-gray-700">
                            Total selecionado: <span className="font-medium">{safeSelected.length}</span>
                        </div>
                        {!readOnly && (
                            <button
                                type="button"
                                onClick={toggleAllFields}
                                className="px-3 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100"
                            >
                                {safeSelected.length === EXPORT_FIELDS.length ? 'Desmarcar todos' : 'Marcar todos'}
                            </button>
                        )}
                    </div>

                    <div className="space-y-4 max-h-72 overflow-y-auto">
                        {Object.entries(getFieldsByCategory()).map(([categoryName, categoryFields]) => {
                            const selectedInCategory = categoryFields.filter(f => safeSelected.includes(f.key));
                            const allSelected = selectedInCategory.length === categoryFields.length;
                            const someSelected = selectedInCategory.length > 0 && selectedInCategory.length < categoryFields.length;

                            // No modo somente leitura, esconder categorias vazias deixa mais limpo
                            if (readOnly && selectedInCategory.length === 0) return null;

                            const listToRender = readOnly ? selectedInCategory : categoryFields;

                            return (
                                <div key={categoryName} className="border border-gray-200 rounded-lg p-3 bg-white">
                                    <div className="flex items-center justify-between mb-2">
                                        {!readOnly ? (
                                            <label className="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={allSelected}
                                                    ref={input => {
                                                        if (input) input.indeterminate = someSelected;
                                                    }}
                                                    onChange={() => toggleCategoryFields(categoryFields, allSelected)}
                                                    className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                />
                                                <span className="ml-2 text-sm font-medium text-gray-900">
                                                    {categoryName} ({selectedInCategory.length}/{categoryFields.length})
                                                </span>
                                            </label>
                                        ) : (
                                            <div className="text-sm font-medium text-gray-900">
                                                {categoryName}{' '}
                                                <span className="text-xs text-gray-500">
                                                    ({selectedInCategory.length})
                                                </span>
                                            </div>
                                        )}

                                        {!readOnly && (
                                            <button
                                                type="button"
                                                onClick={() => toggleCategoryFields(categoryFields, allSelected)}
                                                className="px-2 py-1 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded"
                                            >
                                                {allSelected ? 'Desmarcar' : 'Marcar'} todos
                                            </button>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-2 gap-2 ml-6">
                                        {listToRender.map(field => (
                                            <label key={field.key} className="flex items-center cursor-pointer text-sm">
                                                {!readOnly && (
                                                    <input
                                                        type="checkbox"
                                                        checked={safeSelected.includes(field.key)}
                                                        onChange={() => toggleField(field.key)}
                                                        className="h-3 w-3 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                    />
                                                )}
                                                <span className={`${readOnly ? '' : 'ml-2'} text-gray-700`}>
                                                    {field.label}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {showSummary && hasUnknownKeys && (
                    <div className="mt-4 border border-amber-200 bg-amber-50 rounded-lg p-4">
                        <div className="text-sm font-medium text-amber-900">Campos não reconhecidos</div>
                        <div className="mt-2 text-xs text-amber-900 break-words">
                            {safeSelected.filter(k => !getFieldByKey(k)).join(', ')}
                        </div>
                    </div>
                )}

                <div className="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                        type="button"
                        onClick={() => onClose?.()}
                        className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        {readOnly ? 'Fechar' : 'Voltar'}
                    </button>

                    {!readOnly && (
                        <button
                            type="button"
                            onClick={() => onConfirm?.()}
                            disabled={safeSelected.length === 0}
                            className="w-full inline-flex items-center justify-center min-h-10 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {confirmLabel}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

