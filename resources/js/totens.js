
document.addEventListener('DOMContentLoaded', () => {

    const pagina = document.getElementById('paginaTotens');

    if (!pagina) return;

    /* =====================================
       FUNÇÕES AUXILIARES
    ===================================== */

    function abrirModal(modal) {
        if (modal && !modal.open) {
            modal.showModal();
        }
    }

    function fecharModal(modal) {
        if (modal?.open) {
            modal.close();
        }
    }

    function configurarFechamento(modal) {
        if (!modal) return;

        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) {
                fecharModal(modal);
            }
        });
    }

    function definirTexto(id, valor) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent = valor ?? '';
        }
    }

    function bloquearEnvio(form, botao, mensagem) {
        if (!form) return;

        form.addEventListener('submit', (evento) => {

            if (form.dataset.enviando === '1') {
                evento.preventDefault();
                return;
            }

            form.dataset.enviando = '1';

            if (botao) {
                botao.disabled = true;
                botao.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    ${mensagem}
                `;
            }
        });
    }

    /* =====================================
       FILTROS
    ===================================== */

    const botoesFiltro = document.querySelectorAll(
        '.filter-btn[data-filtro]'
    );

    const tabelaTotens = document.getElementById('tabelaTotens');

    const semResultados = document.getElementById(
        'semResultadosFiltro'
    );

    let filtroAtivo = 'todos';

    function aplicarFiltro(filtro = filtroAtivo) {

        filtroAtivo = filtro;
        const linhas = document.querySelectorAll(
            '#tabelaTotens tr[data-status]'
        );

        let visiveis = 0;

        linhas.forEach((linha) => {

            const mostrar =
                filtro === 'todos' ||
                linha.dataset.status === filtro;

            linha.hidden = !mostrar;

            if (mostrar) visiveis++;
        });

        botoesFiltro.forEach((botao) => {

            const ativo =
                botao.dataset.filtro === filtro;

            botao.classList.toggle('active', ativo);

            botao.setAttribute(
                'aria-pressed',
                ativo ? 'true' : 'false'
            );
        });

        if (semResultados) {
            semResultados.hidden =
                visiveis > 0 || linhas.length === 0;
        }
    }

    botoesFiltro.forEach((botao) => {
        botao.addEventListener('click', () => {
            aplicarFiltro(botao.dataset.filtro);
        });
    });

    aplicarFiltro('todos');

    /* =====================================
       SINCRONIZACAO EM SEGUNDO PLANO
    ===================================== */

    const btnAtualizar = document.getElementById(
        'btnAtualizarLista'
    );

    const indicadorSincronizacao = document.getElementById(
        'totensSyncStatus'
    );

    const intervaloAtualizacao = Math.max(
        Number(pagina.dataset.refreshInterval) || 10000,
        5000
    );

    let sincronizando = false;
    let temporizadorSincronizacao;

    function definirIndicador(texto = null, estado = 'ativo') {
        const rotulo = indicadorSincronizacao?.querySelector(
            '[data-sync-label]'
        );

        if (rotulo && texto) {
            rotulo.textContent = texto;
        }

        indicadorSincronizacao?.setAttribute(
            'data-sync-state',
            estado
        );
    }

    function criarLinhaTotem() {
        const linha = document.createElement('tr');

        linha.innerHTML = `
            <td><span class="totem-id"></span></td>
            <td>
                <div class="dispositivo-info">
                    <div class="dispositivo-nome" data-totem-nome></div>
                    <div class="dispositivo-localizacao">
                        <i class="fas fa-map-marker-alt"></i>
                        <span data-totem-localizacao></span>
                    </div>
                </div>
            </td>
            <td>
                <span class="custom-badge" data-totem-status-badge>
                    <span class="status-dot"></span>
                    <span data-totem-status-text></span>
                </span>
            </td>
            <td>
                <div class="ultimo-contato">
                    <span data-totem-contato-relativo></span>
                    <small class="data-contato" data-totem-contato-data></small>
                </div>
            </td>
            <td class="text-center">
                <div class="acoes-totem">
                    <button type="button" class="btn-action btn-acesso-totem" title="Preparar tablet">
                        <i class="fas fa-tablet-alt"></i>
                    </button>
                    <button type="button" class="btn-action btn-editar-totem" title="Editar Totem">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn-action btn-reiniciar-totem" title="Reiniciar Totem">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                    <button type="button" class="btn-action btn-excluir-totem" title="Excluir Totem">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;

        return linha;
    }

    function atualizarLinhaTotem(linha, totem) {
        const statusAnterior = linha.dataset.status;
        const pingAnterior = linha.dataset.ultimoContato;
        const status = totem.status === 'online'
            ? 'online'
            : 'offline';
        const contato = totem.ultimo_contato || {};

        linha.dataset.totemId = String(totem.id);
        linha.dataset.status = status;
        linha.dataset.ultimoContato = contato.iso || '';

        linha.querySelector('.totem-id').textContent = `#${totem.id}`;
        linha.querySelector('[data-totem-nome]').textContent = totem.nome;
        linha.querySelector('[data-totem-localizacao]').textContent =
            totem.localizacao;

        const badge = linha.querySelector('[data-totem-status-badge]');
        badge.classList.toggle('badge-online', status === 'online');
        badge.classList.toggle('badge-offline', status !== 'online');
        linha.querySelector('[data-totem-status-text]').textContent =
            status === 'online' ? 'Online' : 'Offline';

        const contatoRelativo = linha.querySelector(
            '[data-totem-contato-relativo]'
        );
        contatoRelativo.textContent = contato.relativo || 'Nunca';
        contatoRelativo.title = contato.data || 'Nunca';
        contatoRelativo.classList.toggle(
            'contato-online',
            status === 'online'
        );
        contatoRelativo.classList.toggle(
            'contato-offline',
            status !== 'online'
        );
        linha.querySelector('[data-totem-contato-data]').textContent =
            contato.data || 'Nunca';

        const editar = linha.querySelector('.btn-editar-totem');
        editar.dataset.id = totem.id;
        editar.dataset.urlBusca = totem.urls.buscar;
        editar.dataset.urlEditar = totem.urls.editar;
        editar.setAttribute('aria-label', `Editar totem ${totem.id}`);

        const reiniciar = linha.querySelector('.btn-reiniciar-totem');
        reiniciar.dataset.nome = totem.nome;
        reiniciar.setAttribute('aria-label', `Reiniciar totem ${totem.id}`);

        const excluir = linha.querySelector('.btn-excluir-totem');
        excluir.dataset.nome = totem.nome;
        excluir.dataset.url = totem.urls.excluir;
        excluir.setAttribute('aria-label', `Excluir totem ${totem.id}`);

        const acesso = linha.querySelector('.btn-acesso-totem');
        acesso.dataset.nome = totem.nome;
        acesso.dataset.url = totem.urls.acesso;
        acesso.setAttribute('aria-label', `Preparar tablet ${totem.id}`);

        if (
            statusAnterior &&
            (statusAnterior !== status || pingAnterior !== contato.iso)
        ) {
            linha.classList.remove('is-updated');
            window.requestAnimationFrame(() => {
                linha.classList.add('is-updated');
            });
            window.setTimeout(() => {
                linha.classList.remove('is-updated');
            }, 900);
        }
    }

    function garantirEstadoVazio(estaVazio) {
        let linhaVazia = tabelaTotens?.querySelector('[data-empty-row]');

        if (estaVazio && !linhaVazia && tabelaTotens) {
            linhaVazia = document.createElement('tr');
            linhaVazia.dataset.emptyRow = '';
            linhaVazia.innerHTML = `
                <td colspan="5">
                    <div class="sem-totens">
                        <i class="fas fa-desktop"></i>
                        <h5>Nenhum totem cadastrado</h5>
                        <p>Cadastre um equipamento para começar o monitoramento.</p>
                    </div>
                </td>
            `;
            tabelaTotens.append(linhaVazia);
        }

        if (linhaVazia) {
            linhaVazia.hidden = !estaVazio;
        }
    }

    function aplicarDadosSincronizados(dados) {
        const linhasExistentes = new Map(
            Array.from(tabelaTotens.querySelectorAll('[data-totem-id]'))
                .map((linha) => [linha.dataset.totemId, linha])
        );

        dados.totens.forEach((totem) => {
            const id = String(totem.id);
            let linha = linhasExistentes.get(id);

            if (!linha) {
                linha = criarLinhaTotem();
                tabelaTotens.append(linha);
            }

            atualizarLinhaTotem(linha, totem);
            linhasExistentes.delete(id);
        });

        linhasExistentes.forEach((linha) => linha.remove());
        garantirEstadoVazio(dados.totens.length === 0);

        Object.entries(dados.resumo).forEach(([tipo, total]) => {
            document.querySelectorAll(
                `[data-totem-count="${tipo}"]`
            ).forEach((elemento) => {
                elemento.textContent = total;
            });
        });

        document.querySelectorAll('[data-totem-total]')
            .forEach((elemento) => {
                elemento.textContent = dados.resumo.todos;
            });

        aplicarFiltro();
    }

    async function sincronizarTotens(manual = false) {
        if (sincronizando || !pagina.dataset.statusUrl) return;

        sincronizando = true;
        btnAtualizar?.classList.toggle('is-loading', manual);
        definirIndicador(null, 'sincronizando');

        try {
            const resposta = await fetch(pagina.dataset.statusUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!resposta.ok) {
                throw new Error('Falha ao sincronizar os totens.');
            }

            aplicarDadosSincronizados(await resposta.json());
            const horario = new Intl.DateTimeFormat('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date());
            definirIndicador(`Atualizado às ${horario}`, 'ativo');
        } catch (erro) {
            definirIndicador('Reconectando...', 'erro');
        } finally {
            sincronizando = false;
            btnAtualizar?.classList.remove('is-loading');
        }
    }

    function agendarSincronizacao() {
        window.clearTimeout(temporizadorSincronizacao);
        temporizadorSincronizacao = window.setTimeout(async () => {
            if (!document.hidden) {
                await sincronizarTotens();
            }

            agendarSincronizacao();
        }, intervaloAtualizacao);
    }

    btnAtualizar?.addEventListener('click', () => {
        sincronizarTotens(true);
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            sincronizarTotens();
            agendarSincronizacao();
        }
    });

    agendarSincronizacao();

    /* =====================================
       FORMULÁRIO ÚNICO:
       CADASTRAR E EDITAR
    ===================================== */

    const modalTotem = document.getElementById(
        'modalCadastrarTotem'
    );

    const formTotem = document.getElementById(
        'formCadastrarTotem'
    );

    const metodoForm = document.getElementById(
        'metodoFormTotem'
    );

    const campoModo = document.getElementById(
        'campoModoTotem'
    );

    const campoId = document.getElementById(
        'campoIdTotem'
    );

    const campoNome = document.getElementById(
        'totem_nome_identificacao'
    );

    const campoLocalizacao = document.getElementById(
        'totem_localizacao'
    );

    const campoPaginaInicial = document.getElementById('pagina_inicial_id');
    const campoOrientacao = document.getElementById('totem_orientacao');
    const campoInatividade = document.getElementById('totem_inatividade_segundos');

    const btnSalvar = document.getElementById(
        'btnSalvarCadastro'
    );

    const avisoCadastro = document.getElementById(
        'avisoCadastroTotem'
    );

    const erroCarregamento = document.getElementById(
        'erroCarregamentoTotem'
    );

    let numeroRequisicao = 0;

    /* =====================================
       CONTROLE DO BOTÃO SALVAR
    ===================================== */

    function configurarBotaoSalvar(texto) {

        if (!btnSalvar) return;

        btnSalvar.disabled = false;

        btnSalvar.innerHTML = `
            <i class="fas fa-save"></i>
            <span>${texto}</span>
        `;

        if (formTotem) {
            formTotem.dataset.enviando = '0';
        }
    }

    function mostrarErroCarregamento(mensagem) {

        if (!erroCarregamento) return;

        erroCarregamento.textContent = mensagem;
        erroCarregamento.hidden = !mensagem;
    }

    function limparErrosValidacao() {

        formTotem?.querySelectorAll(
            '.totens-erro-campo'
        ).forEach((elemento) => {

            elemento.remove();

        });

        formTotem?.querySelectorAll(
            '.is-invalid'
        ).forEach((elemento) => {

            elemento.classList.remove('is-invalid');

        });

        mostrarErroCarregamento('');
    }

    /* =====================================
       MODO CADASTRAR
    ===================================== */

    function configurarCadastro(preservarValores = false) {

        numeroRequisicao++;

        if (!preservarValores) {
            formTotem.reset();

            limparErrosValidacao();

            campoNome.value = '';
            campoLocalizacao.value = '';
            campoPaginaInicial.value = '';
            campoOrientacao.value = 'landscape';
            campoInatividade.value = '120';
        }

        formTotem.action = formTotem.dataset.urlCriar;

        // POST normal
        metodoForm.disabled = true;

        campoModo.value = 'criar';
        campoId.value = '';

        definirTexto(
            'textoTituloModalTotem',
            'Cadastrar Novo Totem'
        );

        definirTexto(
            'descricaoModalTotem',
            'Informe os dados de identificação e localização do novo equipamento.'
        );

        if (avisoCadastro) {
            avisoCadastro.hidden = false;
        }

        configurarBotaoSalvar('Salvar Totem');
    }

    /* =====================================
       MODO EDITAR
    ===================================== */

    function configurarEdicao(
        botao,
        preservarValores = false
    ) {

        if (!botao || !formTotem) return;

        numeroRequisicao++;

        if (!preservarValores) {
            formTotem.reset();
            limparErrosValidacao();

            campoNome.value = '';
            campoLocalizacao.value = '';
        }

        // Altera a URL para /totens/{id}
        formTotem.action = botao.dataset.urlEditar;

        // Laravel interpreta POST + _method=PUT
        metodoForm.disabled = false;
        metodoForm.value = 'PUT';

        campoModo.value = 'editar';
        campoId.value = botao.dataset.id;

        definirTexto(
            'textoTituloModalTotem',
            'Editar Totem'
        );

        definirTexto(
            'descricaoModalTotem',
            'Altere as informações do equipamento selecionado.'
        );

        if (avisoCadastro) {
            avisoCadastro.hidden = true;
        }

        configurarBotaoSalvar('Salvar Alterações');
    }

    /* =====================================
       ABRIR MODAL PARA CADASTRAR
    ===================================== */

    document.getElementById('btnCadastrarTotem')
        ?.addEventListener('click', () => {

            configurarCadastro();

            abrirModal(modalTotem);

            campoNome?.focus();

        });

    /* =====================================
       BUSCAR DADOS PARA EDITAR

       GET /totens/{id}

       Consulta diretamente o banco de dados
       através do TotemController@show.
    ===================================== */

    async function carregarTotemParaEdicao(botao) {

        configurarEdicao(botao);

        const requisicaoAtual = numeroRequisicao;

        abrirModal(modalTotem);

        // Bloqueia o envio enquanto carrega
        btnSalvar.disabled = true;

        btnSalvar.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            Carregando...
        `;

        try {

            const resposta = await fetch(
                botao.dataset.urlBusca,
                {
                    method: 'GET',

                    credentials: 'same-origin',

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                }
            );

            if (!resposta.ok) {
                throw new Error(
                    'Não foi possível consultar este totem.'
                );
            }

            const dados = await resposta.json();

            // Ignora respostas antigas ou modal fechado
            if (
                requisicaoAtual !== numeroRequisicao ||
                !modalTotem.open
            ) {
                return;
            }

            if (String(dados.id) !== String(botao.dataset.id)) {
                throw new Error(
                    'O servidor retornou outro equipamento.'
                );
            }

            // Dados retornados pelo banco
            campoNome.value =
                dados.totem_nome_identificacao ?? '';

            campoLocalizacao.value =
                dados.totem_localizacao ?? '';

            campoPaginaInicial.value = dados.pagina_inicial_id ?? '';
            campoOrientacao.value = dados.totem_orientacao ?? 'landscape';
            campoInatividade.value = dados.totem_inatividade_segundos ?? 120;

            configurarBotaoSalvar('Salvar Alterações');

            campoNome.focus();

        } catch (erro) {

            if (requisicaoAtual !== numeroRequisicao) {
                return;
            }

            const mensagemErro = erro.message ||
                'Erro ao carregar o equipamento.';

            mostrarErroCarregamento(mensagemErro);
            window.AdminNotice?.error(
                mensagemErro,
                'Erro ao carregar Totem'
            );

            // Não permite editar sem os dados válidos
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                Dados indisponíveis
            `;
        }
    }

    tabelaTotens?.addEventListener('click', (evento) => {
        const botao = evento.target.closest('.btn-editar-totem');

        if (botao) {
            carregarTotemParaEdicao(botao);
        }
    });

    /* =====================================
       FECHAR MODAL DE CADASTRO / EDIÇÃO
    ===================================== */

    document.getElementById('btnFecharCadastro')
        ?.addEventListener('click', () => {

            fecharModal(modalTotem);

        });

    document.getElementById('btnCancelarCadastro')
        ?.addEventListener('click', () => {

            fecharModal(modalTotem);

        });

    configurarFechamento(modalTotem);

    // Invalida consultas pendentes ao fechar
    modalTotem?.addEventListener('close', () => {
        numeroRequisicao++;
    });

    /* =====================================
       SUBMETER O MESMO FORMULÁRIO
    ===================================== */

    bloquearEnvio(
        formTotem,
        btnSalvar,
        'Salvando...'
    );

    /* =====================================
       REABRIR MODAL EM CASO DE ERROS
       DE VALIDAÇÃO DO LARAVEL
    ===================================== */

    if (pagina.dataset.reabrirFormulario === '1') {

        const modoAnterior = pagina.dataset.modoAnterior;
        const idAnterior = pagina.dataset.idAnterior;

        if (modoAnterior === 'editar' && idAnterior) {

            const botaoEdicao = Array.from(
                document.querySelectorAll('.btn-editar-totem')
            ).find((botao) => {
                return botao.dataset.id === idAnterior;
            });

            if (botaoEdicao) {

                // Preserva os dados digitados antes do erro
                configurarEdicao(botaoEdicao, true);

                abrirModal(modalTotem);
            }

        } else {

            configurarCadastro(true);

            abrirModal(modalTotem);
        }
    }

    /* =====================================
       REINICIAR TOTEM
    ===================================== */

    const modalAcesso = document.getElementById('modalAcessoTotem');
    const urlAcesso = document.getElementById('urlAcessoTotem');
    const erroAcesso = document.getElementById('erroAcessoTotem');
    const btnCopiarAcesso = document.getElementById('btnCopiarAcessoTotem');
    let requisicaoAcesso = 0;

    configurarFechamento(modalAcesso);
    modalAcesso?.addEventListener('close', () => {
        requisicaoAcesso++;
    });

    tabelaTotens?.addEventListener('click', async (evento) => {
        const botao = evento.target.closest('.btn-acesso-totem');
        if (!botao || !modalAcesso) return;

        const atual = ++requisicaoAcesso;
        urlAcesso.value = '';
        erroAcesso.hidden = true;
        btnCopiarAcesso.disabled = true;
        definirTexto('nomeAcessoTotem', botao.dataset.nome);
        definirTexto('statusCopiaAcesso', 'Gerando endereço...');
        abrirModal(modalAcesso);

        try {
            const resposta = await fetch(botao.dataset.url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
            });
            const dados = await resposta.json();
            if (atual !== requisicaoAcesso || !modalAcesso.open) return;

            if (!resposta.ok) {
                throw new Error(dados.message || 'Não foi possível preparar o tablet.');
            }

            urlAcesso.value = dados.url;
            btnCopiarAcesso.disabled = false;
            definirTexto('statusCopiaAcesso', `Página inicial: ${dados.pagina}`);
        } catch (erro) {
            if (atual !== requisicaoAcesso || !modalAcesso.open) return;
            erroAcesso.textContent = erro.message;
            erroAcesso.hidden = false;
            definirTexto('statusCopiaAcesso', '');
        }
    });

    btnCopiarAcesso?.addEventListener('click', async () => {
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Clipboard indisponível');
            await navigator.clipboard.writeText(urlAcesso.value);
            definirTexto('statusCopiaAcesso', 'Endereço copiado. Abra-o no tablet.');
        } catch (erro) {
            urlAcesso.focus();
            urlAcesso.select();
            definirTexto('statusCopiaAcesso', 'Endereço selecionado. Use a opção Copiar do navegador.');
        }
    });

    tabelaTotens?.addEventListener('click', async (evento) => {
        const botao = evento.target.closest('.btn-reiniciar-totem');

        if (!botao) return;

        await window.AdminNotice?.alert({
            type: 'info',
            icon: 'fas fa-redo-alt',
            title: 'Reiniciar Totem',
            message: `${botao.dataset.nome}\n\nA reinicialização remota ainda não está configurada. Nenhum comando será enviado ao equipamento.`,
            confirmText: 'Entendi',
        });
    });

    /* =====================================
       EXCLUIR TOTEM
    ===================================== */

    const formExclusao = document.getElementById(
        'formExcluirTotem'
    );

    tabelaTotens?.addEventListener('click', async (evento) => {
        const botao = evento.target.closest('.btn-excluir-totem');

        if (!botao) return;

        const confirmed = await window.AdminNotice?.confirm({
            type: 'danger',
            icon: 'fas fa-trash-alt',
            title: 'Excluir Totem?',
            message: `Tem certeza de que deseja excluir “${botao.dataset.nome}”?\n\nEsta ação removerá o cadastro do equipamento no banco de dados.`,
            confirmText: 'Excluir Totem',
        });

        if (!confirmed || !formExclusao) {
            return;
        }

        formExclusao.action = botao.dataset.url;
        botao.disabled = true;
        formExclusao.submit();
    });

    /* =====================================
       BOTÕES GENÉRICOS DE FECHAMENTO
    ===================================== */

    document.querySelectorAll(
        '[data-fechar-modal]'
    ).forEach((botao) => {

        botao.addEventListener('click', () => {

            const modal = document.getElementById(
                botao.dataset.fecharModal
            );

            fecharModal(modal);

        });

    });

});
