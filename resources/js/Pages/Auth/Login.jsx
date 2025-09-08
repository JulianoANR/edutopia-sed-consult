import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
            <Head title="Entrar - Sistema Educacional" />
            
            <div className="flex min-h-screen">
                {/* Lado esquerdo - Informações */}
                <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-700 p-12 flex-col justify-center relative overflow-hidden">
                    {/* Padrão de fundo decorativo */}
                    <div className="absolute inset-0 opacity-10">
                        <div className="absolute top-10 left-10 w-32 h-32 bg-white rounded-full"></div>
                        <div className="absolute top-40 right-20 w-24 h-24 bg-white rounded-full"></div>
                        <div className="absolute bottom-20 left-20 w-40 h-40 bg-white rounded-full"></div>
                        <div className="absolute bottom-40 right-10 w-16 h-16 bg-white rounded-full"></div>
                    </div>
                    
                    <div className="relative z-10">
                        <div className="mb-8">
                            <ApplicationLogo className="h-16 w-16 text-white mb-6" />
                            <h1 className="text-4xl font-bold text-white mb-4">
                                Sistema de Integração SED Municipal
                            </h1>
                            <p className="text-xl text-blue-100 mb-8">
                                Plataforma oficial para municípios gerenciarem e controlarem dados educacionais integrados ao Sistema Estadual de Educação
                            </p>
                        </div>
                        
                        <div className="space-y-4">
                            <div className="flex items-center text-white">
                                <div className="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span>Integração direta com SED</span>
                            </div>
                            <div className="flex items-center text-white">
                                <div className="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span>Controle municipal simplificado</span>
                            </div>
                            <div className="flex items-center text-white">
                                <div className="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span>Sincronização automática</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {/* Lado direito - Formulário */}
                <div className="w-full lg:w-1/2 flex items-center justify-center p-8">
                    <div className="w-full max-w-md">
                        {/* Logo para mobile */}
                        <div className="lg:hidden text-center mb-8">
                            <ApplicationLogo className="h-16 w-16 text-indigo-600 mx-auto mb-4" />
                            <h2 className="text-2xl font-bold text-gray-900">Sistema Educacional</h2>
                        </div>
                        
                        <div className="bg-white rounded-2xl shadow-xl p-8">
                            <div className="text-center mb-8">
                                <h3 className="text-2xl font-bold text-gray-900 mb-2">Acesso ao Sistema SED</h3>
                                <p className="text-gray-600">Entre com suas credenciais para gerenciar os dados educacionais municipais</p>
                            </div>
                            
                            {status && (
                                <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <div className="text-sm font-medium text-green-800">
                                        {status}
                                    </div>
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <InputLabel htmlFor="email" value="E-mail" className="text-gray-700 font-medium" />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="mt-2 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        autoComplete="username"
                                        isFocused={true}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="seu@email.com"
                                    />
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="password" value="Senha" className="text-gray-700 font-medium" />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className="mt-2 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="••••••••"
                                    />
                                    <InputError message={errors.password} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-between">
                                    <label className="flex items-center">
                                        <Checkbox
                                            name="remember"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-600">
                                            Lembrar de mim
                                        </span>
                                    </label>
                                    
                                    {/* {canResetPassword && (
                                        <Link
                                            href={route('password.request')}
                                            className="text-sm text-indigo-600 hover:text-indigo-500 font-medium"
                                        >
                                            Esqueceu a senha?
                                        </Link>
                                    )} */}
                                </div>

                                <PrimaryButton 
                                    className="w-full bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 py-3 text-base font-medium" 
                                    disabled={processing}
                                >
                                    {processing ? 'Entrando...' : 'Entrar'}
                                </PrimaryButton>
                            </form>
                            
                            {/* Botão de registro comentado temporariamente */}
                            {/* <div className="mt-8 text-center">
                                <p className="text-sm text-gray-600">
                                    Não tem uma conta?{' '}
                                    <Link href={route('register')} className="font-medium text-indigo-600 hover:text-indigo-500">
                                        Registre-se aqui
                                    </Link>
                                </p>
                            </div> */}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
