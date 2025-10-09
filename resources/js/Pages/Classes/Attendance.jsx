import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function AttendancePage({ classCode, selectedSchool, today }) {
  const [date, setDate] = useState(today);
  const [classInfo, setClassInfo] = useState(null);
  const [students, setStudents] = useState([]);
  const [editable, setEditable] = useState(true);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);

  const loadData = async (targetDate) => {
    setLoading(true);
    setError(null);
    try {
      const res = await axios.get(`/classes/${classCode}/attendance/data`, { params: { date: targetDate } });
      const { data } = res.data;
      setClassInfo(data.class);
      setEditable(data.editable);
      setStudents((data.students || []).map(s => ({
        ...s,
        status: data.editable ? (s.status ?? 'present') : s.status,
      })));
    } catch (e) {
      setError(e.message || 'Erro ao carregar dados de frequência');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData(date);
  }, [date]);

  const handleStatusChange = (ra, status) => {
    setStudents(prev => prev.map(s => s.ra === ra ? { ...s, status } : s));
  };

  const handleNoteChange = (ra, note) => {
    setStudents(prev => prev.map(s => s.ra === ra ? { ...s, note } : s));
  };

  const markAllPresent = () => {
    if (!editable) return;
    setStudents(prev => prev.map(s => ({ ...s, status: 'present' })));
  };

  const markAllAbsent = () => {
    if (!editable) return;
    setStudents(prev => prev.map(s => ({ ...s, status: 'absent' })));
  };

  const markAllJustified = () => {
    if (!editable) return;
    setStudents(prev => prev.map(s => ({ ...s, status: 'justified' })));
  };


  const saveAttendance = async () => {
    setSaving(true);
    try {
      const payload = {
        date,
        records: students.map(s => ({ ra: s.ra, status: s.status || null, note: s.note || null }))
      };
      const res = await axios.post(`/classes/${classCode}/attendance/save`, payload);
      if (res.data.success) {
        await loadData(date);
      }
    } catch (e) {
      setError(e.response?.data?.message || e.message || 'Erro ao salvar frequência');
    } finally {
      setSaving(false);
    }
  };

  const statusOptions = [
    { value: null, label: '—' },
    { value: 'present', label: 'Presente' },
    { value: 'absent', label: 'Ausente' },
    { value: 'justified', label: 'Justificado' },
  ];

  return (
    <AuthenticatedLayout
      header={
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <Link href={route('classes.show', classCode)} className="text-gray-500 hover:text-gray-700 transition-colors duration-200">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </Link>
            <div>
              <h2 className="font-semibold text-xl text-gray-800 leading-tight">Frequência</h2>
              <p className="text-sm text-gray-600">{classInfo?.school || selectedSchool?.outDescNomeAbrevEscola || 'Carregando...'}</p>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            <input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              className="border rounded-md px-3 py-2 text-sm"
            />
            <span className={`text-sm px-3 py-1 rounded-full ${editable ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
              {editable ? 'Editável (hoje)' : 'Bloqueado (dia passado)'}
            </span>
            {/* Botão Salvar movido para acima da tabela */}
          </div>
        </div>
      }
    >
      <Head title={`Frequência ${classCode}`} />

      <div className="py-8">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6">
              {loading ? (
                <div className="text-gray-600">Carregando...</div>
              ) : error ? (
                <div className="text-red-600">{error}</div>
              ) : (
                <div>
                  <div className="mb-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <h3 className="text-lg font-semibold">{classInfo?.name || 'Turma'}</h3>
                        {/* <p className="text-sm text-gray-600">Turno: {classInfo?.shift} • Sala: {classInfo?.room}</p> */}
                      </div>
                      {editable && (
                        <button
                          onClick={saveAttendance}
                          disabled={saving}
                          className="inline-flex items-center px-5 py-2 text-base bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                          title="Salvar frequência"
                        >
                          Salvar frequência
                        </button>
                      )}
                    </div>
                  </div>

                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nº</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RA</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div className="flex items-center justify-between">
                              <span>Status</span>
                              {editable && (
                                <div className="inline-flex items-center space-x-1">
                                  <button
                                    onClick={markAllPresent}
                                    className="inline-flex items-center px-2 py-1 text-xs rounded-md border bg-white text-gray-700 hover:bg-green-50 border-gray-300"
                                    title="Marcar todos como Presente"
                                  >
                                    P
                                  </button>
                                  <button
                                    onClick={markAllAbsent}
                                    className="inline-flex items-center px-2 py-1 text-xs rounded-md border bg-white text-gray-700 hover:bg-red-50 border-gray-300"
                                    title="Marcar todos como Ausente"
                                  >
                                    A
                                  </button>
                                  <button
                                    onClick={markAllJustified}
                                    className="inline-flex items-center px-2 py-1 text-xs rounded-md border bg-white text-gray-700 hover:bg-blue-50 border-gray-300"
                                    title="Marcar todos como Justificado"
                                  >
                                    <span className="font-semibold">J</span>
                                  </button>
                                </div>
                              )}
                            </div>
                          </th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anotações</th>
                        </tr>
                      </thead>
                      <tbody className="bg-white divide-y divide-gray-200">
                        {students.map((s, idx) => (
                          <tr key={s.ra} className={editable ? '' : 'opacity-70'}>
                            <td className="px-4 py-2 text-sm text-gray-700">{s.number || idx + 1}</td>
                            <td className="px-4 py-2 text-sm text-gray-900">{s.name}</td>
                            <td className="px-4 py-2 text-sm text-gray-700">{s.ra}</td>
                            <td className="px-4 py-2">
                              <div className="inline-flex items-center space-x-2">
                                 <button
                                   type="button"
                                   onClick={() => editable && handleStatusChange(s.ra, 'present')}
                                   className={`inline-flex items-center px-3 py-1.5 text-sm rounded-md border ${s.status === 'present' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-green-50 border-gray-300'} ${!editable ? 'opacity-50 cursor-not-allowed' : ''}`}
                                   disabled={!editable}
                                   title="Presente"
                                 >
                                   P
                                 </button>
                                 <button
                                   type="button"
                                   onClick={() => editable && handleStatusChange(s.ra, 'absent')}
                                   className={`inline-flex items-center px-3 py-1.5 text-sm rounded-md border ${s.status === 'absent' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-red-50 border-gray-300'} ${!editable ? 'opacity-50 cursor-not-allowed' : ''}`}
                                   disabled={!editable}
                                   title="Ausente"
                                 >
                                   A
                                 </button>
                                 <button
                                   type="button"
                                   onClick={() => editable && handleStatusChange(s.ra, 'justified')}
                                   className={`inline-flex items-center px-3 py-1.5 text-sm rounded-md border ${s.status === 'justified' ? 'bg-blue-600 text-white border-blue-600 shadow-sm font-semibold' : 'bg-white text-gray-700 hover:bg-blue-50 border-gray-300'} ${!editable ? 'opacity-50 cursor-not-allowed' : ''}`}
                                   disabled={!editable}
                                   title="Justificado"
                                 >
                                   <span className="font-semibold">J</span>
                                 </button>
                               </div>
                            </td>
                            <td className="px-4 py-2">
                              <input
                                type="text"
                                value={s.note ?? ''}
                                onChange={(e) => handleNoteChange(s.ra, e.target.value)}
                                disabled={!editable}
                                placeholder="Adicionar anotações"
                                className="border rounded-md px-3 py-2 text-sm w-full"
                              />
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}