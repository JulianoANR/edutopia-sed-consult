import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import ConfirmDeleteModal from '@/Components/ConfirmDeleteModal';

export default function TeacherLinksIndex({ links = [], teachers = [], disciplines = [], selectedSchool = null }) {
  const [items, setItems] = useState(links.map(l => ({ ...l, editing: false })));
  const [form, setForm] = useState({ user_id: '', class_code: '', discipline_id: '', full_access: false });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Seleção de turmas via SED com base na escola globalmente selecionada
  const [schoolClasses, setSchoolClasses] = useState([]);
  // Removido estado de ano letivo: sempre usar ano atual
  const [loadingClasses, setLoadingClasses] = useState(false);
  // Seleção via SED sempre habilitada (toggle removido)

  // Estados adicionais para busca, reload e toast
  const [searchTerm, setSearchTerm] = useState('');
  const [reloading, setReloading] = useState(false);
  const [toast, setToast] = useState(null);

  // Sincroniza itens quando os props.links mudarem após reload/criação/edição/remoção
  useEffect(() => {
    setItems(links.map(l => ({ ...l, editing: false })));
  }, [links]);
  const reloadAxios = async () => {
    try {
      const res = await axios.get('/teacher-links');
      const page = res.data?.props || {};
      setItems((page.links || []).map(l => ({ ...l, editing: false })));
    } catch (e) {
      // ignore
    }
  };

  // Substitui reload para usar Inertia router com toast
  const reload = async () => {
    router.reload({
      only: ['links'],
      onStart: () => setReloading(true),
      onFinish: () => {
        setReloading(false);
        setToast('Lista atualizada');
        setTimeout(() => setToast(null), 2000);
      },
    });
  };

  // Carregar turmas quando a escola mudar (ano letivo fixo: ano atual)
  useEffect(() => {
    const loadClasses = async () => {
      if (!selectedSchool?.id) {
        setSchoolClasses([]);
        return;
      }
      setLoadingClasses(true);
      try {
        const currentYear = String(new Date().getFullYear());
        const res = await axios.get('/sed-api/classes', { params: { ano_letivo: currentYear, cod_escola: selectedSchool.id } });
        const classes = res.data?.data?.outClasses || [];
        setSchoolClasses(classes);
      } catch (e) {
        // ignore
      } finally {
        setLoadingClasses(false);
      }
    };
    loadClasses();
  }, [selectedSchool]);

  const handleCreate = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const payload = {
        user_id: Number(form.user_id),
        class_code: form.class_code,
        discipline_id: form.full_access ? null : (form.discipline_id ? Number(form.discipline_id) : null),
        full_access: !!form.full_access,
      };
      await axios.post('/teacher-links', payload);
      setForm({ user_id: '', class_code: '', discipline_id: '', full_access: false });
      await reload();
    } catch (e) {
      const status = e.response?.status;
      const data = e.response?.data;
      if (status === 409) {
        const teacher = teachers.find(t => String(t.id) === String(form.user_id));
        const cls = schoolClasses.find(c => String(c.outNumClasse) === String(form.class_code));
        const classLabel = cls ? (cls.nome_turma || [cls.outCodSerieAno, cls.outTurma, cls.outDescricaoTurno].filter(Boolean).join(' - ')) : (form.class_name || form.class_code);
        const disc = form.discipline_id ? disciplines.find(d => String(d.id) === String(form.discipline_id)) : null;
        const discLabel = form.full_access || !form.discipline_id ? 'Todas as disciplinas' : (disc?.name || 'Disciplina selecionada');
        const message = `Já existe um vínculo para ${teacher?.name || 'o professor selecionado'} na turma ${classLabel} em ${discLabel}. Para evitar duplicidades, edite o vínculo existente na lista ou escolha outra combinação de turma/disciplinas.`;
        setError(message);
      } else {
        const msgs = [];
        if (data?.errors) {
          const u = data.errors.user_id?.[0];
          const c = data.errors.class_code?.[0];
          const d = data.errors.discipline_id?.[0];
          const f = data.errors.full_access?.[0];
          if (u) msgs.push(u);
          if (c) msgs.push(c);
          if (d) msgs.push(d);
          if (f) msgs.push(f);
        }
        setError(msgs.length ? msgs : (data?.message || e.message || 'Não foi possível criar o vínculo. Verifique os campos e tente novamente.'));
      }
    } finally {
      setLoading(false);
    }
  };

  const startEdit = (id) => {
    setItems(prev => prev.map(it => it.id === id ? { ...it, editing: true } : it));
  };

  const cancelEdit = (id) => {
    setItems(prev => prev.map(it => it.id === id ? { ...it, editing: false } : it));
  };

  const saveEdit = async (id) => {
    const item = items.find(i => i.id === id);
    if (!item || !id) {
      setError('ID do vínculo inválido para atualização.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const payload = {
        class_code: String(item.class_code || '').trim(),
        discipline_id: item.full_access ? null : (item.discipline_id ? Number(item.discipline_id) : null),
        full_access: !!item.full_access,
        _method: 'PUT',
      };
      await axios.post(`/teacher-links/${encodeURIComponent(id)}`, payload);
      await reload();
    } catch (e) {
      const status = e.response?.status;
      const data = e.response?.data;
      if (status === 409) {
        const teacherName = item.user?.name || 'o professor selecionado';
        const classLabel = item.class_name || item.class_code || 'turma selecionada';
        const discLabel = item.full_access || !item.discipline_id ? 'Todas as disciplinas' : (item.discipline?.name || 'disciplina selecionada');
        const message = `Já existe um vínculo para ${teacherName} na turma ${classLabel} em ${discLabel}. Edite o vínculo existente ou escolha outra combinação para evitar duplicidade.`;
        setError(message);
      } else {
        const msgs = [];
        if (data?.errors) {
          const c = data.errors.class_code?.[0];
          const d = data.errors.discipline_id?.[0];
          const f = data.errors.full_access?.[0];
          if (c) msgs.push(c);
          if (d) msgs.push(d);
          if (f) msgs.push(f);
        }
        setError(msgs.length ? msgs : (data?.message || e.message || 'Não foi possível atualizar o vínculo. Verifique os campos e tente novamente.'));
      }
    } finally {
      setLoading(false);
    }
  };

  const [confirmingId, setConfirmingId] = useState(null);
  const [confirmText, setConfirmText] = useState("Remover");
  const [confirmDesc, setConfirmDesc] = useState("");

  const remove = async (id) => {
    if (!id) {
      setError('ID do vínculo inválido para remoção.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await axios.post(`/teacher-links/${encodeURIComponent(id)}`, { _method: 'DELETE' });
      await reload();
      setToast('Vínculo removido com sucesso.');
      setTimeout(() => setToast(null), 2000);
    } catch (e) {
      const data = e.response?.data;
      const msg = data?.message || data?.error || e.message || 'Não foi possível remover o vínculo.';
      setError(msg);
    } finally {
      setLoading(false);
      setConfirmingId(null);
    }
  };

  // Lista filtrada para busca
  const filteredItems = useMemo(() => {
    if (!searchTerm?.trim()) return items;
    const term = searchTerm.toLowerCase();
    return items.filter(l => (
      (l.user?.name || '').toLowerCase().includes(term) ||
      (l.user?.email || '').toLowerCase().includes(term) ||
      (l.class_code || '').toLowerCase().includes(term) ||
      (l.class_name || '').toLowerCase().includes(term) ||
      (String(l.school_year || '')).toLowerCase().includes(term) ||
      (l.discipline?.name || '').toLowerCase().includes(term)
    ));
  }, [items, searchTerm]);

  return (
    // Header padronizado com escola ativa
    <AuthenticatedLayout header={<div className="flex items-center justify-between"><h2 className="font-semibold text-xl text-gray-800 leading-tight">🔗 Vínculos</h2><div className="flex items-center space-x-3">{selectedSchool && (<div className="flex items-center text-sm text-gray-600"><span className="mr-2">🏫</span><span>Escola Ativa: <span className="font-medium text-indigo-600">{selectedSchool?.school_name || selectedSchool?.outDescNomeEscola || selectedSchool?.name}</span></span><a href="/schools" className="ml-4 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"><span className="mr-2">🔁</span>Trocar Escola</a></div>)}</div></div>}> 
      <Head title="Gerenciar Vínculos" />

      <div className="py-8">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {/* Bloco de erros geral (criação/edição/remoção) */}
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

          {/* Card isolado para criar vínculo */}
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div className="p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">➕ Criar vínculo</h3>
              <form onSubmit={handleCreate}>
                {/* Seleção via SED sempre habilitada: toggle removido */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Professor</label>
                    <select value={form.user_id} onChange={e => setForm({ ...form, user_id: e.target.value })} required className="w-full border-gray-300 rounded-md shadow-sm">
                      <option value="">Selecione…</option>
                      {teachers.map(t => (
                        <option key={t.id} value={t.id}>{t.name} ({t.email})</option>
                      ))}
                    </select>
                  </div>


                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                    <select
                      value={form.class_code}
                      onChange={e => setForm({ ...form, class_code: e.target.value })}
                      required
                      className="w-full border-gray-300 rounded-md shadow-sm"
                      disabled={loadingClasses || !selectedSchool?.id}
                    >
                      <option value="">{loadingClasses ? 'Carregando turmas…' : 'Selecione a turma…'}</option>
                      {schoolClasses.map(c => (
                        <option key={c.outNumClasse} value={c.outNumClasse}>
                          {c.nome_turma || [c.outCodSerieAno, c.outTurma, c.outDescricaoTurno].filter(Boolean).join(' - ')}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                     <label className="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                     <select value={form.discipline_id} onChange={e => setForm({ ...form, discipline_id: e.target.value, full_access: e.target.value === '' })} className="w-full border-gray-300 rounded-md shadow-sm">
                       <option value="">Todas as disciplinas</option>
                       {disciplines.map(d => (
                         <option key={d.id} value={d.id}>{d.name} ({d.code || '-'})</option>
                       ))}
                     </select>
                   </div>
                </div>
                <div className="mt-3">
                  <button type="submit" disabled={loading} className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md disabled:opacity-50">Adicionar vínculo</button>
                </div>
              </form>
            </div>
          </div>

          {/* Card da listagem e busca de vínculos */}
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">Buscar vínculos</label>
                <input
                  type="text"
                  value={searchTerm}
                  onChange={e => setSearchTerm(e.target.value)}
                  placeholder="Professor, turma, ano letivo ou disciplina"
                  className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                     <tr>
                       <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor</th>
                       <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                       <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                       <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                       <th className="px-4 py-2"></th>
                     </tr>
                   </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {filteredItems.map((l) => (
                      <tr key={l.id}>
                        <td className="px-4 py-2 text-sm text-gray-900">{l.user?.name} ({l.user?.email})</td>
                        <td className="px-4 py-2">
                          {l.editing ? (
                            <select
                              value={l.class_code || ''}
                              onChange={e => setItems(prev => prev.map(it => it.id === l.id ? { ...it, class_code: e.target.value } : it))}
                              className="w-full border-gray-300 rounded-md shadow-sm"
                              disabled={loadingClasses || !selectedSchool?.id}
                            >
                              <option value="">{loadingClasses ? 'Carregando turmas…' : 'Selecione a turma…'}</option>
                              {schoolClasses.map(c => (
                                <option key={c.outNumClasse} value={c.outNumClasse}>
                                  {c.nome_turma || [c.outCodSerieAno, c.outTurma, c.outDescricaoTurno].filter(Boolean).join(' - ')}
                                </option>
                              ))}
                            </select>
                          ) : (
                            <span className="text-sm text-gray-700">{l.class_name || l.class_code}</span>
                          )}
                        </td>
                        <td className="px-4 py-2">
                          <span className="text-sm text-gray-700">{l.school_year || '-'}</span>
                        </td>
                        <td className="px-4 py-2">
                          {l.editing ? (
                            <select value={l.discipline_id || ''} onChange={e => setItems(prev => prev.map(it => it.id === l.id ? { ...it, discipline_id: e.target.value || null, full_access: e.target.value === '' } : it))} className="w-full border-gray-300 rounded-md shadow-sm">
                              <option value="">Todas as disciplinas</option>
                              {disciplines.map(d => (
                                <option key={d.id} value={d.id}>{d.name} ({d.code || '-'})</option>
                              ))}
                            </select>
                          ) : (
                            <span className="text-sm text-gray-700">{(l.full_access || !l.discipline_id) ? 'Todas as disciplinas' : (l.discipline?.name || '-')}</span>
                          )}
                        </td>
                        <td className="px-4 py-2 text-right space-x-2">
                          {l.editing ? (
                            <>
                              <button onClick={() => saveEdit(l.id)} disabled={loading} className="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-md disabled:opacity-50">Salvar</button>
                              <button onClick={() => cancelEdit(l.id)} className="px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md">Cancelar</button>
                            </>
                          ) : (
                             <>
                               <button onClick={() => startEdit(l.id)} className="px-3 py-1.5 text-sm bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md">Editar</button>
                               <button
                                 onClick={() => {
                                   const teacherName = l.user?.name || 'Professor(a)';
                                   const className = l.class_name || l.class_code || 'Turma';
                                   const disciplineName = (l.full_access || !l.discipline_id) ? 'Todas as disciplinas' : (l.discipline?.name || 'Disciplina');
                                   setConfirmDesc(`Tem certeza que deseja remover o vínculo de ${teacherName} com a turma ${className} na disciplina ${disciplineName}?`);
                                   setConfirmText('Remover');
                                   setConfirmingId(l.id);
                                 }}
                                 className="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md"
                               >
                                 Remover
                               </button>
                             </>
                           )}
                         </td>
                      </tr>
                    ))}
                    {filteredItems.length === 0 && (
                      <tr>
                        <td className="px-4 py-6 text-center text-gray-500" colSpan={5}>Nenhum vínculo encontrado.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {toast && (
                <div className="fixed bottom-4 right-4 z-50">
                  <div className="flex items-center space-x-2 rounded-md bg-green-600 text-white px-4 py-2 shadow-lg">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
                    <span>{toast}</span>
                  </div>
                </div>
              )}
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
        />
      </div>
    </AuthenticatedLayout>
  );
}