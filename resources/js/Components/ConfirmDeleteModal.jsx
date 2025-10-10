import { useEffect } from 'react';

export default function ConfirmDeleteModal({
  open = false,
  title = 'Confirmar exclusão',
  description = '',
  confirmText = 'Excluir',
  cancelText = 'Cancelar',
  onConfirm = () => {},
  onCancel = () => {},
}) {
  useEffect(() => {
    if (!open) return;
    const handler = (e) => {
      if (e.key === 'Escape') {
        onCancel();
      }
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [open, onCancel]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50" role="dialog" aria-modal="true">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-gray-900/50"
        onClick={onCancel}
        aria-hidden="true"
      ></div>

      {/* Modal content */}
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <div className="w-full max-w-md rounded-lg bg-white shadow-xl">
          <div className="px-6 pt-6">
            <div className="flex items-start">
              <div className="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                <svg className="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01M5.52 19h12.96A2.52 2.52 0 0021 16.48V7.52A2.52 2.52 0 0018.48 5H5.52A2.52 2.52 0 003 7.52v8.96A2.52 2.52 0 005.52 19z" />
                </svg>
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                {description && (
                  <p className="mt-2 text-sm text-gray-600">{description}</p>
                )}
              </div>
            </div>
          </div>

          <div className="px-6 py-4 flex items-center justify-end space-x-3">
            <button
              type="button"
              onClick={onCancel}
              className="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              {cancelText}
            </button>
            <button
              type="button"
              onClick={onConfirm}
              className="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700"
            >
              {confirmText}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}