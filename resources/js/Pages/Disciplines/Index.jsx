import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState, useEffect } from 'react';
import axios from 'axios';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal';

export default function DisciplinesIndex({ disciplines = [], selectedSchool = null }) {
  const [items, setItems] = useState(disciplines.map(d => ({ ...d, editing: false })));
  const [form, setForm] = useState({ name: '', code: '' });
  const [touched, setTouched] = useState({ name: false, code: false });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [reloading, setReloading] = useState(false);
  const [toast, setToast] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [confirmText, setConfirmText] = useState('Excluir');
  const [confirmDesc, setConfirmDesc] = useState('Tem certeza que deseja remover este item? Esta ação não pode ser desfeita.');

  // Sincroniza items quando disciplines muda (após reload)
  useEffect(() => {
    setItems(disciplines.map(d => ({ ...d, editing: false })));
  }, [disciplines]);

  // Sugestão automática para o código da disciplina com base no nome
  const makeCodeSuggestion = (name) => {
    const cleaned = (name || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^A-Za-z0-9\s]/g, '')
      .trim();
    if (!cleaned) return '';
    const words = cleaned.split(/\s+/);
    const acronym = words.slice(0, 3).map(w => w[0] || '').join('');
    const base = (acronym.length >= 2 ? acronym : cleaned.slice(0, 3)).toUpperCase();
    return base.substring(0, 4);
  };
  const codeSuggestion = useMemo(() => makeCodeSuggestion(form.name), [form.name]);

  // Validações simples de formulário
  const nameError = form.name.trim().length < 3 ? 'Mínimo de 3 caracteres' : '';
  const codePattern = /^[A-Z0-9-]{2,8}$/;
  const codeError = form.code && !codePattern.test(form.code) ? 'Use 2-8 caracteres, A-Z, 0-9 e hífen (-)' : '';
  const isFormValid = !nameError && (!form.code || !codeError);

  const filteredItems = useMemo(() => {
    if (!searchTerm.trim()) return items;
    const term = searchTerm.toLowerCase();
    return items.filter(d => (d.name || '').toLowerCase().includes(term) || (d.code || '').toLowerCase().includes(term));
  }, [items, searchTerm]);

  const reload = async () => {
    router.reload({
      only: ['disciplines'],
      onStart: () => setReloading(true),
      onFinish: () => {
        setReloading(false);
        setToast('Lista atualizada');
        setTimeout(() => setToast(null), 2000);
      },
    });
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    setTouched({ name: true, code: true });
    if (!isFormValid) return;
    setLoading(true);
    setError(null);
    try {
      await axios.post('/disciplines', form);
      setForm({ name: '', code: '' });
      setTouched({ name: false, code: false });
      await reload();
    } catch (e) {
      const data = e.response?.data;
      const msgs = [];
      if (data?.errors) {
        const nameErr = data.errors.name?.[0];
        const codeErr = data.errors.code?.[0];
        if (nameErr) msgs.push(nameErr);
        if (codeErr) msgs.push(codeErr);
      }
      setError(msgs.length ? msgs : 'Não foi possível criar a disciplina. Verifique os campos e tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const clearForm = () => {
    setForm({ name: '', code: '' });
    setTouched({ name: false, code: false });
    setError(null);
  };

  const startEdit = (id) => {
    setItems(prev => prev.map(it => it.id === id ? { ...it, editing: true, _name: it.name, _code: it.code ?? '' } : it));
  };

  const cancelEdit = (id) => {
    setItems(prev => prev.map(it => it.id === id ? { ...it, editing: false, _name: undefined, _code: undefined } : it));
  };

  const saveEdit = async (id) => {
    const item = items.find(i => i.id === id);
    if (!item || !id) {
      setError('ID da disciplina inválido para atualização.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await axios.post(`/disciplines/${encodeURIComponent(id)}`, { name: item._name, code: item._code || null, _method: 'PUT' });
      await reload();
    } catch (e) {
      const data = e.response?.data;
      const msgs = [];
      if (data?.errors) {
        const nameErr = data.errors.name?.[0];
        const codeErr = data.errors.code?.[0];
        if (nameErr) msgs.push(nameErr);
        if (codeErr) msgs.push(codeErr);
      }
      setError(msgs.length ? msgs : 'Não foi possível atualizar a disciplina. Verifique os campos e tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const remove = async (id) => {
    // REMOVIDO confirm(), o fluxo de confirmação agora é via modal.
    if (!id) {
      setError('ID da disciplina inválido para remoção.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await axios.post(`/disciplines/${encodeURIComponent(id)}`, { _method: 'DELETE' });
      await reload();
    } catch (e) {
      const data = e.response?.data;
      const msg = data?.message || data?.error || e.message || 'Não foi possível remover a disciplina.';
      setError(msg);
    } finally {
      setLoading(false);
      setConfirmingId(null);
    }
  };

  return (
    <AuthenticatedLayout
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 leading-tight">📘 Disciplinas</h2>
          <div className="flex items-center space-x-3">
            {selectedSchool && (
              <div className="flex items-center text-sm text-gray-600">
                <span className="mr-2">🏫</span>
                <span>
                  Escola Ativa: <span className="font-medium text-indigo-600">{selectedSchool?.school_name || selectedSchool?.outDescNomeEscola || selectedSchool?.name}</span>
                </span>
                <a href="/schools" className="ml-4 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                  <span className="mr-2">🔁</span>
                  Trocar Escola
                </a>
              </div>
            )}
          </div>
        </div>
      }
    >
      <Head title="Gerenciar Disciplinas" />
      <div className="py-8">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {/* Painel superior com busca e ações */}
          <div className="mb-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              <div className="mb-6">
                <h4 className="text-base font-semibold text-gray-900">Nova Disciplina</h4>
                <p className="mt-1 text-sm text-gray-600">Preencha os campos abaixo. Dica: use acrônimos no código (ex.: Matemática → MAT).</p>
              </div>

              {error && (
                <div className="mb-6 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                  {Array.isArray(error) ? (
                    <ul className="list-disc ml-5">
                      {error.map((msg, idx) => (
                        <li key={idx}>{msg}</li>
                      ))}
                    </ul>
                  ) : (
                    <>{error}</>
                  )}
                </div>
              )}

              <form onSubmit={handleCreate}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input
                      type="text"
                      value={form.name}
                      onChange={e => setForm({ ...form, name: e.target.value })}
                      onBlur={() => setTouched(prev => ({ ...prev, name: true }))}
                      required
                      placeholder="Ex.: Matemática"
                      className={`w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 ${touched.name && nameError ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : ''}`}
                    />
                    <div className="mt-2 flex items-center justify-between">
                      <p className={`text-xs ${touched.name && nameError ? 'text-red-600' : 'text-gray-500'}`}>{touched.name && nameError ? nameError : 'Informe um nome claro e descritivo.'}</p>
                      <span className="text-xs text-gray-400">{form.name.trim().length} caracteres</span>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Código (opcional)</label>
                    <input
                      type="text"
                      value={form.code}
                      onChange={e => setForm({ ...form, code: e.target.value.toUpperCase() })}
                      onBlur={() => setTouched(prev => ({ ...prev, code: true }))}
                      placeholder="Ex.: MAT"
                      className={`w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 ${touched.code && codeError ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : ''}`}
                    />
                    <div className="mt-2 flex items-center justify-between">
                      <p className={`text-xs ${touched.code && codeError ? 'text-red-600' : 'text-gray-500'}`}>{touched.code && codeError ? codeError : 'Use 2-8 caracteres (A-Z, 0-9 e hífen). Será convertido para maiúsculas.'}</p>
                      {codeSuggestion && (
                        <button
                          type="button"
                          className="text-xs inline-flex items-center rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700 hover:bg-gray-200"
                          onClick={() => setForm(prev => ({ ...prev, code: codeSuggestion }))}
                        >
                          <svg className="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m4 0h-1v4h-1M12 8h.01" /></svg>
                          Usar sugestão: {codeSuggestion}
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                <div className="mt-8 flex items-center gap-3">
                  <button
                    type="submit"
                    disabled={loading || !isFormValid}
                    className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                  >
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                    Adicionar
                  </button>
                  <button
                    type="button"
                    onClick={clearForm}
                    className="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                  >
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4h16M7 8h10M10 12h7M13 16h4" /></svg>
                    Limpar
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Lista de Disciplinas em cartões */}
          <div className="mb-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              {filteredItems.length === 0 ? (
                <div className="py-12 text-center">
                  <div className="mb-4 text-6xl text-gray-400">📘</div>
                  <h3 className="mb-2 text-lg font-medium text-gray-900">Nenhuma disciplina encontrada</h3>
                  <p className="text-gray-500">Cadastre novas disciplinas ou ajuste os termos de busca.</p>
                </div>
              ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                  {filteredItems.map((d) => (
                    <div key={d.id} className="relative rounded-lg border-2 border-gray-200 p-5 transition-all hover:border-indigo-300 hover:shadow-md">
                      <div className="flex items-start">
                        <div className="flex-shrink-0">
                          <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100">
                            <span className="text-2xl">🎓</span>
                          </div>
                        </div>
                        <div className="ml-4 flex-1">
                          {d.editing ? (
                            <>
                              <label className="block text-xs font-medium text-gray-500">Nome</label>
                              <input type="text" value={d._name || ''} onChange={e => setItems(prev => prev.map(it => it.id === d.id ? { ...it, _name: e.target.value } : it))} className="w-full rounded-md border-gray-300 shadow-sm" />
                              <div className="mt-2">
                                <label className="block text-xs font-medium text-gray-500">Código</label>
                                <input type="text" value={d._code || ''} onChange={e => setItems(prev => prev.map(it => it.id === d.id ? { ...it, _code: e.target.value } : it))} className="w-full rounded-md border-gray-300 shadow-sm" />
                              </div>
                            </>
                          ) : (
                            <>
                              <h3 className="text-lg font-medium text-gray-900">{d.name}</h3>
                              <p className="text-sm text-gray-500">Código: {d.code || '—'}</p>
                            </>
                          )}
                        </div>
                      </div>
                      <div className="mt-4 flex items-center justify-end space-x-2">
                        {d.editing ? (
                          <>
                            <button onClick={() => saveEdit(d.id)} disabled={loading} className="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:opacity-50">
                              <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
                              Salvar
                            </button>
                            <button onClick={() => cancelEdit(d.id)} className="inline-flex items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-300">
                              <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                              Cancelar
                            </button>
                          </>
                        ) : (
                          <>
                            <button onClick={() => startEdit(d.id)} className="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                              <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5h2m2 0h2m-6 4h6m-6 4h6m-6 4h6M5 7h4m-4 4h4m-4 4h4" /></svg>
                              Editar
                            </button>
                            <button onClick={() => setConfirmingId(d.id)} className="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                              <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-3h4m-4 0a1 1 0 011-1h2a1 1 0 011 1m-4 0H6m8 0h2" /></svg>
                              Remover
                            </button>
                          </>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {toast && (
        <div className="fixed bottom-4 right-4 z-50">
          <div className="flex items-center space-x-2 rounded-md bg-green-600 text-white px-4 py-2 shadow-lg">
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
            <span>{toast}</span>
          </div>
        </div>
      )}
      <ConfirmDeleteModal
        open={!!confirmingId}
        title="Remover disciplina"
        description={confirmDesc}
        confirmText={confirmText}
        cancelText="Cancelar"
        onConfirm={() => remove(confirmingId)}
        onCancel={() => setConfirmingId(null)}
      />
    </AuthenticatedLayout>
  );
  <ConfirmDeleteModal
    open={!!confirmingId}
    title="Remover disciplina"
    description={confirmDesc}
    confirmText={confirmText}
    cancelText="Cancelar"
    onConfirm={() => remove(confirmingId)}
    onCancel={() => setConfirmingId(null)}
  />
}