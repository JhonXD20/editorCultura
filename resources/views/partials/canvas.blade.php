@php
    $editavel = $editavel ?? false;
    $conteudos = collect($pagina->conteudos ?? []);

    $lerDados = static function ($bloco): array {
        if (!$bloco)
            return [];

        $raw = $bloco->conteudo_dados_conteudo;

        if (is_array($raw))
            return $raw;

        if (is_object($raw))
            return (array) $raw;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    };

    $num = static fn($v, $padrao = 0) =>
        is_numeric($v) ? (float) $v : (float) $padrao;

    $blocoFundo = $conteudos->firstWhere(
        'conteudo_tipo_componente',
        'fundo'
    );

    $blocoLogo = $conteudos->firstWhere(
        'conteudo_tipo_componente',
        'logo'
    );

    $dadosFundo = $lerDados($blocoFundo);
    $dadosLogo = $lerDados($blocoLogo);

    $fundoTipo = $dadosFundo['tipo_fundo'] ?? 'css';

    $fundoValor = $dadosFundo['valor_fundo']
        ?? 'linear-gradient(135deg, #ff7a00, #ffc107, #8bc34a, #03a9f4)';

    $larguraCanvas = max(
        1,
        (int) $num(
            $dadosFundo['largura_canvas'] ?? null,
            1366
        )
    );

    $alturaCanvas = max(
        1,
        (int) $num(
            $dadosFundo['altura_canvas'] ?? null,
            768
        )
    );

    $fundoX = $num($dadosFundo['pos_x'] ?? null);
    $fundoY = $num($dadosFundo['pos_y'] ?? null);
    $fundoW = $num($dadosFundo['largura'] ?? null);
    $fundoH = $num($dadosFundo['altura'] ?? null);

    $fundoWcss = $fundoW > 0 ? $fundoW . 'px' : '100%';
    $fundoHcss = $fundoH > 0 ? $fundoH . 'px' : '100%';

    $outrosBlocos = $conteudos->filter(
        fn($item) => !in_array(
            $item->conteudo_tipo_componente,
            ['logo', 'fundo'],
            true
        )
    );

    $contadorBtn = 0;
@endphp

