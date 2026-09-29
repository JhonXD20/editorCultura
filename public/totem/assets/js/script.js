// Interações touch-friendly e animações
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const hands = document.querySelectorAll('.hand');
    
    // Evitar chamada de vibração antes de qualquer interação do usuário (previne avisos do navegador)
    let userActivation = false;
    document.addEventListener('pointerdown', function() {
        userActivation = true;
    }, { capture: true, once: true });
    
    // Animação das mãos na árvore
    function animateHands() {
        hands.forEach((hand, index) => {
            setTimeout(() => {
                hand.style.transform += ' scale(1.2)';
                setTimeout(() => {
                    hand.style.transform = hand.style.transform.replace(' scale(1.2)', '');
                }, 300);
            }, index * 100);
        });
    }
    
    // Animar mãos a cada 5 segundos
    setInterval(animateHands, 5000);
    
    // Feedback tátil para dispositivos móveis
    navItems.forEach(item => {
        // Touch start - adiciona efeito visual imediato
        item.addEventListener('touchstart', function(e) {
            this.style.transform = 'scale(0.95)';
            this.style.transition = 'transform 0.1s ease';
            
            // Vibração tátil se disponível (só após interação do usuário para evitar bloqueio)
            if (navigator.vibrate && userActivation) {
                try {
                    navigator.vibrate(50);
                } catch (e) {
                    // Ignora erros de intervenção do navegador
                }
            }
        });
        
        // Touch end - remove efeito e executa ação
        item.addEventListener('touchend', function(e) {
            this.style.transform = '';
            this.style.transition = 'all 0.3s ease';
            
        });
        
        // Touch cancel - remove efeito se o toque for cancelado
        item.addEventListener('touchcancel', function(e) {
            this.style.transform = '';
            this.style.transition = 'all 0.3s ease';
        });
        
        
        // Hover effects para desktop
        if (!('ontouchstart' in window)) {
            item.addEventListener('mouseenter', function() {
                this.querySelector('.icon-circle').style.transform = 'rotate(10deg) scale(1.1)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.querySelector('.icon-circle').style.transform = '';
            });
        }
    });
    
    
    // Adiciona animações CSS dinamicamente
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
        }
        
        /* Melhora a performance em dispositivos móveis */
        .nav-item {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
        
        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }
        
        /* Otimizações para performance */
        .nav-item,
        .icon-circle,
        .hand {
            will-change: transform;
        }
    `;
    document.head.appendChild(style);
    
    // Otimização para dispositivos com tela sensível ao toque
    if ('ontouchstart' in window) {
        // Adiciona classe para dispositivos touch
        document.body.classList.add('touch-device');
        
        // Previne zoom duplo toque em elementos interativos
        navItems.forEach(item => {
            item.addEventListener('touchend', function(e) {
                e.preventDefault();
            });
        });
    }
    
    // Lazy loading para melhor performance
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '50px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);
    
    navItems.forEach(item => {
        observer.observe(item);
    });
    
    // Adiciona o título ao link do template ao clicar (para que o template saiba qual título mostrar)
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            const h3 = this.querySelector('h3');
            if (h3) {
                const titleText = h3.textContent.trim().replace(/\s+/g, ' ');
                try {
                    const url = new URL(this.href, window.location.origin);
                    url.searchParams.set('title', titleText);
                    this.href = url.toString();
                } catch (err) {
                    // Fallback simples: concatena o parâmetro (caso a URL seja relativa e o construtor falhe)
                    const sep = this.href.includes('?') ? '&' : '?';
                    this.href = this.href + sep + 'title=' + encodeURIComponent(titleText);
                }
            }
        });
    });
    
    // Adiciona suporte a gestos de swipe (opcional)
    let startX, startY, endX, endY;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchend', function(e) {
        endX = e.changedTouches[0].clientX;
        endY = e.changedTouches[0].clientY;
        
        const deltaX = endX - startX;
        const deltaY = endY - startY;
        
        // Detecta swipe horizontal
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
            if (deltaX > 0) {
                // Swipe para direita
                console.log('Swipe direita detectado');
            } else {
                // Swipe para esquerda
                console.log('Swipe esquerda detectado');
            }
        }
    });
    
    // Adiciona indicador de carregamento
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        
        // Anima entrada dos elementos
        navItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
});

// Função para redimensionamento responsivo
window.addEventListener('resize', function() {
    // Ajusta layout em mudanças de orientação
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
});

// Inicializa altura da viewport
const vh = window.innerHeight * 0.01;
document.documentElement.style.setProperty('--vh', `${vh}px`);

