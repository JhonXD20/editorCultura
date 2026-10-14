
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

    const linhas = document.querySelectorAll(
        '#tabelaTotens tr[data-status]'
    );

    const semResultados = document.getElementById(
        'semResultadosFiltro'
    );

    function aplicarFiltro(filtro) {

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
       ATUALIZAR LISTAGEM
    ===================================== */

    document.getElementById('btnAtualizarLista')
        ?.addEventListener('click', () => {

            window.location.reload();

        });

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

    document.querySelectorAll(
        '.btn-editar-totem'
    ).forEach((botao) => {

        botao.addEventListener('click', () => {

            carregarTotemParaEdicao(botao);

        });

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

    document.querySelectorAll(
        '.btn-reiniciar-totem'
    ).forEach((botao) => {

        botao.addEventListener('click', async () => {

            await window.AdminNotice?.alert({
                type: 'info',
                icon: 'fas fa-redo-alt',
                title: 'Reiniciar Totem',
                message: `${botao.dataset.nome}\n\nA reinicialização remota ainda não está configurada. Nenhum comando será enviado ao equipamento.`,
                confirmText: 'Entendi',
            });

        });

    });

    /* =====================================
       EXCLUIR TOTEM
    ===================================== */

    const formExclusao = document.getElementById(
        'formExcluirTotem'
    );

    document.querySelectorAll(
        '.btn-excluir-totem'
    ).forEach((botao) => {

        botao.addEventListener('click', async () => {

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
