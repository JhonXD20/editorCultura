
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >

    <title>
        {{ $pagina->pagina_titulo ?? 'Totem Interativo' }}
    </title>

    @vite([
        'resources/css/totem.css',
        'resources/css/publico.css',
        'resources/js/publico.js'
    ])

</head>

<body>

    <main
        class="totem-publico-viewport"
        id="totemPublicoViewport"

        data-proxima-url="{{ !empty($proximaPaginaId) ? route('totem.exibir', $proximaPaginaId) : '' }}"

        data-anterior-url="{{ !empty($paginaAnteriorId) ? route('totem.exibir', $paginaAnteriorId) : '' }}"

        data-inicial-url="{{ url('/') }}"
    >

        <div id="totemStage">

            @include('partials.canvas', [
                'pagina' => $pagina,
                'editavel' => false
            ])

        </div>

        <div
            class="touch-zone-left"
            id="touchZoneLeft"
            role="button"
            tabindex="0"
            aria-label="Página anterior"
        ></div>

        <div
            class="touch-zone-right"
            id="touchZoneRight"
            role="button"
            tabindex="0"
            aria-label="Próxima página"
        ></div>

    </main>

</body>
</html>
