import { createInactivityTimer, useLandscapeLayout } from './totem-player';
import { createPlayerSync } from './player-sync';
import { mountQuestionnaire } from './visitor-questionnaire';
import { createPlayerNavigation, PLAYER_INTERACTIVE_SELECTOR, sectionTapDirection } from './player-navigation';

let unmountPlayer = () => {};

function mountPlayer() {
    const lifecycle = new AbortController();
    const disposers = [];
    const listen = (target, type, handler, options = {}) => target.addEventListener(type, handler, { ...options, signal: lifecycle.signal });
    const repeat = (handler, delay) => { const id = window.setInterval(handler, delay); disposers.push(() => clearInterval(id)); };

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

    const areaExibicao = document.getElementById('totemDisplayArea') || viewport;
    const orientacao = viewport.dataset.orientacao || 'auto';
    const telaHorizontal = window.matchMedia('(orientation: landscape)');
    const layoutHorizontal = () => useLandscapeLayout(orientacao, telaHorizontal.matches);

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
     * Reaplicamos o perfil-base nos tablets horizontais, inclusive abaixo de
     * 992px, e nas telas de desktop. O perfil vertical mantém suas próprias
     * regras responsivas.
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
        compatibilidade.textContent = layoutHorizontal()
            ? regrasBase
            : `@media (min-width: 993px) {${regrasBase}}`;
        document.getElementById(compatibilidade.id)?.remove();
        publishedStyles.after(compatibilidade);
    }

    prepararLayoutBaseResponsivo();

    canvas.querySelectorAll('[data-cultura-carousel="1"]').forEach((carousel) => {
        const slides = [...carousel.querySelectorAll(':scope > [data-cultura-slide="1"]')];
        let selected = 0;
        const show = () => slides.forEach((slide, index) => {
            slide.hidden = index !== selected;
            slide.setAttribute('aria-hidden', String(index !== selected));
        });
        carousel.querySelectorAll('[data-carousel-direction]').forEach((button) => {
            listen(button, 'click', () => {
                if (!slides.length) return;
                selected = (selected + Number(button.dataset.carouselDirection) + slides.length) % slides.length;
                show();
            });
        });
        show();
    });

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

                const menorLarguraCompativel = paginaResponsivaAnterior && !layoutHorizontal()
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

        const larguraTela = areaExibicao.clientWidth;
        const alturaTela = areaExibicao.clientHeight;

        if (!larguraTela || !alturaTela) {
            return;
        }

        if (
            paginaResponsiva &&
            !layoutHorizontal() &&
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
            !layoutHorizontal() &&
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

    listen(window,
        'resize',
        atualizarOrientacao
    );

    if (window.ResizeObserver) {
        const observer = new ResizeObserver(ajustarEscala);
        observer.observe(areaExibicao);
        disposers.push(() => observer.disconnect());
    }

    const avisoOrientacao = document.getElementById('totemOrientationNotice');
    function atualizarOrientacao() {
        prepararLayoutBaseResponsivo();
        ajustarEscala();
        if (!avisoOrientacao) return;
        const divergente = orientacao !== 'auto'
            && (orientacao === 'landscape') !== telaHorizontal.matches;
        avisoOrientacao.hidden = !divergente;
        avisoOrientacao.textContent = orientacao === 'landscape'
            ? 'Gire o tablet para a horizontal para uma melhor experiência.'
            : 'Gire o tablet para a vertical para uma melhor experiência.';
        const hint = document.getElementById('sectionTouchHint');
        if (hint) hint.style.top = divergente ? `${avisoOrientacao.offsetTop + avisoOrientacao.offsetHeight + 8}px` : '';
    }
    listen(telaHorizontal, 'change', atualizarOrientacao);
    atualizarOrientacao();

    /* =====================================
       2. REGISTRO DE INTERAÇÕES
    ===================================== */

    const paginaId = viewport.dataset.paginaId;
    const urlInteracao = viewport.dataset.interacaoUrl;
    const urlPing = viewport.dataset.pingUrl;
    const csrfToken = viewport.dataset.csrfToken;
    const urlManifest = viewport.dataset.manifestUrl;
    const playerStatus = document.getElementById('playerSyncStatus');
    disposers.push(mountQuestionnaire({
        deviceId: viewport.dataset.deviceId, signal: lifecycle.signal,
        onStatus: (message) => {
            if (playerStatus) playerStatus.textContent = message;
            const status = document.getElementById('questionnaireStatus');
            if (status) status.textContent = message;
        },
    }));
    const sincronizarManifesto = createPlayerSync({
        manifestUrl: urlManifest,
        currentVersion: viewport.dataset.manifestVersion,
        onStatus: (message) => { if (!lifecycle.signal.aborted && playerStatus) playerStatus.textContent = message; },
        onUpdate: (manifest) => {
            if (lifecycle.signal.aborted) return;
            const pageExists = manifest.pages.some((page) => String(page.id) === paginaId);
            if (pageExists) window.location.reload();
            else window.location.replace(`/totem/exibir/${manifest.totem.home_page_id}`);
        },
        onRevoked: () => {
            if (lifecycle.signal.aborted) return;
            indexedDB.deleteDatabase('totemsync-player');
            indexedDB.deleteDatabase('totemsync-questionnaire');
            window.location.reload();
        },
    });

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
        }).then((response) => { if (response.status === 401) sincronizarManifesto(); }).catch(() => {
            // O proximo ciclo tentara novamente sem interromper o totem.
        });
    }

    registrarPing();
    repeat(registrarPing, 60_000);

    sincronizarManifesto();
    repeat(sincronizarManifesto, 30_000);
    listen(window, 'online', sincronizarManifesto);
    listen(window, 'focus', sincronizarManifesto);

    const telemetryDb = () => new Promise((resolve, reject) => {
        const open = indexedDB.open('totemsync-player', 1);
        open.onupgradeneeded = () => open.result.createObjectStore('telemetry', { keyPath: 'uuid' });
        open.onsuccess = () => resolve(open.result);
        open.onerror = () => reject(open.error);
    });

    async function enqueueTelemetry(event) {
        const db = await telemetryDb();
        await new Promise((resolve, reject) => {
            const request = db.transaction('telemetry', 'readwrite').objectStore('telemetry').put(event);
            request.onsuccess = resolve;
            request.onerror = () => reject(request.error);
        });
        db.close();
        flushTelemetry();
    }

    let flushingTelemetry = false;
    async function flushTelemetry() {
        if (!urlInteracao || !navigator.onLine || flushingTelemetry) return;
        flushingTelemetry = true;
        try {
            const db = await telemetryDb();
            const events = await new Promise((resolve, reject) => {
                const request = db.transaction('telemetry').objectStore('telemetry').getAll();
                request.onsuccess = () => resolve(request.result.filter((event) => String(event.device_id) === viewport.dataset.deviceId).slice(0, 100));
                request.onerror = () => reject(request.error);
            });
            if (!events.length) { db.close(); return; }
            const response = await fetch(urlInteracao, {
                method: 'POST', credentials: 'same-origin', keepalive: true,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ events }),
            });
            if (!response.ok) { db.close(); return; }
            const result = await response.json();
            const acknowledged = new Set(result.acknowledged);
            const rejected = new Set((result.rejected || []).filter((event) => event.retryable === false).map((event) => event.uuid));
            const transaction = db.transaction('telemetry', 'readwrite');
            events.filter((event) => acknowledged.has(event.uuid) || rejected.has(event.uuid)).forEach((event) => transaction.objectStore('telemetry').delete(event.uuid));
            if (rejected.size && playerStatus) playerStatus.textContent = `${rejected.size} registro(s) não aceito(s): página indisponível ou identificador conflitante.`;
            transaction.oncomplete = () => db.close();
        } catch (_) { /* permanece na fila para o próximo ciclo */ }
        finally { flushingTelemetry = false; }
    }

    function registrarInteracao(tipo, duracaoSegundos = null) {
        if (!paginaId || !urlInteracao) return;
        enqueueTelemetry({
            uuid: crypto.randomUUID(),
            device_id: viewport.dataset.deviceId,
            pagina_id: Number(paginaId),
            tipo,
            duracao_segundos: duracaoSegundos,
            occurred_at: new Date().toISOString(),
        }).catch(() => {});
    }

    listen(window, 'online', flushTelemetry);
    repeat(flushTelemetry, 30_000);
    flushTelemetry();

    function registrarToque() {
        registrarInteracao('toque');
    }

    /* =====================================
       3. CAPTURAR TOQUES NO TOTEM
    ===================================== */

    function ouvirPonteiro(elemento) {

        if (!elemento) return;

        listen(elemento,
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

    listen(document,
        'visibilitychange',
        () => {

            if (document.hidden) {
                finalizarPeriodoVisivel();
            } else if (visivelDesde === null) {
                visivelDesde = performance.now();
            }
        }
    );

    listen(window,
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
            void navigatePlayer(url);
        }
    }

    function irDireita() {
        navegar(proximaUrl);
    }

    function irEsquerda() {
        navegar(anteriorUrl);
    }

    const tempoInativo = Number(viewport.dataset.inatividadeSegundos) || 0;
    if (tempoInativo > 0 && inicialUrl && String(paginaId) !== viewport.dataset.paginaInicialId) {
        const inatividade = createInactivityTimer({
            timeoutMs: tempoInativo * 1000,
            onIdle: () => { void navigatePlayer(inicialUrl); },
        });

        disposers.push(inatividade.stop);

        ['pointerdown', 'keydown', 'wheel', 'scroll', 'input']
            .forEach((evento) => listen(document,
                evento, inatividade.reset, { passive: true, capture: true }
            ));

        listen(document, 'visibilitychange', () => {
            if (document.hidden) inatividade.pause();
            else inatividade.resume();
        });
        if (document.hidden) inatividade.pause();
        listen(window, 'pagehide', inatividade.pause);
        listen(window, 'pageshow', (evento) => {
            if (evento.persisted) inatividade.resume();
        });
    }

    const telaCheia = document.getElementById('totemFullscreen');
    if (telaCheia && document.documentElement.requestFullscreen) {
        telaCheia.hidden = false;
        listen(telaCheia, 'click', async () => {
            try {
                if (document.fullscreenElement) await document.exitFullscreen();
                else await document.documentElement.requestFullscreen();
            } catch (erro) {
                // O navegador pode negar a solicitação; a navegação continua disponível.
            }
        });
        const atualizarTelaCheia = () => {
            telaCheia.setAttribute('aria-label', document.fullscreenElement
                ? 'Sair da tela cheia' : 'Entrar em tela cheia');
            telaCheia.querySelector('i').className = document.fullscreenElement
                ? 'fas fa-compress' : 'fas fa-expand';
        };
        listen(document, 'fullscreenchange', atualizarTelaCheia);
        atualizarTelaCheia();
    }

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

        listen(botao, 'click', () => {
            navegar(destino);
        });

        listen(botao,
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

    const sectionEntries = JSON.parse(viewport.dataset.sectionEntries || '{}');
    const destinationFor = (element) => {
        const section = element.getAttribute('data-section-id');
        const page = section !== null ? sectionEntries[section] : element.dataset.pageId;
        return /^\d+$/.test(String(page || '')) && paginaUrlTemplate
            ? paginaUrlTemplate.replace('__PAGE_ID__', page) : '';
    };
    canvas.querySelectorAll('[data-cultura-navigation="1"]').forEach((element) => {
        if (!element.matches('a, button')) {
            element.tabIndex = 0;
            element.setAttribute('role', 'link');
        }
        if (!destinationFor(element)) element.setAttribute('aria-disabled', 'true');
    });
    listen(canvas, 'click', (event) => {
        const element = event.target.closest('[data-cultura-navigation="1"]');
        if (!element || !canvas.contains(element) || event.defaultPrevented) return;
        // Nested controls, such as a video inside a linked card, keep their behavior.
        const control = event.target.closest('a, button, video, audio, details, summary, [data-carousel-direction]');
        if (control && control !== element && element.contains(control)) return;
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        navegar(destinationFor(element));
    });
    listen(canvas, 'keydown', (event) => {
        const element = event.target.closest('[data-cultura-navigation="1"]');
        if (!element || event.target !== element || element.matches('button') || (element.matches('a') && event.key === 'Enter') || !['Enter', ' '].includes(event.key) || event.repeat) return;
        event.preventDefault();
        registrarToque();
        navegar(destinationFor(element));
    });

    if (viewport.dataset.sectionNavigation === '1') {
        let tap;
        listen(areaExibicao, 'pointerdown', (event) => {
            if (tap || event.isPrimary === false || event.button !== 0) { tap = null; return; }
            tap = { id: event.pointerId, x: event.clientX, y: event.clientY, time: performance.now(), interactive: Boolean(event.target.closest(PLAYER_INTERACTIVE_SELECTOR)) };
        });
        listen(areaExibicao, 'pointercancel', () => { tap = null; });
        listen(areaExibicao, 'pointermove', (event) => {
            if (tap && Math.hypot(event.clientX - tap.x, event.clientY - tap.y) > 12) tap = null;
        });
        listen(areaExibicao, 'scroll', () => { tap = null; }, { capture: true, passive: true });
        listen(areaExibicao, 'pointerup', (event) => {
            const start = tap;
            tap = null;
            if (!start || start.id !== event.pointerId || event.isPrimary === false) return;
            const direction = sectionTapDirection({
                start, end: { x: event.clientX, y: event.clientY }, bounds: areaExibicao.getBoundingClientRect(),
                elapsed: performance.now() - start.time,
                interactive: start.interactive || Boolean(event.target.closest(PLAYER_INTERACTIVE_SELECTOR)),
                selectedText: Boolean(window.getSelection()?.toString()),
            });
            if (direction === 'previous') irEsquerda();
            if (direction === 'next') irDireita();
        });
        const hint = document.getElementById('sectionTouchHint');
        if (hint) {
            const timeout = setTimeout(() => { hint.hidden = true; }, 7000);
            disposers.push(() => clearTimeout(timeout));
        }
    }

    /* =====================================
       7. NAVEGAÇÃO POR TECLADO
    ===================================== */

    listen(document,
        'keydown',
        (evento) => {

            if (document.querySelector('dialog[open]')) return;
            if (
                evento.target.closest(
                    'input, textarea, select, button, a, [contenteditable="true"], [role="button"], [role="link"]'
                )
            ) {
                return;
            }

            if (evento.key === 'ArrowRight') {

                if (proximaUrl) {
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

    unmountPlayer = () => {
        finalizarPeriodoVisivel();
        lifecycle.abort();
        disposers.forEach((dispose) => dispose());
    };
}

const navigatePlayer = createPlayerNavigation({ beforeReplace: () => unmountPlayer(), afterReplace: mountPlayer });
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountPlayer, { once: true });
else mountPlayer();