<div id="canvasTotem" class="totem-canvas" data-pagina-id="{{ $pagina->id }}"
    data-fundo-id="{{ $blocoFundo->id ?? 'novo_fundo' }}" data-fundo-tipo="{{ $fundoTipo }}"
    data-fundo-valor="{{ $fundoValor }}" data-ref-width="{{ $larguraCanvas }}" data-ref-height="{{ $alturaCanvas }}"
    style="
        width: {{ $larguraCanvas }}px;
        height: {{ $alturaCanvas }}px;
        background: {{ $fundoTipo === 'css' ? $fundoValor : '#ffffff' }};
    ">

    {{-- FUNDO DE IMAGEM --}}
    @if ($editavel || $fundoTipo === 'imagem')

        <div class="elemento-livre fundo-imagem-livre {{ $editavel ? 'travado' : '' }}" @if ($editavel)
        id="blocoFundoImagem" @endif data-x="{{ $fundoX }}" data-y="{{ $fundoY }}"
            data-w="{{ $fundoW > 0 ? $fundoW : '' }}" data-h="{{ $fundoH > 0 ? $fundoH : '' }}" style="
                    display: {{ $fundoTipo === 'imagem' ? 'flex' : 'none' }};
                    left: {{ $fundoX }}%;
                    top: {{ $fundoY }}%;
                    width: {{ $fundoWcss }};
                    height: {{ $fundoHcss }};
                ">

            @if ($editavel)
                <div class="resize-handle" title="Redimensionar fundo">
                    <i class="fas fa-expand-arrows-alt"></i>
                </div>
            @endif

            <img src="{{ $fundoTipo === 'imagem' ? asset($fundoValor) : '' }}"
                data-raw-src="{{ $fundoTipo === 'imagem' ? $fundoValor : '' }}" class="img-alvo" draggable="false" alt="">

        </div>

    @endif

    {{-- LOGO --}}
    @if ($blocoLogo)

        @php
            $logoX = $num($dadosLogo['pos_x'] ?? null, 8);
            $logoY = $num($dadosLogo['pos_y'] ?? null, 18);

            $logoW = max(
                1,
                $num($dadosLogo['largura'] ?? null, 240)
            );

            $logoH = $num(
                $dadosLogo['altura'] ?? null,
                0
            );

            $logoHcss = $logoH > 0
                ? $logoH . 'px'
                : 'auto';

            $logoUrl = $dadosLogo['icone']
                ?? 'totem/img/logo-vertical-longa.png';

            $logoTamanhoIcone =
                $dadosLogo['tamanho_icone'] ?? '100%';
        @endphp

        <div class="elemento-livre logo-livre" data-id="{{ $blocoLogo->id }}" data-tipo="logo" data-x="{{ $logoX }}"
            data-y="{{ $logoY }}" data-w="{{ $logoW }}" data-h="{{ $logoH > 0 ? $logoH : '' }}"
            data-tamanho-icone="{{ $logoTamanhoIcone }}" style="
                left: {{ $logoX }}%;
                top: {{ $logoY }}%;
                width: {{ $logoW }}px;
                height: {{ $logoHcss }};
            ">

            @if ($editavel)

                <button type="button" class="btn-delete-element" title="Excluir logo">
                    <i class="fas fa-trash"></i>
                </button>

                <button type="button" class="btn-trocar-img" title="Trocar logo">
                    <i class="fas fa-camera"></i>
                </button>

                <div class="resize-handle">
                    <i class="fas fa-expand-arrows-alt"></i>
                </div>

            @endif

            <img src="{{ asset($logoUrl) }}" data-raw-src="{{ $logoUrl }}" class="img-alvo" draggable="false" alt="Logo">

        </div>

    @endif
    {{-- BOTÕES E TEXTOS --}}
    @foreach ($outrosBlocos as $bloco)

        @php
            $tipo = $bloco->conteudo_tipo_componente;
            $dados = $lerDados($bloco);

            $coluna = $contadorBtn % 3;
            $linha = floor($contadorBtn / 3);

            $defaultX = 38 + $coluna * 19;
            $defaultY = 20 + $linha * 30;

            $posX = $num(
                $dados['pos_x'] ?? null,
                $defaultX
            );

            $posY = $num(
                $dados['pos_y'] ?? null,
                $defaultY
            );
        @endphp

        {{-- BOTÕES --}}
        @if ($tipo === 'botao_navegacao' || $tipo === 'botao')

            @php
                $contadorBtn++;

                $icone = $dados['icone']
                    ?? 'totem/img/botoes/BTN_Historia.png';

                $titulo = $dados['titulo']
                    ?? $dados['texto']
                    ?? 'HISTÓRIA';

                $destinoId = $dados['pagina_destino_id']
                    ?? null;

                $largura = max(
                    1,
                    $num($dados['largura'] ?? null, 260)
                );

                $altura = max(
                    1,
                    $num($dados['altura'] ?? null, 140)
                );

                $tamanhoIcone = max(
                    1,
                    $num($dados['tamanho_icone'] ?? null, 65)
                );

                $formatoIcone =
                    $dados['formato_icone'] ?? '50%';

                $corFundo = $dados['cor_fundo']
                    ?? '#ffffff';

                $urlDestino = $destinoId
                    ? (
                        $editavel
                        ? route('gestao-conteudo.edit', $destinoId)
                        : route('totem.exibir', $destinoId)
                    )
                    : '';
            @endphp

            <div class="card-botao-totem elemento-livre" data-id="{{ $bloco->id }}" data-tipo="{{ $tipo }}"
                data-destino="{{ $destinoId }}" data-url-destino="{{ $urlDestino }}" data-x="{{ $posX }}" data-y="{{ $posY }}"
                data-w="{{ $largura }}" data-h="{{ $altura }}" data-cor-fundo="{{ $corFundo }}"
                data-tamanho-icone="{{ $tamanhoIcone }}" data-formato-icone="{{ $formatoIcone }}" @if (!$editavel && $urlDestino) role="link" tabindex="0" @endif style="
                            left: {{ $posX }}%;
                            top: {{ $posY }}%;
                            width: {{ $largura }}px;
                            height: {{ $altura }}px;
                            background-color: {{ $corFundo }};
                        ">

                @if ($editavel)

                    <button type="button" class="btn-delete-element" title="Excluir botão">
                        <i class="fas fa-trash"></i>
                    </button>

                    <button type="button" class="btn-config-btn" title="Configurar ícone">
                        <i class="fas fa-cog"></i>
                    </button>

                    <button type="button" class="btn-trocar-img" title="Trocar ícone">
                        <i class="fas fa-camera"></i>
                    </button>

                    <div class="resize-handle">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>

                @endif

                <div class="nav-icon">

                    <div class="icon-circle" style="
                                    width: {{ $tamanhoIcone }}px;
                                    height: {{ $tamanhoIcone }}px;
                                    border-radius: {{ $formatoIcone }};
                                ">
                        <img src="{{ asset($icone) }}" data-raw-src="{{ $icone }}" class="img-alvo" draggable="false"
                            alt="Ícone">
                    </div>

                </div>

                <div class="texto-editavel titulo-botao">
                    {!! $titulo !!}
                </div>

            </div>

            {{-- TEXTO E TÍTULO --}}
        @elseif ($tipo === 'texto' || $tipo === 'titulo_pagina')

            @php
                $texto = $dados['texto'] ?? 'Texto Livre';

                $largura = max(
                    1,
                    $num($dados['largura'] ?? null, 350)
                );

                $altura = max(
                    1,
                    $num($dados['altura'] ?? null, 100)
                );

                $corFundo = $dados['cor_fundo']
                    ?? 'rgba(255, 255, 255, 0.85)';

                $corNormalizada = str_replace(
                    ' ',
                    '',
                    strtolower($corFundo)
                );

                $transparente = in_array(
                    $corNormalizada,
                    ['transparent', 'rgba(0,0,0,0)'],
                    true
                );

                $sombra = $transparente
                    ? 'none'
                    : '0 4px 10px rgba(0,0,0,0.15)';
            @endphp

            <div class="elemento-livre bloco-texto-livre" data-id="{{ $bloco->id }}" data-tipo="{{ $tipo }}"
                data-x="{{ $posX }}" data-y="{{ $posY }}" data-w="{{ $largura }}" data-h="{{ $altura }}"
                data-cor-fundo="{{ $corFundo }}" style="
                            left: {{ $posX }}%;
                            top: {{ $posY }}%;
                            width: {{ $largura }}px;
                            height: {{ $altura }}px;
                            background-color: {{ $corFundo }};
                            box-shadow: {{ $sombra }};
                        ">

                @if ($editavel)

                    <button type="button" class="btn-delete-element" title="Excluir texto">
                        <i class="fas fa-trash"></i>
                    </button>

                    <button type="button" class="btn-bg-texto" title="Alterar fundo">
                        <i class="fas fa-fill-drip"></i>
                    </button>

                    <div class="resize-handle">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>

                @endif

                <div class="texto-editavel">
                    {!! $texto !!}
                </div>

            </div>

        @endif

    @endforeach

</div>