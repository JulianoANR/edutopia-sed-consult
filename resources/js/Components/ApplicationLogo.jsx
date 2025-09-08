export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 120 120"
            xmlns="http://www.w3.org/2000/svg"
        >
            {/* Círculo de fundo */}
            <circle cx="60" cy="60" r="55" fill="currentColor" opacity="0.1"/>
            
            {/* Prédio escolar - base */}
            <rect x="25" y="65" width="70" height="35" rx="2" fill="currentColor" opacity="0.9"/>
            
            {/* Telhado triangular */}
            <polygon points="20,65 60,35 100,65" fill="currentColor"/>
            
            {/* Porta principal */}
            <rect x="52" y="80" width="16" height="20" rx="8" fill="white" opacity="0.9"/>
            <circle cx="60" cy="90" r="1" fill="currentColor" opacity="0.6"/>
            
            {/* Janelas */}
            <rect x="35" y="75" width="8" height="8" rx="1" fill="white" opacity="0.8"/>
            <rect x="77" y="75" width="8" height="8" rx="1" fill="white" opacity="0.8"/>
            
            {/* Livro aberto no topo */}
            <rect x="50" y="20" width="20" height="15" rx="1" fill="currentColor"/>
            <path d="M50 27.5 L60 25 L70 27.5 L70 35 L60 32.5 L50 35 Z" fill="white" opacity="0.9"/>
            
            {/* Linhas do livro */}
            <line x1="52" y1="30" x2="58" y2="29" stroke="currentColor" strokeWidth="0.5" opacity="0.4"/>
            <line x1="62" y1="29" x2="68" y2="30" stroke="currentColor" strokeWidth="0.5" opacity="0.4"/>
            <line x1="52" y1="32" x2="58" y2="31" stroke="currentColor" strokeWidth="0.5" opacity="0.4"/>
            <line x1="62" y1="31" x2="68" y2="32" stroke="currentColor" strokeWidth="0.5" opacity="0.4"/>
            
            {/* Estrela de excelência */}
            <polygon points="60,10 62,16 68,16 63,20 65,26 60,23 55,26 57,20 52,16 58,16" fill="currentColor" opacity="0.7"/>
        </svg>
    );
}
