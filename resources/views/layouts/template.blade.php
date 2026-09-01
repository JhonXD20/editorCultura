@extends('adminlte::page')

<!-- Define um título padrão, mas permite que a view filha o sobrescreva -->
@section('title')
    @yield('page_title', 'Editor Cultura')
@stop

<!-- Cabeçalho padronizado da página -->
@section('content_header')
<div class="d-flex justify-content-between align-items-center custom-header mb-3">
    <div>
        <!-- Os yields permitem que cada tela injete seu próprio título e data -->
        <h1 class="font-serif text-main mb-1">@yield('header_title', 'Visão Geral')</h1>
        <p class="text-muted-custom mb-0 text-lowercase" style="text-transform: none !important;">
            @yield('header_subtitle', 'Bem-vindo ao sistema')
        </p>
    </div>
    <div class="d-flex align-items-center text-green text-muted-custom">
        <span class="mr-2 me-2" style="font-size: 10px;">●</span> SISTEMA OPERACIONAL
    </div>
</div>
@stop

<!-- Área central onde as views filhas vão injetar o conteúdo -->
@section('content')
    @yield('conteudo')
@stop

<!-- Rodapé padronizado -->
@section('footer')
<div class="d-flex justify-content-between align-items-center dashboard-theme w-100" style="font-size: 0.85rem;"">
    <div class="text-muted">
        <strong>Copyright &copy; 2026 <a href="#" class="text-gold text-decoration-none">Editor Cultura</a>.</strong> Todos os direitos reservados.
    </div>
    <div class="text-muted">
        <b>Versão</b> 1.0.0
    </div>
</div>
@stop

<!-- CSS GLOBAL DA APLICAÇÃO -->
@section('css')
<!-- Google Fonts Globais -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">

<style>
    /* ==== VARIÁVEIS GLOBAIS (MODO CLARO) ==== */
    :root {
        --bg-card: #ffffff;
        --color-gold: #cba258;
        --color-green: #2ea043;
        --color-red: #d73a49;
        --color-text-main: #24292e;
        --color-text-muted: #6a737d;
        --border-color: #e1e4e8;
        --progress-bg: #e1e4e8;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    /* ==== VARIÁVEIS GLOBAIS (MODO ESCURO) ==== */
    body.dark-mode {
        --bg-card: #161b22;
        --color-gold: #cba258;
        --color-green: #2ea043;
        --color-red: #f85149;
        --color-text-main: #c9d1d9;
        --color-text-muted: #8b949e;
        --border-color: #30363d;
        --progress-bg: #30363d;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    }

    /* ==== TIPOGRAFIA BASE ==== */
    .dashboard-theme {
        font-family: 'Inter', sans-serif;
        color: var(--color-text-main);
    }

    .font-serif {
        font-family: 'Playfair Display', serif;
    }

    /* ==== UTILITÁRIOS GLOBAIS DE COR ==== */
    .text-main { color: var(--color-text-main) !important; }
    .text-gold { color: var(--color-gold) !important; }
    .text-green { color: var(--color-green) !important; }
    .text-red { color: var(--color-red) !important; }
    .text-muted { color: var(--color-text-muted) !important; }
    .text-muted-custom {
        color: var(--color-text-muted);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* ==== COMPONENTES GLOBAIS (CARDS E TÍTULOS) ==== */
    .dashboard-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.25rem; 
        box-shadow: var(--card-shadow);
        transition: background-color 0.3s, border-color 0.3s;
    }

    .section-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--color-text-muted);
        margin-bottom: 1rem;
        margin-top: 1.5rem;
        font-family: 'Inter', sans-serif;
    }

    /* ==== UTILITÁRIOS DE ROLAGEM (SCROLL) ==== */
    .scrollable-content {
        max-height: 220px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 5px;
    }

    .scrollable-content::-webkit-scrollbar { width: 5px; }
    .scrollable-content::-webkit-scrollbar-track { background: transparent; }
    .scrollable-content::-webkit-scrollbar-thumb {
        background-color: var(--border-color);
        border-radius: 10px;
    }

    /* ==== TRUNCATE DE TEXTOS LONGOS ==== */
    .text-truncate-custom {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    /* ==== AJUSTES ESTRUTURAIS DO ADMINLTE ==== */
    .custom-header h1 {
        font-size: 2rem;
    }

    
</style>

<!-- Ponto de injeção para as views filhas mandarem CSS específico delas -->
@yield('css_custom')
@stop

<!-- JAVASCRIPT GLOBAL -->
@section('js')
<!-- Ponto de injeção para as views filhas mandarem JS específico (como o Chart.js) -->
@yield('js_custom')
@stop