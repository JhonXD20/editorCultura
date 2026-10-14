
document.addEventListener('DOMContentLoaded', () => {

    const viewport = document.getElementById(
        'totemPublicoViewport'
    );

    const palco = document.getElementById(
        'totemStage'
    );

    const canvas = document.getElementById(
        'canvasTotem'
    );

    if (!viewport || !palco || !canvas) {
        return;
    }

    /* =====================================
       1. CONFIGURAÇÃO DO CANVAS
    ===================================== */

    const largura =
        Number(canvas.dataset.refWidth) || 1366;

    const altura =
        Number(canvas.dataset.refHeight) || 768;

    const paginaResponsiva =
        canvas.classList.contains('grapes-published-page') &&
        canvas.dataset.responsiveVersion === '2';
    const paginaResponsivaAnterior =
        !paginaResponsiva &&
        Boolean(canvas.querySelector(
            '[data-cultura-responsive-tablet="1"], [data-cultura-responsive-mobile="1"]'
        ));

    /*
     * O GrapesJS salva o layout-base do totem dentro de
     * `@media (max-width: 1366px)`. Essa media query considera a largura da
     * janela, e nao a largura (1366px) do palco que sera escalado abaixo.
     * Assim, em monitores maiores, o palco aparecia mas seus elementos
     * perdiam as posicoes ate a janela ser reduzida (por exemplo, ao abrir o
     * inspetor do navegador).
     *
     * Reaplicamos as regras do perfil-base somente em telas de desktop. As
     * regras originais de tablet e celular continuam controlando larguras de
     * ate 992px.
     */
    function prepararLayoutBaseResponsivo() {
        if (!paginaResponsiva) {
            return;
        }

        const publishedStyles = document.getElementById(
            'publishedPageStyles'
        );

        let regrasBase = '';

        try {
            const rules = publishedStyles?.sheet?.cssRules || [];

            for (const rule of rules) {
                const limite = rule.conditionText?.match(
                    /^\s*\(?\s*max-width\s*:\s*(\d+(?:\.\d+)?)px\s*\)?\s*$/i
                );

                if (!limite || Number(limite[1]) !== largura) {
                    continue;
                }

                regrasBase += Array.from(rule.cssRules || [])
                    .map((regra) => regra.cssText)
                    .join('\n');
            }
        } catch (error) {
            console.warn(
                'Nao foi possivel preparar o layout-base responsivo:',
                error
            );
            return;
        }

        if (!regrasBase) {
            return;
        }

        const compatibilidade = document.createElement('style');
        compatibilidade.id = 'publishedDesktopCompatibilityStyles';
        compatibilidade.textContent =
            `@media (min-width: 993px) {${regrasBase}}`;
        publishedStyles.after(compatibilidade);
    }

    prepararLayoutBaseResponsivo();

    if (!paginaResponsiva) {
        const publishedStyles = document.getElementById(
            'publishedPageStyles'
        );

        try {
            const rules = publishedStyles?.sheet?.cssRules || [];

            for (let index = rules.length - 1; index >= 0; index -= 1) {
                const rule = rules[index];
                const maxWidth = rule.conditionText?.match(
                    /max-width\s*:\s*(\d+(?:\.\d+)?)px/i
                );

                const menorLarguraCompativel = paginaResponsivaAnterior
                    ? 992
                    : largura;

                if (maxWidth && Number(maxWidth[1]) < menorLarguraCompativel) {
                    publishedStyles.sheet.deleteRule(index);
                }
            }
        } catch (error) {
            console.warn(
                'NÃ£o foi possÃ­vel preparar a compatibilidade responsiva:',
                error
            );
        }
    }

    palco.style.width = `${largura}px`;
    palco.style.height = `${altura}px`;

    function ajustarEscala() {

        const larguraTela = viewport.clientWidth;
        const alturaTela = viewport.clientHeight;

        if (!larguraTela || !alturaTela) {
            return;
        }

        if (
            paginaResponsiva &&
            window.matchMedia('(max-width: 992px)').matches
        ) {
            palco.style.width = `${larguraTela}px`;
            palco.style.height = `${alturaTela}px`;
            palco.style.transformOrigin = 'center center';
            palco.style.transform = 'translate(-50%, -50%)';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            return;
        }

        const usarTabletAnterior =
            paginaResponsivaAnterior &&
            window.matchMedia('(max-width: 992px)').matches;
        const larguraLayout = usarTabletAnterior ? 768 : largura;
        const alturaLayout = usarTabletAnterior ? 1024 : altura;

        palco.style.width = `${larguraLayout}px`;
        palco.style.height = `${alturaLayout}px`;

        const escalaX = larguraTela / larguraLayout;
        const escalaY = alturaTela / alturaLayout;

        // Mantém todo o conteúdo visível em proporções diferentes.
        const escala = Math.min(
            escalaX,
            escalaY
        );

        palco.style.transformOrigin =
            'center center';

        palco.style.transform =
            `translate(-50%, -50%) scale(${escala})`;
    }

    ajustarEscala();

    window.addEventListener(
        'resize',
        ajustarEscala
    );

    /* =====================================
       2. REGISTRO DE INTERAÇÕES
    ===================================== */

    const paginaId = viewport.dataset.paginaId;
    const urlInteracao = viewport.dataset.interacaoUrl;
    const urlPing = viewport.dataset.pingUrl;
    const csrfToken = viewport.dataset.csrfToken;

    function registrarPing() {
        if (!urlPing || !csrfToken) {
            return;
        }

        fetch(urlPing, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {
            // O proximo ciclo tentara novamente sem interromper o totem.
        });
    }

    registrarPing();
    window.setInterval(registrarPing, 60_000);

    function registrarInteracao(
        tipo,
        duracaoSegundos = null
    ) {

        if (
            !paginaId ||
            !urlInteracao ||
            !csrfToken
        ) {
            return;
        }

        const dados = new URLSearchParams();

        dados.append('_token', csrfToken);
        dados.append('pagina_id', paginaId);
        dados.append('tipo', tipo);

        if (duracaoSegundos !== null) {
            dados.append(
                'duracao_segundos',
                String(duracaoSegundos)
            );
        }

        // sendBeacon é adequado para eventos
        // enviados durante uma navegação.
        if (navigator.sendBeacon) {

            const enviado = navigator.sendBeacon(
                urlInteracao,
                dados
            );

            if (enviado) {
                return;
            }
        }

        // Alternativa para navegadores sem sendBeacon
        fetch(urlInteracao, {
            method: 'POST',

            credentials: 'same-origin',

            keepalive: true,

            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },

            body: dados,
        }).catch((erro) => {
            console.warn(
                'Não foi possível registrar a interação:',
                erro
            );
        });
    }

    function registrarToque() {
        registrarInteracao('toque');
    }

    /* =====================================
       3. CAPTURAR TOQUES NO TOTEM
    ===================================== */

    function ouvirPonteiro(elemento) {

        if (!elemento) return;

        elemento.addEventListener(
            'pointerdown',
            (evento) => {

                // Evita registrar múltiplos dedos
                // da mesma interação.
                if (evento.isPrimary === false) {
                    return;
                }

                registrarToque();
            }
        );
    }

    ouvirPonteiro(viewport);

    // Zonas podem estar fora do viewport.
    ouvirPonteiro(
        document.getElementById('touchZoneLeft')
    );

    ouvirPonteiro(
        document.getElementById('touchZoneRight')
    );

    /* =====================================
       4. TEMPO DE PERMANÊNCIA

       Mede o período em que a página está
       visível no navegador.
    ===================================== */

    let visivelDesde = document.hidden
        ? null
        : performance.now();

    function finalizarPeriodoVisivel() {

        if (visivelDesde === null) {
            return;
        }

        const duracao = Math.round(
            (performance.now() - visivelDesde) / 1000
        );

        visivelDesde = null;

        if (duracao < 1) {
            return;
        }

        registrarInteracao(
            'tempo_pagina',
            Math.min(duracao, 86400)
        );
    }

    document.addEventListener(
        'visibilitychange',
        () => {

            if (document.hidden) {
                finalizarPeriodoVisivel();
            } else if (visivelDesde === null) {
                visivelDesde = performance.now();
            }
        }
    );

    window.addEventListener(
        'pagehide',
        finalizarPeriodoVisivel
    );

    /* =====================================
       5. NAVEGAÇÃO ENTRE PÁGINAS
    ===================================== */

    const proximaUrl =
        viewport.dataset.proximaUrl || '';

    const anteriorUrl =
        viewport.dataset.anteriorUrl || '';

    const inicialUrl =
        viewport.dataset.inicialUrl || '';

    const paginaUrlTemplate =
        viewport.dataset.pageUrlTemplate || '';

    function navegar(url) {
        if (url) {
            window.location.assign(url);
        }
    }

    function irDireita() {
        navegar(proximaUrl || inicialUrl);
    }

    function irEsquerda() {
        navegar(anteriorUrl);
    }

    function prepararZona(id, callback) {

        const zona = document.getElementById(id);

        if (!zona) {
            return;
        }

        zona.addEventListener('click', callback);

        zona.addEventListener(
            'keydown',
            (evento) => {

                if (
                    evento.key === 'Enter' ||
                    evento.key === ' '
                ) {
                    evento.preventDefault();

                    registrarToque();
                    callback();
                }
            }
        );
    }

    prepararZona(
        'touchZoneLeft',
        irEsquerda
    );

    prepararZona(
        'touchZoneRight',
        irDireita
    );

    /* =====================================
       6. BOTÕES DO CONTEÚDO
    ===================================== */

    canvas.querySelectorAll(
        '.card-botao-totem[data-url-destino]'
    ).forEach((botao) => {

        const destino =
            botao.dataset.urlDestino;

        if (!destino) {
            return;
        }

        botao.addEventListener('click', () => {
            navegar(destino);
        });

        botao.addEventListener(
            'keydown',
            (evento) => {

                if (
                    evento.key === 'Enter' ||
                    evento.key === ' '
                ) {
                    evento.preventDefault();

                    registrarToque();
                    navegar(destino);
                }
            }
        );
    });

    canvas.querySelectorAll(
        '[data-cultura-navigation="1"][data-page-id]'
    ).forEach((botao) => {
        const paginaDestino = botao.dataset.pageId;

        if (!/^\d+$/.test(paginaDestino || '') || !paginaUrlTemplate) {
            return;
        }

        botao.addEventListener('click', () => {
            navegar(
                paginaUrlTemplate.replace(
                    '__PAGE_ID__',
                    paginaDestino
                )
            );
        });
    });

    /* =====================================
       7. NAVEGAÇÃO POR TECLADO
    ===================================== */

    document.addEventListener(
        'keydown',
        (evento) => {

            if (
                evento.target.closest(
                    'input, textarea, [role="button"], [role="link"]'
                )
            ) {
                return;
            }

            if (evento.key === 'ArrowRight') {

                if (proximaUrl || inicialUrl) {
                    registrarToque();
                    irDireita();
                }
            }

            if (evento.key === 'ArrowLeft') {

                if (anteriorUrl) {
                    registrarToque();
                    irEsquerda();
                }
            }
        }
    );

});
