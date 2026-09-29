document.addEventListener('DOMContentLoaded', function() {
    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
        timerProgressBar: true, didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    let modoEdicao = false;
    let imagemSendoEditada = null;

    const canvas = document.getElementById('canvasTotem');
    const btnToggle = document.getElementById('btnToggleEdicao');
    const btnSalvar = document.getElementById('btnSalvarCanvas');
    const btnAdd = document.getElementById('btnAddBotao');
    const btnTrocarFundo = document.getElementById('btnTrocarFundo');
    const btnUndo = document.getElementById('btnUndo');
    const btnRedo = document.getElementById('btnRedo');
    const inputUpload = document.getElementById('inputUploadImagem');
    const inputUploadFundo = document.getElementById('inputUploadFundo');

    let historico = [];
    let passoAtual = -1;

    // Aplica posições e larguras
    document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
        el.style.left = el.getAttribute('data-x') + '%';
        el.style.top = el.getAttribute('data-y') + '%';
        if (el.getAttribute('data-w')) el.style.width = el.getAttribute('data-w') + 'px';
        if (el.getAttribute('data-h')) el.style.height = el.getAttribute('data-h') + 'px';
    });

    function capturarEstado() {
        const itens = [];
        document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
            const imgEl = el.querySelector('.img-alvo');
            const txtEl = el.querySelector('.texto-editavel');
            itens.push({
                id: el.getAttribute('data-id'),
                x: el.getAttribute('data-x'), y: el.getAttribute('data-y'),
                w: el.getAttribute('data-w'), h: el.getAttribute('data-h'),
                texto: txtEl ? txtEl.innerHTML : null,
                imgSrc: imgEl ? imgEl.src : null,
                imgRaw: imgEl ? imgEl.getAttribute('data-raw-src') : null
            });
        });

        return JSON.stringify({
            fundoTipo: canvas.getAttribute('data-fundo-tipo') || 'css',
            fundoValor: canvas.getAttribute('data-fundo-valor') || '',
            elementos: itens
        });
    }

    function registrarMudanca() {
        const novoEstado = capturarEstado();
        if (passoAtual >= 0 && historico[passoAtual] === novoEstado) return;
        historico = historico.slice(0, passoAtual + 1);
        historico.push(novoEstado);
        passoAtual++;
        atualizarBotoesHistorico();
    }

    function restaurarEstado(jsonEstado) {
        const estado = JSON.parse(jsonEstado);

        // Restaura o plano de fundo dinâmico
        if (estado.fundoTipo === 'imagem' && estado.fundoValor) {
            canvas.style.setProperty('background', `url(/${estado.fundoValor}) center/cover no-repeat`, 'important');
        } else if (estado.fundoValor) {
            canvas.style.setProperty('background', estado.fundoValor, 'important');
        }
        canvas.setAttribute('data-fundo-tipo', estado.fundoTipo);
        canvas.setAttribute('data-fundo-valor', estado.fundoValor);

        estado.elementos.forEach(item => {
            const el = document.querySelector(`#canvasTotem .elemento-livre[data-id="${item.id}"]`);
            if (el) {
                el.setAttribute('data-x', item.x); el.setAttribute('data-y', item.y);
                el.setAttribute('data-w', item.w); el.setAttribute('data-h', item.h);
                el.style.left = item.x + '%'; el.style.top = item.y + '%';
                if (item.w) el.style.width = item.w + 'px';
                if (item.h) el.style.height = item.h + 'px';

                const txtEl = el.querySelector('.texto-editavel');
                if (txtEl && item.texto !== null) txtEl.innerHTML = item.texto;
                const imgEl = el.querySelector('.img-alvo');
                if (imgEl && item.imgSrc) {
                    imgEl.src = item.imgSrc;
                    imgEl.setAttribute('data-raw-src', item.imgRaw);
                }
            }
        });
        atualizarBotoesHistorico();
    }

    function atualizarBotoesHistorico() {
        btnUndo.disabled = (passoAtual <= 0);
        btnRedo.disabled = (passoAtual >= historico.length - 1);
    }

    btnUndo.addEventListener('click', () => { if (passoAtual > 0) { passoAtual--; restaurarEstado(historico[passoAtual]); } });
    btnRedo.addEventListener('click', () => { if (passoAtual < historico.length - 1) { passoAtual++; restaurarEstado(historico[passoAtual]); } });

    document.addEventListener('keydown', e => {
        if (!modoEdicao) return;
        if (e.ctrlKey && e.key.toLowerCase() === 'z') { e.preventDefault(); btnUndo.click(); } 
        else if (e.ctrlKey && e.key.toLowerCase() === 'y') { e.preventDefault(); btnRedo.click(); }
    });

    btnToggle.addEventListener('click', () => {
        modoEdicao = !modoEdicao;
        if (modoEdicao) {
            canvas.classList.add('modo-edicao-ativo');
            btnToggle.innerHTML = '<i class="fas fa-times"></i>';
            btnToggle.style.background = '#dc3545';
            btnToggle.style.color = '#fff';
            btnSalvar.style.display = 'flex';
            btnAdd.style.display = 'flex';
            if (btnTrocarFundo) btnTrocarFundo.style.display = 'flex';

            document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => el.setAttribute('contenteditable', 'true'));
            if (historico.length === 0) registrarMudanca();
            Toast.fire({ icon: 'info', title: 'Modo Edição Ativado' });
        } else {
            desativarEdicao();
        }
    });

    function desativarEdicao() {
        modoEdicao = false;
        canvas.classList.remove('modo-edicao-ativo');
        btnToggle.innerHTML = '<i class="fas fa-pencil-alt"></i>';
        btnToggle.style.background = '#ffffff';
        btnToggle.style.color = '#333';
        btnSalvar.style.display = 'none';
        btnAdd.style.display = 'none';
        if (btnTrocarFundo) btnTrocarFundo.style.display = 'none';

        document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => el.removeAttribute('contenteditable'));
    }

    document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => el.addEventListener('blur', () => { if (modoEdicao) registrarMudanca(); }));

    // Lógica do Modal Nova Página
    const selectDestino = document.getElementById('selectPaginaDestino');
    const divNovaPagina = document.getElementById('divNovaPagina');
    const inputNovaPagina = document.getElementById('inputNovaPagina');

    if (selectDestino) {
        selectDestino.addEventListener('change', function() {
            if (this.value === 'nova') {
                divNovaPagina.style.display = 'block';
                inputNovaPagina.setAttribute('required', 'required');
            } else {
                divNovaPagina.style.display = 'none';
                inputNovaPagina.removeAttribute('required');
                inputNovaPagina.value = '';
            }
        });
    }

    // Redimensionar
    document.querySelectorAll('.resize-handle').forEach(handle => {
        handle.addEventListener('mousedown', function(e) {
            if (!modoEdicao) return;
            e.preventDefault(); e.stopPropagation();

            const el = this.closest('.elemento-livre');
            const startWidth = el.offsetWidth;
            const startHeight = el.offsetHeight;
            const startX = e.clientX;
            const startY = e.clientY;

            function onMouseMove(event) {
                const diffX = event.clientX - startX;
                const diffY = event.clientY - startY;
                const newWidth = Math.max(90, startWidth + diffX);
                const newHeight = Math.max(90, startHeight + diffY);

                el.style.width = newWidth + 'px';
                el.style.height = newHeight + 'px';
                el.setAttribute('data-w', newWidth);
                el.setAttribute('data-h', newHeight);
            }

            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                registrarMudanca();
            }

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });

    // Mover
    document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
        el.addEventListener('click', function(e) {
            if (!modoEdicao && this.hasAttribute('data-url-destino')) {
                window.location.href = this.getAttribute('data-url-destino');
            }
        });

        el.addEventListener('mousedown', function(e) {
            if (!modoEdicao) return;
            if (e.target.isContentEditable || e.target.closest('.btn-trocar-img') || e.target.closest('.resize-handle')) return;

            e.preventDefault();
            let moveu = false;
            const rectCanvas = canvas.getBoundingClientRect();
            const rectEl = el.getBoundingClientRect();
            const shiftX = e.clientX - rectEl.left;
            const shiftY = e.clientY - rectEl.top;

            function onMouseMove(event) {
                moveu = true;
                let newLeft = ((event.clientX - rectCanvas.left - shiftX) / rectCanvas.width) * 100;
                let newTop = ((event.clientY - rectCanvas.top - shiftY) / rectCanvas.height) * 100;

                newLeft = Math.max(1, Math.min(newLeft, 90));
                newTop = Math.max(1, Math.min(newTop, 90));

                el.setAttribute('data-x', newLeft.toFixed(2));
                el.setAttribute('data-y', newTop.toFixed(2));
                el.style.left = newLeft.toFixed(2) + '%';
                el.style.top = newTop.toFixed(2) + '%';
            }

            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                if (moveu) registrarMudanca();
            }

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });

   // ==========================================
    // UPLOAD / EDIÇÃO DE FUNDO DA TELA
    // ==========================================
    if (btnTrocarFundo && inputUploadFundo) {
        btnTrocarFundo.addEventListener('click', () => {
            Swal.fire({
                title: 'Personalizar Fundo',
                text: 'O que você quer colocar no fundo da tela?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-image"></i> Imagem',
                denyButtonText: '<i class="fas fa-palette"></i> Gradiente',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#007bff',
                denyButtonColor: '#17a2b8'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Escolheu Imagem
                    inputUploadFundo.click(); 
                } else if (result.isDenied) {
                    // Escolheu Gradiente - Abre Modal de Cores
                    Swal.fire({
                        title: 'Montar Gradiente (4 Cores)',
                        html: `
                            <p style="margin-bottom:15px; font-size:14px; color:#555;">Misture até 4 cores (se quiser menos cores, repita a cor anterior):</p>
                            <div style="display: flex; justify-content: space-around; margin-top: 10px;">
                                <div>
                                    <label style="font-size:12px;">Cor 1</label><br>
                                    <input type="color" id="corBg1" value="#ff7a00" style="width: 50px; height: 40px; cursor: pointer; border: none; padding: 0;">
                                </div>
                                <div>
                                    <label style="font-size:12px;">Cor 2</label><br>
                                    <input type="color" id="corBg2" value="#ffc107" style="width: 50px; height: 40px; cursor: pointer; border: none; padding: 0;">
                                </div>
                                <div>
                                    <label style="font-size:12px;">Cor 3</label><br>
                                    <input type="color" id="corBg3" value="#8bc34a" style="width: 50px; height: 40px; cursor: pointer; border: none; padding: 0;">
                                </div>
                                <div>
                                    <label style="font-size:12px;">Cor 4</label><br>
                                    <input type="color" id="corBg4" value="#03a9f4" style="width: 50px; height: 40px; cursor: pointer; border: none; padding: 0;">
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check"></i> Aplicar Fundo',
                        confirmButtonColor: '#28a745',
                        preConfirm: () => {
                            return {
                                cor1: document.getElementById('corBg1').value,
                                cor2: document.getElementById('corBg2').value,
                                cor3: document.getElementById('corBg3').value,
                                cor4: document.getElementById('corBg4').value
                            }
                        }
                    }).then((colorResult) => {
                        if (colorResult.isConfirmed) {
                            const v = colorResult.value;
                            // Monta o gradiente com 4 pontos: 0%, 33%, 66% e 100%
                            const gradiente = `linear-gradient(135deg, ${v.cor1} 0%, ${v.cor2} 33%, ${v.cor3} 66%, ${v.cor4} 100%)`;
                            
                            canvas.style.setProperty('background', gradiente, 'important');
                            canvas.setAttribute('data-fundo-tipo', 'css');
                            canvas.setAttribute('data-fundo-valor', gradiente);
                            registrarMudanca();
                        }
                    });
                }
            });
        });

        // Quando o usuário envia a foto de fundo...
        inputUploadFundo.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            const formData = new FormData();
            formData.append('imagem', this.files[0]);
            formData.append('_token', window.TotemEditor.csrfToken);

            Toast.fire({ icon: 'info', title: 'Carregando plano de fundo...' });

            fetch(window.TotemEditor.routes.upload, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    canvas.style.setProperty('background', `url(${data.url_completa}) center/cover no-repeat`, 'important');
                    canvas.setAttribute('data-fundo-tipo', 'imagem');
                    canvas.setAttribute('data-fundo-valor', data.caminho_relativo);
                    registrarMudanca();
                    Toast.fire({ icon: 'success', title: 'Fundo de imagem atualizado!' });
                } else { 
                    Toast.fire({ icon: 'error', title: 'Erro: ' + (data.message || 'Falha ao enviar.') });
                }
            }).catch(() => Toast.fire({ icon: 'error', title: 'Erro de conexão no upload.' }));
        });
    }

    // UPLOAD DE IMAGENS DOS BOTÕES E LOGO
    document.querySelectorAll('#canvasTotem .btn-trocar-img').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); e.preventDefault();
            imagemSendoEditada = this.closest('.elemento-livre').querySelector('.img-alvo');
            inputUpload.click();
        });
    });

    inputUpload.addEventListener('change', function() {
        if (!this.files || !this.files[0] || !imagemSendoEditada) return;
        const formData = new FormData();
        formData.append('imagem', this.files[0]);
        formData.append('_token', window.TotemEditor.csrfToken);

        Toast.fire({ icon: 'info', title: 'Enviando imagem...' });

        fetch(window.TotemEditor.routes.upload, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                imagemSendoEditada.src = data.url_completa;
                imagemSendoEditada.setAttribute('data-raw-src', data.caminho_relativo);
                registrarMudanca();
                Toast.fire({ icon: 'success', title: 'Imagem atualizada!' });
            } else { 
                Toast.fire({ icon: 'error', title: 'Erro: ' + (data.message || 'Falha ao enviar.') });
            }
        }).catch(() => Toast.fire({ icon: 'error', title: 'Erro de conexão.' }));
    });

    // ==========================================
    // SALVAR POSIÇÕES E O PLANO DE FUNDO
    // ==========================================
    btnSalvar.addEventListener('click', () => {
        const blocosAtualizados = [];
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // 1. Salva os Elementos
        document.querySelectorAll('#canvasTotem .elemento-livre').forEach((el, index) => {
            const imgEl = el.querySelector('.img-alvo');
            const txtEl = el.querySelector('.texto-editavel');
            
            blocosAtualizados.push({
                id: el.getAttribute('data-id'),
                tipo: el.getAttribute('data-tipo'),
                ordem: index + 1,
                dados_conteudo: {
                    titulo: txtEl ? txtEl.innerHTML.trim() : 'Logo',
                    icone: imgEl ? imgEl.getAttribute('data-raw-src') : 'totem/img/botoes/BTN_Historia.png',
                    pagina_destino_id: el.getAttribute('data-destino') || 1,
                    pos_x: parseFloat(el.getAttribute('data-x')) || 10,
                    pos_y: parseFloat(el.getAttribute('data-y')) || 20,
                    largura: parseFloat(el.getAttribute('data-w')) || el.offsetWidth,
                    altura: parseFloat(el.getAttribute('data-h')) || el.offsetHeight    
                }
            });
        });

        // 2. Salva o Plano de Fundo
        const fundoTipo = canvas.getAttribute('data-fundo-tipo');
        const fundoValor = canvas.getAttribute('data-fundo-valor');
        if (fundoValor) {
            blocosAtualizados.push({
                id: canvas.getAttribute('data-fundo-id') || 'novo_fundo',
                tipo: 'fundo',
                ordem: 0,
                dados_conteudo: { tipo_fundo: fundoTipo, valor_fundo: fundoValor }
            });
        }

        fetch(window.TotemEditor.routes.salvar, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.TotemEditor.csrfToken },
            body: JSON.stringify({ pagina_id: canvas.getAttribute('data-pagina-id'), blocos: blocosAtualizados })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao salvar.');
            return data;
        })
        .then(() => {
            desativarEdicao();
            Swal.fire({ title: 'Tudo Salvo!', text: 'As posições e o fundo de tela foram registrados.', icon: 'success', timer: 1500, showConfirmButton: false })
            .then(() => window.location.reload());
        })
        .catch(err => {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fas fa-check"></i>';
            Swal.fire({ icon: 'error', title: 'Ops...', text: 'Falha ao salvar: ' + err.message });
        });
    });

    // Publicar nos Totens
    const btnPublicar = document.getElementById('btnPublicarTotem');
    if (btnPublicar) {
        btnPublicar.addEventListener('click', () => {
            Swal.fire({
                title: 'Sincronizar Totens?', text: "Forçar a atualização imediata de todas as telas?",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#6f42c1',
                confirmButtonText: '<i class="fas fa-broadcast-tower"></i> Sincronizar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const iconeOriginal = btnPublicar.innerHTML;
                    btnPublicar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btnPublicar.disabled = true;

                    fetch(window.TotemEditor.routes.publicar, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.TotemEditor.csrfToken }
                    }).then(res => res.json()).then(data => {
                        btnPublicar.innerHTML = iconeOriginal; btnPublicar.disabled = false;
                        if (data.success) Swal.fire('Publicado!', data.message, 'success');
                        else Swal.fire('Falha', data.message, 'error');
                    }).catch(() => {
                        btnPublicar.innerHTML = iconeOriginal; btnPublicar.disabled = false;
                        Swal.fire('Erro', 'Sem comunicação com o servidor.', 'error');
                    });
                }
            });
        });
    }
});