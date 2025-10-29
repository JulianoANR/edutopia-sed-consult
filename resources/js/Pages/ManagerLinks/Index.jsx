import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal';

export default function ManagerLinksIndex({ links = [], gestors = [], selectedSchool = null, schools = [] }) {
  const [items, setItems] = useState(links);
  const schoolsList = useMemo(() => Array.isArray(schools) ? schools : (schools?.outEscolas ?? []), [schools]);
  const [form, setForm] = useState({ user_id: '', school_code: '', school_name: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [touched, setTouched] = useState({ user_id: false, school_code: false });
  const userError = !form.user_id ? 'Selecione um gestor.' : '';
  const schoolError = !form.school_code ? 'Selecione uma escola.' : '';
  const isFormValid = !!(form.user_id && form.school_code && form.school_name);

  useEffect(() => {
    setItems(links);
  }, [links]);

  const reload = async () => {
    router.reload({
      only: ['links'],
    });
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    setTouched({ user_id: true });
    if (!isFormValid) return;
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const payload = {
        user_id: Number(form.user_id),
        school_code: form.school_code,
        school_name: form.school_name,
      };
      await axios.post('/manager-links', payload);
      setForm({ user_id: '', school_code: '', school_name: '' });
      await reload();
      setSuccess('Vínculo criado com sucesso.');
    } catch (e) {
      const status = e.response?.status;
      const data = e.response?.data;
      if (status === 409) {
        const gestor = gestors.find(g => String(g.id) === String(form.user_id));
        const message = `Já existe um vínculo de ${gestor?.name || 'o gestor selecionado'} com esta escola.`;
        setError(message);
      } else {
        const msgs = [];
        if (data?.errors) {
          const u = data.errors.user_id?.[0];
          const s = data.errors.school_code?.[0] || data.errors.school_name?.[0];
          if (u) msgs.push(u);
          if (s) msgs.push(s);
        }
        setError(msgs.length ? msgs : (data?.message || e.message || 'Não foi possível criar o vínculo.'));
      }
    } finally {
      setLoading(false);
    }
  };

  const [confirmingId, setConfirmingId] = useState(null);
  const [confirmText, setConfirmText] = useState('Remover');
  const [confirmDesc, setConfirmDesc] = useState('');

  const remove = async (id) => {
    if (loading) return; // evita múltiplos cliques
    if (!id) {
      setError('ID do vínculo inválido para remoção.');
      return;
    }
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      await axios.post(`/manager-links/${encodeURIComponent(id)}`, { _method: 'DELETE' });
      await reload();
      setSuccess('Vínculo removido com sucesso.');
    } catch (e) {
      const data = e.response?.data;
      const msg = data?.message || data?.error || e.message || 'Não foi possível remover o vínculo.';
      setError(msg);
    } finally {
      setLoading(false);
      setConfirmingId(null);
    }
  };

  const [searchTerm, setSearchTerm] = useState('');
  const filteredItems = useMemo(() => {
    if (!searchTerm?.trim()) return items;
    const term = searchTerm.toLowerCase();
    return items.filter(l => {
      const name = (l.user?.name || '').toLowerCase();
      const email = (l.user?.email || '').toLowerCase();
      const schoolName = (l.school_name || '').toLowerCase();
      const schoolCode = String(l.school_code || '').toLowerCase();
      return (
        name.includes(term) ||
        email.includes(term) ||
        schoolName.includes(term) ||
        schoolCode.includes(term)
      );
    });
  }, [items, searchTerm]);

  return (
    <AuthenticatedLayout header={<div className="flex items-center justify-between"><h2 className="font-semibold text-xl text-gray-800 leading-tight">🔗 Vínculos Gestor ↔ Escola</h2></div>}> 
      <Head title="Gestores por Escola" />

      <div className="py-8">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {success && (
            <div className="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200 flex items-start justify-between">
              <div>
                {Array.isArray(success) ? (
                  <ul className="list-disc ml-5">
                    {success.map((msg, idx) => (
                      <li key={idx}>{msg}</li>
                    ))}
                  </ul>
                ) : (
                  <>{success}</>
                )}
              </div>
              <button
                type="button"
                onClick={() => setSuccess(null)}
                className="text-sm text-green-700 hover:text-green-900"
                aria-label="Fechar"
                title="Fechar"
              >
                ✖
              </button>
            </div>
          )}
          {error && (
            <div className="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
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

          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div className="p-6">
              <div className="mb-6">
                <h4 className="text-base font-semibold text-gray-900">Novo Vínculo (Gestor ↔ Escola)</h4>
                <p className="mt-1 text-sm text-gray-600">Selecione o gestor para a escola ativa. O vínculo impede duplicidades e pode ser removido abaixo.</p>
              </div>

              <form onSubmit={handleCreate}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Gestor</label>
                    <select
                      value={form.user_id}
                      onChange={e => setForm({ ...form, user_id: e.target.value })}
                      onBlur={() => setTouched(prev => ({ ...prev, user_id: true }))}
                      required
                      className={`w-full border-gray-300 rounded-md shadow-sm ${touched.user_id && userError ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'focus:ring-indigo-500 focus:border-indigo-500'}`}
                    >
                      <option value="">Selecione…</option>
                      {gestors.map(g => (
                        <option key={g.id} value={g.id}>{g.name} ({g.email})</option>
                      ))}
                    </select>
                    <div className="mt-2 flex items-center justify-between">
                      <p className={`text-xs ${touched.user_id && userError ? 'text-red-600' : 'text-gray-500'}`}>{touched.user_id && userError ? userError : 'Escolha o gestor para esta escola.'}</p>
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Escola</label>
                    <select
                      value={form.school_code}
                      onChange={e => {
                        const code = e.target.value;
                        const s = schoolsList.find(sc => String(sc.outCodEscola) === String(code));
                        const name = s?.outDescNomeEscola || s?.outDescNomeAbrevEscola || s?.name || '';
                        setForm({ ...form, school_code: code, school_name: name });
                      }}
                      onBlur={() => setTouched(prev => ({ ...prev, school_code: true }))}
                      required
                      className={`w-full border-gray-300 rounded-md shadow-sm ${touched.school_code && schoolError ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'focus:ring-indigo-500 focus:border-indigo-500'}`}
                    >
                      <option value="">Selecione…</option>
                      {schoolsList.map(sc => (
                        <option key={sc.outCodEscola} value={sc.outCodEscola}>
                          {sc.outDescNomeEscola || sc.outDescNomeAbrevEscola || sc.name} ({sc.outCodEscola})
                        </option>
                      ))}
                    </select>
                    <div className="mt-2 flex items-center justify-between">
                      <p className={`text-xs ${touched.school_code && schoolError ? 'text-red-600' : 'text-gray-500'}`}>{touched.school_code && schoolError ? schoolError : 'Selecione a escola do vínculo.'}</p>
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
                    onClick={() => { setForm({ user_id: '', school_code: '', school_name: '' }); setTouched({ user_id: false, school_code: false }); setError(null); setSuccess(null); }}
                    className="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                  >
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4h16M7 8h10M10 12h7M13 16h4" /></svg>
                    Limpar
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">Buscar vínculos</label>
                <input
                  type="text"
                  value={searchTerm}
                  onChange={e => setSearchTerm(e.target.value)}
                  placeholder="Gestor, email ou escola"
                  className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gestor</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                      <th className="px-4 py-2"></th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {filteredItems.map((l) => (
                      <tr key={l.id}>
                        <td className="px-4 py-2 text-sm text-gray-900">{l.user?.name} ({l.user?.email})</td>
                        <td className="px-4 py-2">
                          <span className="text-sm text-gray-700">{l.school_name || l.school_code}</span>
                        </td>
                        <td className="px-4 py-2 text-right space-x-2">
                          <button
                            onClick={() => {
                              const gestorName = l.user?.name || 'Gestor';
                              const schoolName = l.school_name || l.school_code || 'Escola';
                              setConfirmDesc(`Tem certeza que deseja remover o vínculo de ${gestorName} com a escola ${schoolName}?`);
                              setConfirmText('Remover');
                              setConfirmingId(l.id);
                            }}
                            className="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md"
                          >
                            Remover
                          </button>
                        </td>
                      </tr>
                    ))}
                    {filteredItems.length === 0 && (
                      <tr>
                        <td className="px-4 py-6 text-center text-gray-500" colSpan={3}>Nenhum vínculo encontrado.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <ConfirmDeleteModal
          open={!!confirmingId}
          title="Remover vínculo"
          description={confirmDesc}
          confirmText={confirmText}
          cancelText="Cancelar"
          onConfirm={() => remove(confirmingId)}
          onCancel={() => setConfirmingId(null)}
          confirmDisabled={loading}
        />
      </div>
    </AuthenticatedLayout>
  );
}