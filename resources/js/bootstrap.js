import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Garantir que o Laravel retorne respostas de validação em JSON
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Usar proteção CSRF baseada em cookie (recomendada para SPAs com Inertia)
// O axios lerá o cookie `XSRF-TOKEN` e o enviará no header `X-XSRF-TOKEN`
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Não fixar o header 'X-CSRF-TOKEN' a partir da meta tag.
// Isso evita inconsistência quando o token é regenerado sem reload completo.

// Interceptor de resposta: manter padrão, sem reload automático em 419
window.axios.interceptors.response.use(
    function (response) {
        return response;
    },
    function (error) {
        return Promise.reject(error);
    }
);
