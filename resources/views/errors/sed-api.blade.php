<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro na integração com SED</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif; background: #f8fafc; color: #0f172a; }
        .container { max-width: 920px; margin: 40px auto; padding: 0 16px; }
        .card { background: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(2,6,23,.08), 0 1px 3px rgba(2,6,23,.12); padding: 24px; }
        h1 { font-size: 24px; margin: 0 0 12px; letter-spacing: .2px; }
        p.lead { margin: 0 0 20px; font-size: 16px; color: #334155; }
        .meta { font-size: 14px; color: #475569; margin-bottom: 16px; }
        pre { background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; overflow: auto; font-size: 13px; line-height: 1.5; }
        .actions { display: flex; gap: 12px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .btn-primary { background: #0ea5e9; color: #042f2e; }
        .btn-secondary { background: #e2e8f0; color: #0f172a; }
        .btn:hover { filter: brightness(0.95); }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1220; color: #e2e8f0; }
            .card { background: #0f172a; box-shadow: 0 10px 25px rgba(0,0,0,.35), 0 1px 3px rgba(0,0,0,.45); }
            p.lead { color: #cbd5e1; }
            .meta { color: #94a3b8; }
            .btn-secondary { background: #1f2937; color: #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Erro na integração com SED</h1>
            <p class="lead">{{ $message }}</p>
            <div class="meta">Status HTTP: {{ $statusCode }}</div>

            @if(!empty($context))
                <h2 style="font-size:18px; margin: 18px 0 8px;">Detalhes</h2>
                <pre>{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif

            <div class="actions">
                <button class="btn btn-secondary" type="button" onclick="history.back()">Voltar</button>
                <a class="btn btn-primary" href="/">Ir para a Home</a>
            </div>
        </div>
    </div>
</body>
</html>