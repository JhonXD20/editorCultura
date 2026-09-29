document.addEventListener("DOMContentLoaded", () => {
    const contentArea = document.getElementById("content-area");

    async function loadView(view) {
        try {
            const response = await fetch(`../conteudo/${view}`);
            if (!response.ok) throw new Error("Erro ao carregar view");
            const html = await response.text();
            contentArea.innerHTML = html;
        } catch (error) {
            contentArea.innerHTML = `<div class="alert alert-danger">Erro ao carregar conteúdo da view</div>`;
        }
    }

    const params = new URLSearchParams(window.location.search);
    const page = params.get("page");

    // Usa operador ternário
    page ? loadView(page) : contentArea.innerHTML = `<div class="text-center">Selecione uma view no menu</div>`;
});

addEventListener('click', () => {
    const botaoEsquerda = document.getElementById("botao-esquerda");
    const botaoDireita = document.getElementById("botao-direita");

    

});  