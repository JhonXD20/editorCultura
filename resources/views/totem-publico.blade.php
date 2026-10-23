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

    @php
        $totemPageUrl = fn ($id) => $preview
            ? route('gestao-conteudo.preview', $id)
            : route('totem.exibir', $id);
    @endphp

    <main
        class="totem-publico-viewport" id="totemPublicoViewport"
        data-pagina-id="{{ $pagina->id }}"
        data-device-id="{{ request()->attributes->get('device_credential')?->id }}"
        data-orientacao="{{ $orientacao }}"
        data-inatividade-segundos="{{ $inatividadeSegundos }}"
        data-pagina-inicial-id="{{ $paginaInicialId }}"
        data-preview="{{ $preview ? '1' : '0' }}"
        data-section-navigation="{{ $navegacaoSecao ? '1' : '0' }}"
        data-section-entries="{{ $entradasSecoes->toJson() }}"
        data-manifest-version="{{ $manifestVersion }}"
        data-manifest-url="{{ $preview ? '' : route('device.manifest') }}"
        data-interacao-url="{{ $preview ? '' : route('device.telemetry') }}"
        data-ping-url="{{ $preview ? '' : route('device.heartbeat') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-page-url-template="{{ $preview ? url('/gestao-conteudo/__PAGE_ID__/visualizar') : url('/totem/exibir/__PAGE_ID__') }}"
        data-proxima-url="{{ !empty($proximaPaginaId) ? $totemPageUrl($proximaPaginaId) : '' }}"
        data-anterior-url="{{ !empty($paginaAnteriorId) ? $totemPageUrl($paginaAnteriorId) : '' }}"
        data-inicial-url="{{ !empty($paginaInicialId) ? $totemPageUrl($paginaInicialId) : '' }}">

    <div class="totem-display-area" id="totemDisplayArea">
    <div id="totemStage">

        @if (filled($pagina->pagina_html_publicado))
            @php
                $responsiveVersion = str_contains(
                    $pagina->pagina_html_publicado,
                    'data-cultura-responsive-version="2"'
                ) ? '2' : 'legacy';
            @endphp
            <style id="publishedPageStyles">{!! $pagina->pagina_css_publicado !!}</style>
            <div id="canvasTotem" class="totem-canvas grapes-published-page"
                 data-pagina-id="{{ $pagina->id }}"
                 data-responsive-version="{{ $responsiveVersion }}"
                 data-ref-width="1366"
                 data-ref-height="768">
                {!! $pagina->pagina_html_publicado !!}
            </div>
        @else
            @include('partials.canvas', [
                'pagina' => $pagina,
                'editavel' => false
            ])
        @endif

    </div>
    </div>

    <nav class="totem-navigation {{ $navegacaoSecao ? 'is-section-navigation' : '' }}" aria-label="Navegação do TotemSync">
        <span class="totem-player-brand" aria-hidden="true">Totem<span>Sync</span></span>
        <div class="totem-navigation-links">
            @if ($paginaAnteriorId)
                <a class="totem-nav-button totem-sequence-control" href="{{ $totemPageUrl($paginaAnteriorId) }}" rel="prev">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i><span>Voltar</span>
                </a>
            @else
                <span class="totem-nav-button totem-sequence-control is-disabled" aria-disabled="true"><i class="fas fa-arrow-left" aria-hidden="true"></i><span>Voltar</span></span>
            @endif

            @if ($paginaInicialId && $paginaInicialId !== $pagina->id)
                <a class="totem-nav-button totem-nav-home" href="{{ $totemPageUrl($paginaInicialId) }}">
                    <i class="fas fa-home" aria-hidden="true"></i><span>Início</span>
                </a>
            @else
                <span class="totem-nav-button totem-nav-home is-disabled" aria-disabled="true"><i class="fas fa-home" aria-hidden="true"></i><span>Início</span></span>
            @endif

            @if ($proximaPaginaId)
                <a class="totem-nav-button totem-sequence-control" href="{{ $totemPageUrl($proximaPaginaId) }}" rel="next">
                    <span>Próximo</span><i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="totem-nav-button totem-sequence-control is-disabled" aria-disabled="true"><span>Próximo</span><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            @endif
        </div>
        <button type="button" class="totem-nav-button totem-fullscreen" id="totemFullscreen" aria-label="Entrar em tela cheia" hidden>
            <i class="fas fa-expand" aria-hidden="true"></i>
        </button>
    </nav>
    @if(!$preview && request()->attributes->get('totem')?->questionario_ativo)
        @include('partials.visitor-questionnaire')
    @endif
    @if ($navegacaoSecao)
        <p class="totem-touch-hint" id="sectionTouchHint" role="status">Toque à esquerda para voltar ou à direita para avançar.</p>
    @endif

    <p class="totem-orientation-notice" id="totemOrientationNotice" role="status" hidden></p>
    @unless($preview)<small id="playerSyncStatus" role="status" style="position:fixed;bottom:2px;left:8px;font:11px system-ui;z-index:10000;color:#666">Preparando sincronização…</small>@endunless

    </main>

</body>

</html>
