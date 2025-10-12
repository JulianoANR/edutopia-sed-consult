import React, { useState } from 'react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { Head, usePage, router } from '@inertiajs/react';
import InputLabel from '../../Components/InputLabel';
import TextInput from '../../Components/TextInput';
import InputError from '../../Components/InputError';
import PrimaryButton from '../../Components/PrimaryButton';
import SecondaryButton from '../../Components/SecondaryButton';
import DangerButton from '../../Components/DangerButton';

export default function Index({ tenants = [] }) {
  const { auth } = usePage().props;
  const userRole = auth?.user?.role;

  // Apenas super_admin deve ver esta página
  if (userRole !== 'super_admin') {
    return (
      <AuthenticatedLayout>
        <Head title="Integrações Tenants" />
        <div className="py-12">
          <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
              <p className="text-red-600">Acesso negado.</p>
            </div>
          </div>
        </div>
      </AuthenticatedLayout>
    );
  }

  // Form state
  const [form, setForm] = useState({
    name: '',
    diretoria_id: '',
    municipio_id: '',
    rede_ensino_cod: '',
    sed_username: '',
    sed_password: '',
    status: 'active',
  });
  const [errors, setErrors] = useState({});

  const [editing, setEditing] = useState(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    const payload = { ...form };

    if (editing) {
      router.put(route('tenants.update', editing.id), payload, {
        onError: (errs) => setErrors(errs),
        onSuccess: () => {
          setEditing(null);
          setForm({
            name: '', diretoria_id: '', municipio_id: '', rede_ensino_cod: '',
            sed_username: '', sed_password: '', status: 'active',
          });
        }
      });
    } else {
      router.post(route('tenants.store'), payload, {
        onError: (errs) => setErrors(errs),
        onSuccess: () => {
          setForm({
            name: '', diretoria_id: '', municipio_id: '', rede_ensino_cod: '',
            sed_username: '', sed_password: '', status: 'active',
          });
        }
      });
    }
  };

  const handleEdit = (tenant) => {
    setEditing(tenant);
    setForm({
      name: tenant.name,
      diretoria_id: tenant.diretoria_id,
      municipio_id: tenant.municipio_id,
      rede_ensino_cod: tenant.rede_ensino_cod,
      sed_username: tenant.sed_username,
      sed_password: '', // não carregamos a senha
      status: tenant.status,
    });
  };

  const handleDelete = (tenant) => {
    if (!confirm(`Excluir tenant "${tenant.name}"?`)) return;
    router.delete(route('tenants.destroy', tenant.id));
  };

  return (
    <AuthenticatedLayout>
      <Head title="Integrações Tenants" />
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 className="text-xl font-semibold mb-4">Integrações por Município/Diretoria</h2>

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4 mb-8">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <InputLabel htmlFor="name" value="Nome" />
                  <TextInput id="name" name="name" value={form.name} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.name} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="diretoria_id" value="Diretoria ID" />
                  <TextInput id="diretoria_id" name="diretoria_id" value={form.diretoria_id} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.diretoria_id} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="municipio_id" value="Município ID" />
                  <TextInput id="municipio_id" name="municipio_id" value={form.municipio_id} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.municipio_id} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="rede_ensino_cod" value="Código da Rede de Ensino" />
                  <TextInput id="rede_ensino_cod" name="rede_ensino_cod" value={form.rede_ensino_cod} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.rede_ensino_cod} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="sed_username" value="Usuário SED" />
                  <TextInput id="sed_username" name="sed_username" value={form.sed_username} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.sed_username} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="sed_password" value="Senha SED" />
                  <TextInput id="sed_password" type="password" name="sed_password" value={form.sed_password} onChange={handleChange} className="mt-1 block w-full" />
                  <InputError message={errors.sed_password} className="mt-2" />
                </div>
                <div>
                  <InputLabel htmlFor="status" value="Status" />
                  <select id="status" name="status" value={form.status} onChange={handleChange} className="mt-1 block w-full border-gray-300 rounded-md">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                  </select>
                  <InputError message={errors.status} className="mt-2" />
                </div>
              </div>

              <div className="flex items-center gap-2">
                <PrimaryButton type="submit">{editing ? 'Atualizar' : 'Criar'}</PrimaryButton>
                {editing && (
                  <SecondaryButton type="button" onClick={() => { setEditing(null); setForm({ name: '', diretoria_id: '', municipio_id: '', rede_ensino_cod: '', sed_username: '', sed_password: '', status: 'active' }); }}>Cancelar edição</SecondaryButton>
                )}
              </div>
            </form>

            {/* List */}
            <div>
              <h3 className="text-lg font-semibold mb-2">Tenants cadastrados</h3>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diretoria</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Município</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rede Ensino</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário SED</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Senha?</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {tenants.map((t) => (
                      <tr key={t.id}>
                        <td className="px-4 py-2">{t.name}</td>
                        <td className="px-4 py-2">{t.diretoria_id}</td>
                        <td className="px-4 py-2">{t.municipio_id}</td>
                        <td className="px-4 py-2">{t.rede_ensino_cod}</td>
                        <td className="px-4 py-2">{t.sed_username}</td>
                        <td className="px-4 py-2">{t.status}</td>
                        <td className="px-4 py-2">{t.has_password ? 'Sim' : 'Não'}</td>
                        <td className="px-4 py-2 flex gap-2">
                          <SecondaryButton onClick={() => handleEdit(t)}>Editar</SecondaryButton>
                          <DangerButton onClick={() => handleDelete(t)}>Excluir</DangerButton>
                        </td>
                      </tr>
                    ))}
                    {tenants.length === 0 && (
                      <tr>
                        <td colSpan="8" className="px-4 py-4 text-center text-gray-500">Nenhum tenant cadastrado.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}