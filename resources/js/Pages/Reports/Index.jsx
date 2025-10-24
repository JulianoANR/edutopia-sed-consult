import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState, useEffect } from 'react';
import axios from 'axios';

export default function ReportsIndex({ schools = [], disciplines = [], tiposEnsino = [] }) {
  const [filters, setFilters] = useState({ school: '', discipline: '', tipoEnsino: '', classCode: '' });
  const [touched, setTouched] = useState({ school: false, discipline: false, tipoEnsino: false, classCode: false });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [toast, setToast] = useState(null);

  // Turmas (placeholder: serão carregadas após a escola ser selecionada)
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);

  // Helpers para opções de selects
  const getSchoolLabel = (s) => s?.school_name || s?.outDescNomeEscola || s?.name || `Escola ${s?.outCodEscola || s?.id || ''}`;
  const getSchoolValue = (s, idx) => s?.outCodEscola || s?.outIdentEscola || s?.code || s?.id || String(idx);

  const schoolOptions = useMemo(() => schools.map((s, idx) => ({ value: getSchoolValue(s, idx), label: getSchoolLabel(s), raw: s })), [schools]);
  const disciplineOptions = useMemo(() => disciplines.map((d) => ({ value: String(d?.id ?? ''), label: `${d?.name ?? ''}${d?.code ? ` (${d.code})` : ''}` })), [disciplines]);
  // idToLabel removido: não necessário para seleção única
  const tipoEnsinoOptions = useMemo(() => (Array.isArray(tiposEnsino) ? tiposEnsino : []).map((t) => ({ value: String(t), label: String(t) })), [tiposEnsino]);

  useEffect(() => {
  if (!filters.school) {
    setClasses([]);
    setLoadingClasses(false);
    return;
  }

  const fetchClasses = async () => {
    try {
      setLoadingClasses(true);
      setError(null);
      setClasses([]);

      const schoolCode = filters.school;
      const url = (typeof route === 'function') ? route('sed-api.classes') : '/sed-api/classes';

      const response = await axios.get(url, {
        params: {
          cod_escola: schoolCode,
          ano_letivo: String(new Date().getFullYear()),
        }
      });

      const fetchedClasses = response.data?.data?.outClasses || [];
      setClasses(Array.isArray(fetchedClasses) ? fetchedClasses : []);
    } catch (err) {
      console.error('Erro ao buscar turmas:', err);
      setError('Falha ao buscar as turmas. Tente novamente mais tarde.');
      setClasses([]);
    } finally {
      setLoadingClasses(false);
    }
  };

  fetchClasses();
}, [filters.school]);

  const clearFilters = () => {
    setFilters({ school: '', discipline: '', tipoEnsino: '', classCode: '' });
    setTouched({ school: false, discipline: false, tipoEnsino: false, classCode: false });
    setError(null);
  };

  const generateReport = (e) => {
    e.preventDefault();
    setTouched({ school: true, discipline: true, tipoEnsino: true, classCode: true });
    // Validação mínima: exigir escola para habilitar turma (no futuro)
    if (!filters.school) {
      setError('Selecione uma escola para continuar.');
      return;
    }
    setError(null);
    setLoading(true);
    setToast('Filtros aplicados (pré-visualização).');
    const t = setTimeout(() => setToast(null), 2000);
    setTimeout(() => {
      setLoading(false);
      clearTimeout(t);
    }, 800);
  };

  return (
    <AuthenticatedLayout
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 leading-tight">📊 Relatórios de Frequência</h2>
          <div className="flex items-center space-x-3">
            <div className="flex items-center text-sm text-gray-600">
              <span className="mr-2">🧪</span>
              <span>Selecione filtros e gere o relatório consolidado.</span>
            </div>
          </div>
        </div>
      }
    >
      <Head title="Relatórios de Frequência" />

      <div className="py-8">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {/* Painel de Filtros */}
          <div className="mb-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              <div className="mb-6">
                <h4 className="text-base font-semibold text-gray-900">Filtros do Relatório</h4>
                <p className="mt-1 text-sm text-gray-600">Escolha os filtros abaixo. O campo de turma será habilitado após selecionar a escola.</p>
              </div>

              {error && (
                <div className="mb-6 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                  {error}
                </div>
              )}

              <form onSubmit={generateReport}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Escola */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Escola</label>
                    <select
                      value={filters.school}
                      onChange={(e) => setFilters((prev) => ({ ...prev, school: e.target.value, classCode: '' }))}
                      onBlur={() => setTouched((prev) => ({ ...prev, school: true }))}
                      className={`w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 ${touched.school && !filters.school ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : ''}`}
                    >
                      <option value="">Selecione uma escola</option>
                      {schoolOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                    <p className={`mt-2 text-xs ${touched.school && !filters.school ? 'text-red-600' : 'text-gray-500'}`}>
                      {touched.school && !filters.school ? 'Obrigatório.' : 'Escolha a escola alvo do relatório.'}
                    </p>
                  </div>

                  {/* Disciplina (seleção única) */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                    <select
                      value={filters.discipline}
                      onChange={(e) => setFilters((prev) => ({ ...prev, discipline: e.target.value }))}
                      onBlur={() => setTouched((prev) => ({ ...prev, discipline: true }))}
                      className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Todas</option>
                      {disciplineOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                    <p className="mt-2 text-xs text-gray-500">Opcional. Filtra o relatório por disciplina específica.</p>
                  </div>

                  {/* Tipo de Ensino */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de Ensino</label>
                    <select
                      value={filters.tipoEnsino}
                      onChange={(e) => setFilters((prev) => ({ ...prev, tipoEnsino: e.target.value }))}
                      onBlur={() => setTouched((prev) => ({ ...prev, tipoEnsino: true }))}
                      className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Todos</option>
                      {tipoEnsinoOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                    <p className="mt-2 text-xs text-gray-500">Opcional. Filtra por modalidade/etapa.</p>
                  </div>

                  {/* Turma (carregada após escola) */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                    <select
                      value={filters.classCode}
                      onChange={(e) => setFilters((prev) => ({ ...prev, classCode: e.target.value }))}
                      onBlur={() => setTouched((prev) => ({ ...prev, classCode: true }))}
                      disabled={!filters.school || loadingClasses || !classes.length}
                      className={`w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 ${!filters.school ? 'bg-gray-100 text-gray-500' : ''}`}
                    >
                      <option value="">
                        {(!filters.school) ? 'Selecione a escola para habilitar' : (loadingClasses ? 'Carregando turmas...' : (classes.length ? 'Todas' : 'Sem turmas disponíveis'))}
                      </option>
                      {classes.map((cls) => (
                        <option key={cls?.outNumClasse || cls?.code || cls?.id} value={cls?.outNumClasse || cls?.code || cls?.id}>
                          {cls?.nome_turma ?? '-'}
                        </option>
                      ))}
                    </select>
                    <p className="mt-2 text-xs text-gray-500">Será habilitado quando uma escola for selecionada.</p>
                  </div>
                </div>

                <div className="mt-8 flex items-center gap-3">
                  <button
                    type="submit"
                    disabled={loading || !filters.school}
                    className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                  >
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                    Gerar Relatório
                  </button>
                  <button
                    type="button"
                    onClick={clearFilters}
                    className="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                  >
                    <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4h16M7 8h10M10 12h7M13 16h4" /></svg>
                    Limpar
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Área de pré-visualização/sumário (mock) */}
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              <h4 className="text-base font-semibold text-gray-900">Pré-visualização</h4>
              <p className="mt-1 text-sm text-gray-600">Esta seção exibirá o relatório após a geração. Por enquanto, mostra os filtros selecionados.</p>
              <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div className="rounded-md border border-gray-200 p-3">
                  <span className="font-medium text-gray-700">Escola:</span> <span className="text-gray-900">{filters.school || '—'}</span>
                </div>
                <div className="rounded-md border border-gray-200 p-3">
                  <span className="font-medium text-gray-700">Disciplina:</span> <span className="text-gray-900">{filters.discipline || 'Todas'}</span>
                </div>
                <div className="rounded-md border border-gray-200 p-3">
                  <span className="font-medium text-gray-700">Tipo de Ensino:</span> <span className="text-gray-900">{filters.tipoEnsino || 'Todos'}</span>
                </div>
                <div className="rounded-md border border-gray-200 p-3">
                  <span className="font-medium text-gray-700">Turma:</span> <span className="text-gray-900">{filters.classCode || '—'}</span>
                </div>
              </div>
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
    </AuthenticatedLayout>
  );
}

// [Removido] Efeito duplicado; lógica de busca de turmas está dentro do componente.