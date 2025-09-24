import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar token CSRF
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Interceptor de resposta para capturar erro 419 (CSRF Token Mismatch)
window.axios.interceptors.response.use(
    // Função para respostas bem-sucedidas
    function (response) {
        return response;
    },
    // Função para tratar erros
    function (error) {
        // Verifica se o erro é 419 (CSRF Token Mismatch)
        if (error.response && error.response.status === 419) {
            // CSRF Token Mismatch - recarrega a página silenciosamente
            window.location.reload();
            
            // Retorna uma promise que nunca resolve nem rejeita para evitar que o erro continue se propagando
            return new Promise(() => {}); // Promise que nunca resolve nem rejeita
        }
        
        // Para outros erros, apenas repassa o erro original
        return Promise.reject(error);
    }
);
