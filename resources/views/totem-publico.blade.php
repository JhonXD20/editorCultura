<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#20252a">

    <title>
        {{ $pagina->pagina_titulo ?? 'Totem Interativo' }} | TotemSync
    </title>

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    @vite([
        'resources/css/totem.css',
        'resources/css/publico.css',
        'resources/js/publico.js'
    ])

</head>

<body>

    <main
        class="totem-publico-viewport" id="totemPublicoViewport"
        data-pagina-id="{{ $pagina->id }}"
        data-orientacao="{{ $orientacao }}"
        data-inatividade-segundos="{{ $inatividadeSegundos }}"
        data-pagina-inicial-id="{{ $paginaInicialId }}"
        data-interacao-url="{{ route('totem.interacoes.store') }}"
        data-ping-url="{{ route('totem.ping') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-page-url-template="{{ url('/totem/exibir/__PAGE_ID__') }}"
        data-proxima-url="{{ !empty($proximaPaginaId) ? route('totem.exibir', $proximaPaginaId) : '' }}"
        data-anterior-url="{{ !empty($paginaAnteriorId) ? route('totem.exibir', $paginaAnteriorId) : '' }}"
        data-inicial-url="{{ !empty($paginaInicialId) ? route('totem.exibir', $paginaInicialId) : '' }}">

    <div class="totem-display-area" id="totemDisplayArea">
    <div id="totemStage">

        @if (filled($pagina->published_html))
            @php
                $responsiveVersion = str_contains(
                    $pagina->published_html,
                    'data-cultura-responsive-version="2"'
                ) ? '2' : 'legacy';
            @endphp
            <style id="publishedPageStyles">{!! $pagina->published_css !!}</style>
            <div id="canvasTotem" class="totem-canvas grapes-published-page"
                 data-pagina-id="{{ $pagina->id }}"
                 data-responsive-version="{{ $responsiveVersion }}"
                 data-ref-width="1366"
                 data-ref-height="768">
                {!! $pagina->published_html !!}
            </div>
        @else
            @include('partials.canvas', [
                'pagina' => $pagina,
                'editavel' => false
            ])
        @endif

    </div>
    </div>

    <nav class="totem-navigation" aria-label="Navegação do TotemSync">
        <span class="totem-player-brand" aria-hidden="true">Totem<span>Sync</span></span>
        <div class="totem-navigation-links">
            @if ($paginaAnteriorId)
                <a class="totem-nav-button" href="{{ route('totem.exibir', $paginaAnteriorId) }}" rel="prev">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i><span>Voltar</span>
                </a>
            @else
                <span class="totem-nav-button is-disabled" aria-disabled="true"><i class="fas fa-arrow-left" aria-hidden="true"></i><span>Voltar</span></span>
            @endif

            @if ($paginaInicialId && $paginaInicialId !== $pagina->id)
                <a class="totem-nav-button totem-nav-home" href="{{ route('totem.exibir', $paginaInicialId) }}">
                    <i class="fas fa-home" aria-hidden="true"></i><span>Início</span>
                </a>
            @else
                <span class="totem-nav-button totem-nav-home is-disabled" aria-disabled="true"><i class="fas fa-home" aria-hidden="true"></i><span>Início</span></span>
            @endif

            @if ($proximaPaginaId)
                <a class="totem-nav-button" href="{{ route('totem.exibir', $proximaPaginaId) }}" rel="next">
                    <span>Próximo</span><i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="totem-nav-button is-disabled" aria-disabled="true"><span>Próximo</span><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            @endif
        </div>
        <button type="button" class="totem-nav-button totem-fullscreen" id="totemFullscreen" aria-label="Entrar em tela cheia" hidden>
            <i class="fas fa-expand" aria-hidden="true"></i>
        </button>
    </nav>

    <p class="totem-orientation-notice" id="totemOrientationNotice" role="status" hidden></p>

    </main>

</body>

</html>
