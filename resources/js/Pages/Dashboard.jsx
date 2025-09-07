import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function Dashboard() {
    // Redireciona automaticamente para a listagem de escolas
    useEffect(() => {
        router.visit('/schools');
    }, []);



    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    🎓 Sistema Educacional Municipal
                </h2>
            }
        >
            <Head title="Dashboard - Sistema Educacional" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="text-center">
                                <div className="text-6xl mb-4">🏫</div>
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    Redirecionando para o Sistema Educacional...
                                </h3>
                                <p className="text-gray-600 mb-6">
                                    Você será redirecionado automaticamente para a listagem de escolas.
                                </p>
                                <Link
                                    href="/schools"
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Ir para Escolas
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
