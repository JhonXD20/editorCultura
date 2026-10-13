
document.addEventListener('DOMContentLoaded', () => {

    const viewport = document.getElementById(
        'totemPublicoViewport'
    );

    const palco = document.getElementById('totemStage');
    const canvas = document.getElementById('canvasTotem');

    if (!viewport || !palco || !canvas) return;

    const largura = Number(canvas.dataset.refWidth) || 1366;
    const altura = Number(canvas.dataset.refHeight) || 768;

    palco.style.width = largura + 'px';
    palco.style.height = altura + 'px';

    /* Escala proporcional */

  
function ajustarEscala() {

    const larguraTela = viewport.clientWidth;
    const alturaTela = viewport.clientHeight;

    if (!larguraTela || !alturaTela) return;

    const escalaX = larguraTela / largura;
    const escalaY = alturaTela / altura;

    // Preenche a tela mantendo as proporções
    const escala = Math.max(
        escalaX,
        escalaY
    );

    palco.style.width = largura + 'px';
    palco.style.height = altura + 'px';

    palco.style.transformOrigin = 'center center';

    palco.style.transform =
        `translate(-50%, -50%) scale(${escala})`;
}


    ajustarEscala();

    window.addEventListener('resize', ajustarEscala);

    /* Navegação */

    const proximaUrl = viewport.dataset.proximaUrl || '';
    const anteriorUrl = viewport.dataset.anteriorUrl || '';
    const inicialUrl = viewport.dataset.inicialUrl || '';

    function navegar(url) {
        if (url) window.location.assign(url);
    }

    function irDireita() {
        navegar(proximaUrl || inicialUrl);
    }

    function irEsquerda() {
        navegar(anteriorUrl);
    }

    function prepararZona(id, callback) {
        const zona = document.getElementById(id);
        if (!zona) return;

        zona.addEventListener('click', callback);

        zona.addEventListener('keydown', evento => {
            if (
                evento.key === 'Enter' ||
                evento.key === ' '
            ) {
                evento.preventDefault();
                callback();
            }
        });
    }

    prepararZona('touchZoneLeft', irEsquerda);
    prepararZona('touchZoneRight', irDireita);

    /* Botões de navegação dos conteúdos */

    canvas.querySelectorAll(
        '.card-botao-totem[data-url-destino]'
    ).forEach(botao => {

        const destino = botao.dataset.urlDestino;
        if (!destino) return;

        botao.addEventListener('click', () => {
            navegar(destino);
        });

        botao.addEventListener('keydown', evento => {
            if (
                evento.key === 'Enter' ||
                evento.key === ' '
            ) {
                evento.preventDefault();
                navegar(destino);
            }
        });
    });

    /* Navegação por teclado */

    document.addEventListener('keydown', evento => {

        if (evento.target.closest(
            'input, textarea, [role="button"], [role="link"]'
        )) return;

        if (evento.key === 'ArrowRight') {
            irDireita();
        }

        if (evento.key === 'ArrowLeft') {
            irEsquerda();
        }
    });

});
