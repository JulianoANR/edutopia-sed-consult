import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useMemo, useState, useEffect } from 'react';
import axios from 'axios';
import ReportChart from '@/Components/ReportChart';
import ReportTable from '@/Components/ReportTable';

export default function ReportsIndex({ schools = [], disciplines = [], tiposEnsino = [] }) {
  const [filters, setFilters] = useState({ school: '', discipline: '', tipoEnsino: '', classCode: '' });
  const [touched, setTouched] = useState({ school: false, discipline: false, tipoEnsino: false, classCode: false });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [toast, setToast] = useState(null);

  // Turmas (placeholder: serão carregadas após a escola ser selecionada)
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(false);
  // Estados de relatório
  const [reportLoading, setReportLoading] = useState(false);
  const [reportError, setReportError] = useState(null);
  const [reportData, setReportData] = useState(null);

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

  const generateReport = async (e) => {
    e.preventDefault();
    setTouched({ school: true, discipline: true, tipoEnsino: true, classCode: true });
    if (!filters.school) {
      setError('Selecione uma escola para continuar.');
      return;
    }
    setError(null);

    const payload = {
      school_codes: filters.school ? [filters.school] : [],
      class_codes: filters.classCode ? [filters.classCode] : [],
      discipline_ids: filters.discipline ? [parseInt(filters.discipline, 10)] : [],
      tipo_ensino: filters.tipoEnsino ? [filters.tipoEnsino] : [],
      student_ras: [], // filtro futuro
      dates: [], // sem filtro de datas por enquanto
    };

    const url = (typeof route === 'function') ? route('reports.data') : '/reports/data';
    try {
      setReportLoading(true);
      setReportError(null);
      setReportData(null);
      const res = await axios.post(url, payload);
      if (res.data?.success) {
        setReportData(res.data);
        setToast('Relatório gerado com sucesso.');
        setTimeout(() => setToast(null), 2000);
      } else {
        throw new Error(res.data?.message || 'Falha ao gerar relatório');
      }
    } catch (err) {
      console.error('Erro ao gerar relatório:', err);
      setReportError(err.response?.data?.message || err.message || 'Erro ao gerar relatório.');
    } finally {
      setReportLoading(false);
    }
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

          {/* Área de resultados do relatório */}
          {reportLoading && (
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div className="p-6 flex items-center">
                <svg className="animate-spin h-5 w-5 text-indigo-600 mr-3" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span className="text-sm text-gray-700">Gerando relatório, aguarde...</span>
              </div>
            </div>
          )}

          {reportError && (
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div className="p-6">
                <div className="p-3 rounded bg-red-50 text-red-700 border border-red-200">
                  {reportError}
                </div>
              </div>
            </div>
          )}

          {reportData && (
            <div className="space-y-6">
              {/* Cards de resumo */}
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="rounded-lg border border-gray-200 bg-white p-4">
                  <div className="text-xs text-gray-500">Total registros</div>
                  <div className="mt-1 text-2xl font-semibold text-gray-900">{reportData.summary?.total ?? 0}</div>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4">
                  <div className="text-xs text-gray-500">Presentes</div>
                  <div className="mt-1 text-2xl font-semibold text-green-600">{reportData.summary?.present ?? 0}</div>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4">
                  <div className="text-xs text-gray-500">Faltas</div>
                  <div className="mt-1 text-2xl font-semibold text-red-600">{reportData.summary?.absent ?? 0}</div>
                </div>
                <div className="rounded-lg border border-gray-200 bg-white p-4">
                  <div className="text-xs text-gray-500">Justificadas</div>
                  <div className="mt-1 text-2xl font-semibold text-amber-600">{reportData.summary?.justified ?? 0}</div>
                </div>
              </div>

              {/* Gráfico por data */}
              <ReportChart
                title="Frequência por Data"
                categories={(reportData.byDate || []).map((d) => d.date)}
                series={[
                  { name: 'Presentes', data: (reportData.byDate || []).map((d) => d.present) },
                  { name: 'Faltas', data: (reportData.byDate || []).map((d) => d.absent) },
                  { name: 'Justificadas', data: (reportData.byDate || []).map((d) => d.justified) },
                ]}
                type="line"
                height={320}
              />

              {/* Tabela por turma */}
              <ReportTable
                title="Resumo por Turma"
                columns={[
                  { key: 'school_name', label: 'Escola' },
                  { key: 'class_name', label: 'Turma' },
                  { key: 'class_code', label: 'Código' },
                  { key: 'present', label: 'Presentes' },
                  { key: 'absent', label: 'Faltas' },
                  { key: 'justified', label: 'Justificadas' },
                  { key: 'total', label: 'Total' },
                ]}
                rows={reportData.byClass || []}
              />

              {/* Gráfico por disciplina (se existir) */}
              {(reportData.byDiscipline || []).length > 0 && (
                <ReportChart
                  title="Resumo por Disciplina"
                  categories={(reportData.byDiscipline || []).map((d) => d.discipline_name || String(d.discipline_id))}
                  series={[
                    { name: 'Presentes', data: (reportData.byDiscipline || []).map((d) => d.present) },
                    { name: 'Faltas', data: (reportData.byDiscipline || []).map((d) => d.absent) },
                    { name: 'Justificadas', data: (reportData.byDiscipline || []).map((d) => d.justified) },
                  ]}
                  type="bar"
                  height={320}
                  stacked={true}
                />
              )}
            </div>
          )}
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