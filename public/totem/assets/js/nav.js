/**
 * Este script gerencia a navegação de conteúdo dentro do template.html.
 * Ele lê o parâmetro 'page' da URL, carrega o conteúdo correspondente
 * e ativa os botões de navegação da esquerda/direita se o conteúdo
 * for paginado (ex: "conteudo-1.html").
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Seleção dos Elementos ---
    const contentArea = document.getElementById('content-area');
    const btnEsquerda = document.getElementById('botao-esquerda');
    const btnDireita = document.getElementById('botao-direita');

    // --- 2. Variáveis de Estado da Navegação ---
    let basePath = '';      // Guarda a pasta (ex: 'criacao-estado-tocantins/')
    let currentIndex = 0;       // Guarda o número da página atual (ex: 1)
    let isPaginated = false;  // true se a página for "conteudo-X.html"

    // --- 3. Funções de Navegação ---

    /**
     * Chamado pelo clique no botão DIREITA.
     */
    async function navegarDireita() {
        if (!isPaginated) return; // Se não for paginável, não faz nada

        const proximoIndice = currentIndex + 1;
        const proximaPagina = `${basePath}conteudo-${proximoIndice}.html`;

        // Tenta carregar a próxima página
        const sucesso = await carregarConteudo(proximaPagina);

        if (sucesso) {
            // Se conseguiu carregar, atualiza a URL na barra do navegador
            atualizarURLBrowser(proximaPagina);
        } else {
            // Se FALHOU (provavelmente 404, chegou ao fim do capítulo)
            // Volta para a página 1 (faz um loop)
            // console.log('Fim da seção. Voltando para a página 1.');
            const paginaInicial = `${basePath}conteudo-1.html`;
            await carregarConteudo(paginaInicial);
            atualizarURLBrowser(paginaInicial);
        }
    }

    /**
     * Chamado pelo clique no botão ESQUERDA.
     */
    async function navegarEsquerda() {
        if (!isPaginated) return; // Se não for paginável, não faz nada

        const indiceAnterior = currentIndex - 1;

        // Regra: Não deixa voltar antes da página 1
        if (indiceAnterior < 1) {
            console.log('Já está na primeira página.');
            return; // Para a execução
        }

        const paginaAnterior = `${basePath}conteudo-${indiceAnterior}.html`;
        
        // Tenta carregar a página anterior e atualiza a URL se conseguir
        const sucesso = await carregarConteudo(paginaAnterior);
        if (sucesso) {
            atualizarURLBrowser(paginaAnterior);
        }
    }

    // --- 4. Funções Auxiliares (O "Motor" do script) ---

    /**
     * Busca e insere o HTML na #content-area.
     * Retorna 'true' se teve sucesso ou 'false' se falhou (ex: 404).
     */
    async function carregarConteudo(pagePath) {
    const url = `/conteudo/${pagePath}`;

    try {
        const response = await fetch(url);

        if (!response.ok) {
            console.warn(`Arquivo não encontrado: ${url}`);
            return false;
        }

        // Inicia o fade-out
        contentArea.classList.add('fade-out');

        // Aguarda a transição antes de trocar o conteúdo
        await new Promise(resolve => setTimeout(resolve, 400)); // 400ms = duração do fade

        // Troca o conteúdo
        const html = await response.text();
        contentArea.innerHTML = html;

        // Faz o fade-in
        contentArea.classList.remove('fade-out');

        // Atualiza estado de paginação
        analisarPath(pagePath.replace('/conteudo/', ''));
        return true;

    } catch (error) {
        console.error("Falha ao carregar conteúdo:", error);
        contentArea.innerHTML = `<p>Erro ao carregar a página '${pagePath}'.</p>`;
        analisarPath('');
        return false;
    }
}


    /**
     * Analisa o 'pagePath' para descobrir se é paginável
     * e atualiza as variáveis de estado (basePath, currentIndex).
     */
    function analisarPath(pagePath) {
        // Regex para encontrar caminhos no formato 'pasta/conteudo-NUMERO.html'
        const regex = /(.*\/)conteudo-(\d+)\.html$/;
        const match = pagePath.match(regex);

        if (match) {
            // É UMA PÁGINA PAGINÁVEL!
            isPaginated = true;
            basePath = match[1];      // Grupo 1: 'criacao-estado-tocantins/'
            currentIndex = parseInt(match[2]); // Grupo 2: '1' (convertido para número)

            // Mostra os botões de navegação
            btnEsquerda.style.display = 'block';
            btnDireita.style.display = 'block';

            // Regra: Esconde o botão da esquerda se estiver na página 1
            if (currentIndex === 1) {
                btnEsquerda.style.display = 'none';
            }

        } else {
            // NÃO É PAGINÁVEL (ex: videos.html)
            isPaginated = false;
            basePath = '';
            currentIndex = 0;

            // Esconde os botões de navegação
            btnEsquerda.style.display = 'none';
            btnDireita.style.display = 'none';
        }
    }

    /**
     * Atualiza a barra de endereço do navegador (?page=...) sem recarregar.
     */
    function atualizarURLBrowser(pagePath) {
        const cleanedPath = pagePath.replace('/conteudo/', '');
        const params = new URLSearchParams(window.location.search);
        // Mantém outros parâmetros (ex: ?title=...)
        params.set('page', cleanedPath);
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        // pushState atualiza a URL na barra de endereço
        window.history.pushState({ path: newUrl }, '', newUrl);
    }

    /**
     * Função de INICIALIZAÇÃO.
     * É executada assim que o DOM do template.html é carregado.
     */
    function init() {
        // 1. Lê os parâmetros da URL
        const params = new URLSearchParams(window.location.search);
        const initialPage = params.get('page'); // Pega o valor de ?page=...

        // Se houver um título passado via query (?title=...), aplica no document.title e no h1 visível
        const titleParam = params.get('title');
        if (titleParam) {
            const t = titleParam.replace(/\+/g, ' ');
            document.title = t;
            const pageTitleEl = document.getElementById('page-title');
            if (pageTitleEl) pageTitleEl.textContent = t;
        }

        // 2. Adiciona os cliques nos botões
        btnDireita.addEventListener('click', navegarDireita);
        btnEsquerda.addEventListener('click', navegarEsquerda);

        // 3. Carrega o conteúdo inicial
        if (initialPage) {
            contentArea.innerHTML = '<p>Carregando...</p>';
            carregarConteudo(initialPage);
        } else {
            // Se ninguém passou um ?page=...
            contentArea.innerHTML = '<h1>Erro: Nenhuma página de conteúdo foi especificada.</h1>';
            analisarPath(''); // Esconde os botões
        }
    }

    // --- 5. Executar a Inicialização ---
    init();


});

