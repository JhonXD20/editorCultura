
document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
       1. CONFIGURAÇÕES E ELEMENTOS
    ===================================================== */

    const canvas = document.getElementById('canvasTotem');

    const viewport = document.getElementById(
        'totemEditorViewport'
    );

    const config = window.TotemEditor;

    if (!canvas || !viewport) {
        console.warn('Canvas ou viewport do editor não encontrado.');
        return;
    }

    if (!config || !window.Swal) {
        console.error('TotemEditor ou SweetAlert2 não disponível.');
        return;
    }

    const $id = id => document.getElementById(id);

    const btnToggle = $id('btnToggleEdicao');
    const btnSalvar = $id('btnSalvarCanvas');
    const btnAddTexto = $id('btnAddTextoLivre');
    const btnAddBotao = $id('btnAddBotao');
    const btnTrocarFundo = $id('btnTrocarFundo');
    const btnAjustarFundo = $id('btnAjustarFundo');
    const btnPublicar = $id('btnPublicarTotem');
    const btnUndo = $id('btnUndo');
    const btnRedo = $id('btnRedo');

    const inputImagem = $id('inputUploadImagem');
    const inputFundo = $id('inputUploadFundo');

    if (!btnToggle || !btnSalvar) {
        console.warn('Controles principais não encontrados.');
        return;
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    let modoEdicao = false;

    let imagemAtual = null;
    let textoAtual = null;

    let historico = [];
    let indiceHistorico = -1;

    const inicializados = new WeakSet();

    const larguraBase = Number(
        canvas.dataset.refWidth
    ) || 1366;

    const alturaBase = Number(
        canvas.dataset.refHeight
    ) || 768;
    // Mantém a proporção de referência da página
viewport.style.aspectRatio = `${larguraBase} / ${alturaBase}`;

    let escalaAtual = 1;

    /* =====================================================
       2. FUNÇÕES AUXILIARES
    ===================================================== */

    function aviso(icon, title) {
        Toast.fire({ icon, title });
    }

    function erro(mensagem) {
        Swal.fire({
            title: 'Erro',
            text: mensagem,
            icon: 'error'
        });
    }

    function elementos() {
        return Array.from(canvas.children).filter(
            el => el.classList.contains('elemento-livre')
        );
    }

    function fundo() {
        return $id('blocoFundoImagem');
    }

    function numero(valor, padrao = 0) {
        const n = Number(valor);

        return Number.isFinite(n) ? n : padrao;
    }

    function ehIdPersistido(id) {
        return /^\d+$/.test(String(id || ''));
    }

    /* =====================================================
       3. REGISTROS ORIGINAIS DO BANCO

       Esses IDs são capturados ANTES de qualquer edição.

       Assim podemos identificar quais componentes
       foram removidos pelo usuário.
    ===================================================== */

    const idsOriginais = new Set(
        elementos()
            .filter(el => el.id !== 'blocoFundoImagem')
            .map(el => el.dataset.id)
            .filter(ehIdPersistido)
    );

    function identificarExcluidos() {

        // Elementos que continuam na página
        const idsAtuais = new Set(
            elementos()
                .filter(el => el.id !== 'blocoFundoImagem')
                .map(el => el.dataset.id)
                .filter(ehIdPersistido)
        );

        // Identifica registros que existiam
        // inicialmente e não estão mais no canvas.
        return [...idsOriginais]
            .filter(id => !idsAtuais.has(id))
            .map(Number);
    }

    /* =====================================================
       4. ESCALA DO CANVAS

       O editor utiliza a mesma resolução
       de referência da exibição pública.
    ===================================================== */

    function ajustarEditor() {

        const larguraViewport = viewport.clientWidth;
        const alturaViewport = viewport.clientHeight;

        if (!larguraViewport || !alturaViewport) return;

        escalaAtual = Math.min(
            larguraViewport / larguraBase,
            alturaViewport / alturaBase
        );

        const deslocX = (
            larguraViewport - larguraBase * escalaAtual
        ) / 2;

        const deslocY = (
            alturaViewport - alturaBase * escalaAtual
        ) / 2;

        canvas.style.transformOrigin = 'top left';

        canvas.style.transform = `
            translate(${deslocX}px, ${deslocY}px)
            scale(${escalaAtual})
        `;
    }

    ajustarEditor();

    window.addEventListener('resize', ajustarEditor);

    if (typeof ResizeObserver !== 'undefined') {

        const observer = new ResizeObserver(() => {
            ajustarEditor();
        });

        observer.observe(viewport);
    }

    /* =====================================================
       5. BLOQUEAR / DESBLOQUEAR FUNDO
    ===================================================== */

    function atualizarCadeado() {

        const el = fundo();

        if (!el || !btnAjustarFundo) return;

        const travado = el.classList.contains('travado');

        btnAjustarFundo.innerHTML = travado
            ? '<i class="fas fa-lock"></i>'
            : '<i class="fas fa-lock-open"></i>';

        btnAjustarFundo.classList.toggle(
            'btn-adjust-bg',
            travado
        );

        btnAjustarFundo.classList.toggle(
            'btn-success-bg',
            !travado
        );
    }

    btnAjustarFundo?.addEventListener('click', () => {

        const el = fundo();

        if (!el || el.style.display === 'none') {

            Swal.fire(
                'Atenção',
                'Insira uma imagem de fundo primeiro.',
                'warning'
            );

            return;
        }

        el.classList.toggle('travado');

        atualizarCadeado();

        registrarMudanca();

        aviso(
            'info',
            el.classList.contains('travado')
                ? 'Fundo bloqueado!'
                : 'Fundo desbloqueado!'
        );
    });

    /* =====================================================
       6. INICIALIZAR ELEMENTOS E ARRASTAR
    ===================================================== */

    function initElemento(el) {

        if (inicializados.has(el)) return;

        inicializados.add(el);

        if (!el.style.width && numero(el.dataset.w) > 0) {
            el.style.width = el.dataset.w + 'px';
        }

        if (!el.style.height && numero(el.dataset.h) > 0) {
            el.style.height = el.dataset.h + 'px';
        }

        el.addEventListener('pointerdown', evento => {

            if (!modoEdicao) return;

            if (!evento.isPrimary || evento.button !== 0) {
                return;
            }

            if (el.classList.contains('travado')) {
                return;
            }

            const ignorar = [
                '.texto-editavel',
                '.btn-delete-element',
                '.btn-trocar-img',
                '.btn-config-btn',
                '.btn-bg-texto',
                '.resize-handle'
            ].join(',');

            if (evento.target.closest(ignorar)) {
                return;
            }

            evento.preventDefault();

            const rectCanvas = canvas.getBoundingClientRect();
            const rectEl = el.getBoundingClientRect();

            const shiftX = evento.clientX - rectEl.left;
            const shiftY = evento.clientY - rectEl.top;

            let moveu = false;

            function mover(e) {

                if (e.pointerId !== evento.pointerId) {
                    return;
                }

                const x = (
                    (e.clientX - rectCanvas.left - shiftX)
                    / rectCanvas.width
                ) * 100;

                const y = (
                    (e.clientY - rectCanvas.top - shiftY)
                    / rectCanvas.height
                ) * 100;

                el.style.left = x.toFixed(2) + '%';
                el.style.top = y.toFixed(2) + '%';

                moveu = true;
            }

            function finalizar(e) {

                if (e.pointerId !== evento.pointerId) {
                    return;
                }

                window.removeEventListener(
                    'pointermove',
                    mover
                );

                window.removeEventListener(
                    'pointerup',
                    finalizar
                );

                window.removeEventListener(
                    'pointercancel',
                    finalizar
                );

                if (moveu) {
                    registrarMudanca();
                }
            }

            window.addEventListener('pointermove', mover);
            window.addEventListener('pointerup', finalizar);
            window.addEventListener('pointercancel', finalizar);
        });

        /* Navegação no editor, fora do modo edição */

        el.addEventListener('click', evento => {

            if (modoEdicao) return;

            const destino = el.dataset.urlDestino;

            if (destino) {

                evento.preventDefault();

                window.location.href = destino;
            }
        });
    }

    elementos().forEach(initElemento);

    /* =====================================================
       7. REDIMENSIONAMENTO
    ===================================================== */

    canvas.addEventListener('pointerdown', evento => {

        if (!modoEdicao) return;

        if (!evento.isPrimary || evento.button !== 0) {
            return;
        }

        const handle = evento.target.closest('.resize-handle');

        if (!handle) return;

        const el = handle.closest('.elemento-livre');

        if (!el || el.classList.contains('travado')) {
            return;
        }

        evento.preventDefault();
        evento.stopPropagation();

        const inicioX = evento.clientX;
        const inicioY = evento.clientY;

        const larguraInicial = el.offsetWidth;
        const alturaInicial = el.offsetHeight;

        const escalaInicio = escalaAtual || 1;

        const minW = el.classList.contains('bloco-texto-livre')
            ? 150
            : el.classList.contains('card-botao-totem')
                ? 90
                : 50;

        const minH = el.classList.contains('card-botao-totem')
            ? 90
            : el.classList.contains('bloco-texto-livre')
                ? 60
                : 50;

        let alterou = false;

        function mover(e) {

            if (e.pointerId !== evento.pointerId) {
                return;
            }

            const diffX = (
                e.clientX - inicioX
            ) / escalaInicio;

            const diffY = (
                e.clientY - inicioY
            ) / escalaInicio;

            el.style.width = Math.max(
                minW,
                larguraInicial + diffX
            ) + 'px';

            el.style.height = Math.max(
                minH,
                alturaInicial + diffY
            ) + 'px';

            alterou = true;
        }

        function finalizar(e) {

            if (e.pointerId !== evento.pointerId) {
                return;
            }

            window.removeEventListener('pointermove', mover);
            window.removeEventListener('pointerup', finalizar);
            window.removeEventListener('pointercancel', finalizar);

            if (alterou) {

                el.dataset.w = el.offsetWidth;
                el.dataset.h = el.offsetHeight;

                registrarMudanca();
            }
        }

        window.addEventListener('pointermove', mover);
        window.addEventListener('pointerup', finalizar);
        window.addEventListener('pointercancel', finalizar);
    });

    /* =====================================================
       8. HISTÓRICO - DESFAZER / REFAZER
    ===================================================== */

    function capturarEstado() {

        return JSON.stringify({

            fundoTipo: canvas.dataset.fundoTipo || 'css',

            fundoValor: canvas.dataset.fundoValor || '',

            elementos: elementos().map(
                el => el.outerHTML
            )
        });
    }

    function atualizarHistorico() {

        if (btnUndo) {
            btnUndo.disabled = indiceHistorico <= 0;
        }

        if (btnRedo) {
            btnRedo.disabled =
                indiceHistorico >= historico.length - 1;
        }
    }

    function registrarMudanca() {

        const estado = capturarEstado();

        if (historico[indiceHistorico] === estado) {
            return;
        }

        historico = historico.slice(
            0,
            indiceHistorico + 1
        );

        historico.push(estado);

        if (historico.length > 50) {
            historico.shift();
        }

        indiceHistorico = historico.length - 1;

        atualizarHistorico();
    }

    function restaurarEstado(json) {

        const estado = JSON.parse(json);

        imagemAtual = null;
        textoAtual = null;

        canvas.dataset.fundoTipo = estado.fundoTipo;
        canvas.dataset.fundoValor = estado.fundoValor;

        canvas.style.background =
            estado.fundoTipo === 'css'
                ? estado.fundoValor || '#ffffff'
                : '#ffffff';

        elementos().forEach(el => el.remove());

        estado.elementos.forEach(html => {

            canvas.insertAdjacentHTML(
                'beforeend',
                html
            );

            const el = canvas.lastElementChild;

            if (el?.classList.contains('elemento-livre')) {
                initElemento(el);
            }
        });

        atualizarCadeado();
        atualizarHistorico();
    }

    btnUndo?.addEventListener('click', () => {

        if (indiceHistorico <= 0) return;

        indiceHistorico--;

        restaurarEstado(
            historico[indiceHistorico]
        );
    });

    btnRedo?.addEventListener('click', () => {

        if (indiceHistorico >= historico.length - 1) {
            return;
        }

        indiceHistorico++;

        restaurarEstado(
            historico[indiceHistorico]
        );
    });

    document.addEventListener('keydown', evento => {

        if (!modoEdicao) return;

        if (evento.target.closest(
            'input, textarea, [contenteditable], .note-editor'
        )) {
            return;
        }

        const tecla = evento.key.toLowerCase();

        if (evento.ctrlKey && tecla === 'z') {

            evento.preventDefault();
            btnUndo?.click();
        }

        if (evento.ctrlKey && tecla === 'y') {

            evento.preventDefault();
            btnRedo?.click();
        }
    });

    /* =====================================================
       9. AÇÕES DOS ELEMENTOS
    ===================================================== */

    canvas.addEventListener('click', evento => {

        if (!modoEdicao) return;

        /* -------------------------------------
           EXCLUIR ELEMENTO
        ------------------------------------- */

        const excluir = evento.target.closest(
            '.btn-delete-element'
        );

        if (excluir) {

            evento.preventDefault();
            evento.stopPropagation();

            const elemento = excluir.closest(
                '.elemento-livre'
            );

            if (!elemento) return;

            const idElemento = elemento.dataset.id;
            const tipoElemento = elemento.dataset.tipo;

            Swal.fire({

                title: 'Excluir elemento?',

                text: (
                    tipoElemento === 'logo'
                        ? 'Deseja remover a logo desta página?'
                        : 'Deseja remover este elemento?'
                ),

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Sim, excluir',

                cancelButtonText: 'Cancelar',

                confirmButtonColor: '#dc3545'

            }).then(resultado => {

                if (!resultado.isConfirmed) return;

                // Remove visualmente do canvas.
                elemento.remove();

                // O ID original permanece em idsOriginais.
                // Assim será identificado no salvamento.
                registrarMudanca();

                aviso(
                    'success',
                    ehIdPersistido(idElemento)
                        ? 'Elemento removido. Salve para confirmar!'
                        : 'Elemento removido!'
                );

                console.log(
                    'IDs pendentes para exclusão:',
                    identificarExcluidos()
                );
            });

            return;
        }

        /* -------------------------------------
           ALTERAR COR DO TEXTO
        ------------------------------------- */

        const btnCor = evento.target.closest(
            '.btn-bg-texto'
        );

        if (btnCor) {

            evento.preventDefault();
            evento.stopPropagation();

            const el = btnCor.closest(
                '.elemento-livre'
            );

            Swal.fire({

                title: 'Fundo do Texto',

                html: `
                    <label>Cor de fundo</label>

                    <input
                        type="color"
                        id="corTexto"
                        value="#ffffff"
                        style="width:100%;height:40px"
                    >

                    <br><br>

                    <button
                        type="button"
                        id="corTransparente"
                        class="btn btn-outline-dark w-100"
                    >
                        Deixar transparente
                    </button>
                `,

                showCancelButton: true,

                confirmButtonText: 'Aplicar',

                didOpen: () => {

                    $id('corTransparente').addEventListener(
                        'click',
                        () => {

                            el.style.backgroundColor = 'transparent';

                            el.style.boxShadow = 'none';

                            registrarMudanca();

                            Swal.close();
                        }
                    );
                },

                preConfirm: () => $id('corTexto').value

            }).then(resultado => {

                if (!resultado.isConfirmed) return;

                el.style.backgroundColor = resultado.value;

                el.style.boxShadow =
                    '0 4px 10px rgba(0,0,0,0.15)';

                registrarMudanca();
            });

            return;
        }

        /* -------------------------------------
           TROCAR IMAGEM
        ------------------------------------- */

        const trocar = evento.target.closest(
            '.btn-trocar-img'
        );

        if (trocar) {

            evento.preventDefault();
            evento.stopPropagation();

            imagemAtual = trocar
                .closest('.elemento-livre')
                .querySelector('.img-alvo');

            inputImagem?.click();

            return;
        }

        /* -------------------------------------
           CONFIGURAR ÍCONE
        ------------------------------------- */

        const configurar = evento.target.closest(
            '.btn-config-btn'
        );

        if (configurar) {

            evento.preventDefault();
            evento.stopPropagation();

            const circulo = configurar
                .closest('.elemento-livre')
                .querySelector('.icon-circle');

            if (!circulo) return;

            const tamanho =
                parseInt(circulo.style.width, 10)
                || circulo.offsetWidth
                || 65;

            const formato =
                circulo.style.borderRadius || '50%';

            Swal.fire({

                title: 'Configurar Ícone',

                html: `
                    <label>Tamanho (px)</label>

                    <input
                        type="range"
                        id="tamanhoIcone"
                        min="40"
                        max="150"
                        value="${tamanho}"
                        class="form-control-range"
                    >

                    <div
                        id="valorIcone"
                        style="text-align:center"
                    >
                        ${tamanho}px
                    </div>

                    <label>Formato</label>

                    <select
                        id="formatoIcone"
                        class="form-control"
                    >
                        <option value="50%">Círculo</option>
                        <option value="15px">Arredondado</option>
                        <option value="0px">Quadrado</option>
                    </select>
                `,

                showCancelButton: true,

                confirmButtonText: 'Aplicar',

                didOpen: () => {

                    $id('formatoIcone').value = formato;

                    $id('tamanhoIcone').addEventListener(
                        'input',
                        e => {

                            $id('valorIcone').textContent =
                                e.target.value + 'px';
                        }
                    );
                },

                preConfirm: () => ({

                    tamanho: $id('tamanhoIcone').value,

                    formato: $id('formatoIcone').value
                })

            }).then(resultado => {

                if (!resultado.isConfirmed) return;

                circulo.style.width =
                    resultado.value.tamanho + 'px';

                circulo.style.height =
                    resultado.value.tamanho + 'px';

                circulo.style.borderRadius =
                    resultado.value.formato;

                registrarMudanca();
            });

            return;
        }

        /* -------------------------------------
           EDITAR TEXTO
        ------------------------------------- */

        const texto = evento.target.closest(
            '.texto-editavel'
        );

        if (texto && window.jQuery?.fn?.summernote) {

            evento.preventDefault();
            evento.stopPropagation();

            textoAtual = texto;

            window.jQuery('#summernoteTexto').summernote(
                'code',
                texto.innerHTML.trim()
            );

            window.jQuery('#modalEdicaoTexto').modal('show');
        }
    });

    /* =====================================================
       10. ADICIONAR BLOCO DE TEXTO
    ===================================================== */

    btnAddTexto?.addEventListener('click', () => {

        const el = document.createElement('div');

        el.className = 'elemento-livre bloco-texto-livre';

        el.dataset.id = 'novo_texto_' + Date.now();
        el.dataset.tipo = 'texto';

        el.style.left = '30%';
        el.style.top = '50%';

        el.style.width = '350px';
        el.style.height = '100px';

        el.style.backgroundColor =
            'rgba(255, 255, 255, 0.85)';

        el.style.boxShadow =
            '0 4px 10px rgba(0,0,0,0.15)';

        el.innerHTML = `
            <button
                type="button"
                class="btn-delete-element"
                title="Excluir"
            >
                <i class="fas fa-trash"></i>
            </button>

            <button
                type="button"
                class="btn-bg-texto"
                title="Alterar fundo"
            >
                <i class="fas fa-fill-drip"></i>
            </button>

            <div class="resize-handle">
                <i class="fas fa-expand-arrows-alt"></i>
            </div>

            <div class="texto-editavel">
                <h2>Novo Texto</h2>
            </div>
        `;

        canvas.appendChild(el);

        initElemento(el);

        registrarMudanca();
    });

    /* =====================================================
       11. MODO DE EDIÇÃO
    ===================================================== */

    function mostrarFerramentas(ativo) {

        [
            btnSalvar,
            btnAddBotao,
            btnAddTexto,
            btnTrocarFundo,
            btnAjustarFundo
        ].forEach(btn => {

            if (btn) {
                btn.style.display = ativo
                    ? 'flex'
                    : 'none';
            }
        });
    }

    function desativarEdicao() {

        modoEdicao = false;

        viewport.classList.remove(
            'modo-edicao-ativo'
        );

        btnToggle.innerHTML =
            '<i class="fas fa-pencil-alt"></i>';

        btnToggle.style.background = '#ffffff';
        btnToggle.style.color = '#333';

        mostrarFerramentas(false);

        if (fundo()) {
            fundo().classList.add('travado');
        }

        atualizarCadeado();
    }

    btnToggle.addEventListener('click', () => {

        if (modoEdicao) {

            desativarEdicao();
            return;
        }

        modoEdicao = true;

        viewport.classList.add(
            'modo-edicao-ativo'
        );

        btnToggle.innerHTML =
            '<i class="fas fa-times"></i>';

        btnToggle.style.background = '#dc3545';
        btnToggle.style.color = '#ffffff';

        mostrarFerramentas(true);

        aviso(
            'info',
            'Modo de edição ativado!'
        );
    });

    /* =====================================================
       12. SUMMERNOTE
    ===================================================== */

    function iniciarSummernote(tentativa = 0) {

        if (window.jQuery?.fn?.summernote) {

            window.jQuery('#summernoteTexto').summernote({

                height: 250,

                toolbar: [
                    ['style', ['style']],
                    ['font', [
                        'bold',
                        'italic',
                        'underline',
                        'clear'
                    ]],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', [
                        'ul',
                        'ol',
                        'paragraph',
                        'height'
                    ]],
                    ['insert', ['link']],
                    ['view', [
                        'fullscreen',
                        'codeview'
                    ]]
                ]
            });

        } else if (tentativa < 20) {

            setTimeout(
                () => iniciarSummernote(tentativa + 1),
                250
            );
        }
    }

    iniciarSummernote();

    $id('btnSalvarTextoFormato')?.addEventListener(
        'click',
        () => {

            if (!textoAtual || !window.jQuery?.fn?.summernote) {
                return;
            }

            textoAtual.innerHTML =
                window.jQuery('#summernoteTexto')
                    .summernote('code');

            registrarMudanca();

            window.jQuery('#modalEdicaoTexto').modal('hide');

            aviso(
                'success',
                'Texto atualizado!'
            );
        }
    );

    /* =====================================================
       13. CRIAÇÃO DE NOVA PÁGINA
    ===================================================== */

    $id('selectPaginaDestino')?.addEventListener(
        'change',
        function() {

            const nova = this.value === 'nova';

            const div = $id('divNovaPagina');
            const input = $id('inputNovaPagina');

            if (!div || !input) return;

            div.style.display = nova
                ? 'block'
                : 'none';

            input.required = nova;

            if (!nova) {
                input.value = '';
            }
        }
    );

    /* =====================================================
       14. UPLOAD DE IMAGENS
    ===================================================== */

    async function enviarImagem(arquivo) {

        const formData = new FormData();

        formData.append('imagem', arquivo);

        formData.append(
            '_token',
            config.csrfToken
        );

        const resposta = await fetch(
            config.routes.upload,
            {
                method: 'POST',

                headers: {
                    'Accept': 'application/json'
                },

                body: formData
            }
        );

        const dados = await resposta.json();

        if (!resposta.ok || !dados.success) {

            throw new Error(
                dados.message || 'Falha no upload.'
            );
        }

        return dados;
    }

    /* =====================================================
       15. PERSONALIZAR FUNDO
    ===================================================== */

    btnTrocarFundo?.addEventListener('click', () => {

        Swal.fire({

            title: 'Personalizar Fundo',

            html: `
                <p>Escolha o tipo de fundo:</p>

                <div style="
                    display:flex;
                    justify-content:center;
                    gap:15px;
                ">

                    <button
                        type="button"
                        id="escolherImagem"
                        class="btn btn-primary"
                    >
                        Imagem
                    </button>

                    <button
                        type="button"
                        id="escolherGradiente"
                        class="btn btn-info"
                    >
                        Gradiente
                    </button>

                </div>
            `,

            showConfirmButton: false,
            showCancelButton: true,

            didOpen: () => {

                $id('escolherImagem').addEventListener(
                    'click',
                    () => {

                        Swal.close();
                        inputFundo?.click();
                    }
                );

                $id('escolherGradiente').addEventListener(
                    'click',
                    () => {

                        Swal.close();
                        configurarGradiente();
                    }
                );
            }
        });
    });

    function configurarGradiente() {

        Swal.fire({

            title: 'Montar Gradiente',

            html: `
                <div style="
                    display:flex;
                    justify-content:space-around;
                ">

                    <div>
                        <label>Cor 1</label><br>
                        <input
                            type="color"
                            id="corBg1"
                            value="#ff7a00"
                        >
                    </div>

                    <div>
                        <label>Cor 2</label><br>
                        <input
                            type="color"
                            id="corBg2"
                            value="#ffc107"
                        >
                    </div>

                    <div>
                        <label>Cor 3</label><br>
                        <input
                            type="color"
                            id="corBg3"
                            value="#8bc34a"
                        >
                    </div>

                    <div>
                        <label>Cor 4</label><br>
                        <input
                            type="color"
                            id="corBg4"
                            value="#03a9f4"
                        >
                    </div>

                </div>
            `,

            showCancelButton: true,
            confirmButtonText: 'Aplicar',

            preConfirm: () => ({

                c1: $id('corBg1').value,
                c2: $id('corBg2').value,
                c3: $id('corBg3').value,
                c4: $id('corBg4').value
            })

        }).then(resultado => {

            if (!resultado.isConfirmed) return;

            const c = resultado.value;

            const gradiente =
                `linear-gradient(135deg, ` +
                `${c.c1} 0%, ${c.c2} 33%, ` +
                `${c.c3} 66%, ${c.c4} 100%)`;

            canvas.style.background = gradiente;

            canvas.dataset.fundoTipo = 'css';

            canvas.dataset.fundoValor = gradiente;

            if (fundo()) {

                fundo().style.display = 'none';

                fundo().classList.add('travado');
            }

            atualizarCadeado();

            registrarMudanca();
        });
    }

    /* =====================================================
       16. UPLOAD DO FUNDO
    ===================================================== */

    inputFundo?.addEventListener(
        'change',
        async function() {

            const arquivo = this.files?.[0];

            if (!arquivo) return;

            try {

                const dados = await enviarImagem(arquivo);

                const el = fundo();

                if (!el) {

                    throw new Error(
                        'Bloco do fundo não encontrado.'
                    );
                }

                canvas.style.background = '#ffffff';

                canvas.dataset.fundoTipo = 'imagem';

                canvas.dataset.fundoValor =
                    dados.caminho_relativo;

                el.style.display = 'flex';

                el.style.left = '0%';
                el.style.top = '0%';

                el.style.width = larguraBase + 'px';
                el.style.height = alturaBase + 'px';

                el.dataset.w = larguraBase;
                el.dataset.h = alturaBase;

                const img = el.querySelector('.img-alvo');

                if (img) {

                    img.src = dados.url_completa;

                    img.dataset.rawSrc =
                        dados.caminho_relativo;
                }

                el.classList.remove('travado');

                atualizarCadeado();

                registrarMudanca();

                aviso(
                    'success',
                    'Fundo inserido!'
                );

            } catch (e) {

                erro(e.message);

            } finally {

                this.value = '';
            }
        }
    );

    /* =====================================================
       17. UPLOAD DE LOGO OU ÍCONE
    ===================================================== */

    inputImagem?.addEventListener(
        'change',
        async function() {

            const arquivo = this.files?.[0];

            if (!arquivo || !imagemAtual) {
                return;
            }

            try {

                const dados = await enviarImagem(arquivo);

                imagemAtual.src = dados.url_completa;

                imagemAtual.dataset.rawSrc =
                    dados.caminho_relativo;

                registrarMudanca();

                aviso(
                    'success',
                    'Imagem atualizada!'
                );

            } catch (e) {

                erro(e.message);

            } finally {

                this.value = '';
                imagemAtual = null;
            }
        }
    );

    /* =====================================================
       18. SALVAR CONTEÚDOS + EXCLUSÕES

       Aqui enviamos:
         pagina_id
         blocos
         ids_excluidos
    ===================================================== */

    btnSalvar.addEventListener('click', async () => {

        btnSalvar.disabled = true;

        btnSalvar.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i>';

        try {

            const blocos = [];

            /* -------------------------------------
               COMPONENTES EXISTENTES NA TELA
            ------------------------------------- */

            elementos()
                .filter(el => el.id !== 'blocoFundoImagem')
                .forEach((el, index) => {

                    const tipo = el.dataset.tipo;

                    const img = el.querySelector('.img-alvo');

                    const txt = el.querySelector(
                        '.texto-editavel'
                    );

                    const icon = el.querySelector(
                        '.icon-circle'
                    );

                    const texto = txt
                        ? txt.innerHTML.trim()
                        : 'Logo';

                    const tamanhoIcone = icon
                        ? icon.offsetWidth || 65
                        : tipo === 'logo'
                            ? '100%'
                            : 65;

                    const formatoIcone = icon
                        ? icon.style.borderRadius || '50%'
                        : '50%';

                    let cor = el.style.backgroundColor
                        || getComputedStyle(el).backgroundColor;

                    if (
                        !cor ||
                        cor === 'rgba(0, 0, 0, 0)'
                    ) {
                        cor = 'transparent';
                    }

                    blocos.push({

                        id: el.dataset.id,

                        tipo: tipo,

                        ordem: index + 1,

                        dados_conteudo: {

                            texto: texto,

                            titulo: texto,

                            icone: img?.dataset.rawSrc
                                || 'totem/img/botoes/BTN_Historia.png',

                            pagina_destino_id:
                                el.dataset.destino || 1,

                            pos_x:
                                parseFloat(el.style.left) || 0,

                            pos_y:
                                parseFloat(el.style.top) || 0,

                            largura: el.offsetWidth,

                            altura: el.offsetHeight,

                            formato_icone: formatoIcone,

                            tamanho_icone: tamanhoIcone,

                            cor_fundo: cor
                        }
                    });
                });

            /* -------------------------------------
               CONFIGURAÇÕES DO FUNDO
            ------------------------------------- */

            const tipoFundo =
                canvas.dataset.fundoTipo || 'css';

            const valorFundo =
                canvas.dataset.fundoValor || '';

            const dadosFundo = {

                tipo_fundo: tipoFundo,

                valor_fundo: valorFundo,

                largura_canvas: larguraBase,

                altura_canvas: alturaBase
            };

            if (tipoFundo === 'imagem') {

                const el = fundo();

                if (!el) {
                    throw new Error(
                        'Imagem de fundo ausente.'
                    );
                }

                dadosFundo.pos_x =
                    parseFloat(el.style.left) || 0;

                dadosFundo.pos_y =
                    parseFloat(el.style.top) || 0;

                dadosFundo.largura = el.offsetWidth;

                dadosFundo.altura = el.offsetHeight;
            }

            blocos.push({

                id: canvas.dataset.fundoId || 'novo_fundo',

                tipo: 'fundo',

                ordem: 0,

                dados_conteudo: dadosFundo
            });

            /* -------------------------------------
               IDENTIFICAR REGISTROS EXCLUÍDOS

               O sistema compara os IDs originais
               com os IDs ainda existentes.
            ------------------------------------- */

            const idsExcluidos = identificarExcluidos();

            console.log('Página:', canvas.dataset.paginaId);

            console.log(
                'Elementos para salvar:',
                blocos
            );

            console.log(
                'Elementos para excluir:',
                idsExcluidos
            );

            /* -------------------------------------
               ENVIAR DADOS PARA O LARAVEL
            ------------------------------------- */

            const payload = {

                pagina_id: canvas.dataset.paginaId,

                blocos: blocos,

                ids_excluidos: idsExcluidos
            };

            const resposta = await fetch(
                config.routes.salvar,
                {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/json',

                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': config.csrfToken
                    },

                    body: JSON.stringify(payload)
                }
            );

            const dados = await resposta.json();

            if (!resposta.ok || !dados.success) {

                throw new Error(
                    dados.message
                    || 'Não foi possível salvar os conteúdos.'
                );
            }

            desativarEdicao();

            await Swal.fire({

                title: 'Conteúdo salvo!',

                text: (
                    idsExcluidos.length > 0
                        ? `${idsExcluidos.length} exclusão(ões) enviadas ao servidor.`
                        : 'Alterações enviadas com sucesso.'
                ),

                icon: 'success',

                timer: 1800,

                showConfirmButton: false
            });

            // Recarrega os dados atualizados do banco
            window.location.reload();

        } catch (e) {

            erro(e.message);

        } finally {

            btnSalvar.disabled = false;

            btnSalvar.innerHTML =
                '<i class="fas fa-check"></i>';
        }
    });

    /* =====================================================
       19. PUBLICAR NOS TOTENS
    ===================================================== */

    btnPublicar?.addEventListener('click', async () => {

        const confirmacao = await Swal.fire({

            title: 'Publicar conteúdos?',

            text: 'Salve suas alterações antes de publicar.',

            icon: 'question',

            showCancelButton: true,

            confirmButtonText: 'Publicar',

            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        btnPublicar.disabled = true;

        try {

            const resposta = await fetch(
                config.routes.publicar,
                {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/json',

                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': config.csrfToken
                    },

                    body: JSON.stringify({

                        pagina_id: canvas.dataset.paginaId
                    })
                }
            );

            const dados = await resposta.json();

            if (!resposta.ok || dados.success === false) {

                throw new Error(
                    dados.message || 'Falha ao publicar.'
                );
            }

            aviso(
                'success',
                'Conteúdo publicado!'
            );

        } catch (e) {

            erro(e.message);

        } finally {

            btnPublicar.disabled = false;
        }
    });

    /* =====================================================
       20. INICIALIZAÇÃO
    ===================================================== */

    atualizarCadeado();

    // Salva o estado inicial no histórico.
    registrarMudanca();

    console.log('Editor do Totem inicializado.');

    console.log(
        'IDs originais:',
        [...idsOriginais]
    );

});
