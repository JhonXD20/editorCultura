<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>
        {{ $pagina->pagina_titulo ?? 'Totem Interativo' }}
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
        data-interacao-url="{{ route('totem.interacoes.store') }}"
        data-ping-url="{{ route('totem.ping') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-page-url-template="{{ url('/totem/exibir/__PAGE_ID__') }}"
        data-proxima-url="{{ !empty($proximaPaginaId) ? route('totem.exibir', $proximaPaginaId) : '' }}"
        data-anterior-url="{{ !empty($paginaAnteriorId) ? route('totem.exibir', $paginaAnteriorId) : '' }}"
        data-inicial-url="{{ !empty($paginaInicialId) ? route('totem.exibir', $paginaInicialId) : '' }}">

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

    <div class="touch-zone-left" id="touchZoneLeft" role="button" tabindex="0" aria-label="Página anterior"></div>

    <div class="touch-zone-right" id="touchZoneRight" role="button" tabindex="0" aria-label="Próxima página"></div>

    </main>

</body>

</html>
