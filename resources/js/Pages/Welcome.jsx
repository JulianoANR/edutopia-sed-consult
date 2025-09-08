import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Sistema Educacional Municipal" />
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
                {/* Header */}
                <header className="bg-white shadow-sm border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-4">
                            <div className="flex items-center space-x-4">
                                <ApplicationLogo className="h-10 w-10 text-indigo-600" />
                                <div>
                                    <h1 className="text-xl font-bold text-gray-900">Sistema Educacional</h1>
                                    <p className="text-sm text-gray-600">Gestão Municipal de Ensino</p>
                                </div>
                            </div>
                            <nav className="flex items-center space-x-4">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        Acessar Sistema
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        Entrar
                                    </Link>
                                )}
                            </nav>
                        </div>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative py-20 lg:py-32">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center">
                            <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                                Sistema de Gestão
                                <span className="block text-indigo-600">Educacional Municipal</span>
                            </h1>
                            <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                                Plataforma integrada ao Sistema Estadual de Educação (SED) que permite aos municípios 
                                gerenciar e controlar seus dados educacionais de forma prática e eficiente.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                {!auth.user && (
                                    <Link
                                        href={route('login')}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-colors shadow-lg"
                                    >
                                        Acessar Sistema
                                    </Link>
                                )}
                                {auth.user && (
                                    <Link
                                        href={route('dashboard')}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-colors shadow-lg"
                                    >
                                        Ir para Dashboard
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section className="py-20 bg-white">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                Integração e Controle Municipal
                            </h2>
                            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
                                Conecte-se ao SED e gerencie dados educacionais municipais com simplicidade
                            </p>
                        </div>
                        
                        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {/* Feature 1 */}
                            <div className="bg-gradient-to-br from-blue-50 to-indigo-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Gestão de Escolas</h3>
                                <p className="text-gray-600">
                                    Cadastre e gerencie todas as escolas municipais em um só lugar, 
                                    com informações completas e atualizadas.
                                </p>
                            </div>

                            {/* Feature 2 */}
                            <div className="bg-gradient-to-br from-green-50 to-emerald-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-emerald-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Controle de Turmas</h3>
                                <p className="text-gray-600">
                                    Organize turmas por série, período e modalidade, 
                                    facilitando o acompanhamento pedagógico.
                                </p>
                            </div>

                            {/* Feature 3 */}
                            <div className="bg-gradient-to-br from-purple-50 to-violet-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-violet-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Gestão de Alunos</h3>
                                <p className="text-gray-600">
                                    Mantenha registros completos dos alunos, 
                                    incluindo dados pessoais e histórico escolar.
                                </p>
                            </div>

                            {/* Feature 4 */}
                            <div className="bg-gradient-to-br from-orange-50 to-amber-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-amber-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Relatórios Detalhados</h3>
                                <p className="text-gray-600">
                                    Gere relatórios completos sobre matrículas, frequência 
                                    e desempenho dos alunos.
                                </p>
                            </div>

                            {/* Feature 5 */}
                            <div className="bg-gradient-to-br from-red-50 to-rose-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-rose-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Segurança de Dados</h3>
                                <p className="text-gray-600">
                                    Sistema seguro com controle de acesso e 
                                    proteção de dados sensíveis dos estudantes.
                                </p>
                            </div>

                            {/* Feature 6 */}
                            <div className="bg-gradient-to-br from-teal-50 to-cyan-100 p-8 rounded-2xl">
                                <div className="w-12 h-12 bg-cyan-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                </div>
                                <h3 className="text-xl font-semibold text-gray-900 mb-3">Exportação de Dados</h3>
                                <p className="text-gray-600">
                                    Exporte dados em diversos formatos para 
                                    integração com outros sistemas educacionais.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Stats Section */}
                <section className="py-20 bg-indigo-600">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
                                Sistema Confiável e Eficiente
                            </h2>
                            <p className="text-xl text-indigo-100 max-w-2xl mx-auto">
                                Desenvolvido especificamente para atender às necessidades da educação municipal
                            </p>
                        </div>
                        
                        <div className="grid md:grid-cols-4 gap-8">
                            <div className="text-center">
                                <div className="text-4xl md:text-5xl font-bold text-white mb-2">150+</div>
                                <div className="text-indigo-100">Municípios Conectados</div>
                            </div>
                            <div className="text-center">
                                <div className="text-4xl md:text-5xl font-bold text-white mb-2">24/7</div>
                                <div className="text-indigo-100">Sincronização SED</div>
                            </div>
                            <div className="text-center">
                                <div className="text-4xl md:text-5xl font-bold text-white mb-2">100%</div>
                                <div className="text-indigo-100">Dados Seguros</div>
                            </div>
                            <div className="text-center">
                                <div className="text-4xl md:text-5xl font-bold text-white mb-2">95%</div>
                                <div className="text-indigo-100">Eficiência Operacional</div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center">
                            <div className="flex items-center justify-center space-x-4 mb-6">
                                <ApplicationLogo className="h-8 w-8 text-indigo-400" />
                                <span className="text-xl font-bold">Sistema Educacional Municipal</span>
                            </div>
                            <p className="text-gray-400 mb-4">
                                Desenvolvido para facilitar a gestão educacional municipal
                            </p>
                            <div className="text-sm text-gray-500">
                                Laravel v{laravelVersion} • PHP v{phpVersion}
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
