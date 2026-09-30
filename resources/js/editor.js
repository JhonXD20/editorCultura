document.addEventListener('DOMContentLoaded', function() {
    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
        timerProgressBar: true, didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
    });

    let modoEdicao = false;
    let imagemSendoEditada = null;
    let textoSendoEditado = null;

    const canvas = document.getElementById('canvasTotem');
    const btnToggle = document.getElementById('btnToggleEdicao');
    const btnSalvar = document.getElementById('btnSalvarCanvas');
    const btnAdd = document.getElementById('btnAddBotao');
    const btnAddTexto = document.getElementById('btnAddTextoLivre');
    const btnTrocarFundo = document.getElementById('btnTrocarFundo');
    const btnAjustarFundo = document.getElementById('btnAjustarFundo'); // CADEADO DO FUNDO
    const btnUndo = document.getElementById('btnUndo');
    const btnRedo = document.getElementById('btnRedo');
    const inputUpload = document.getElementById('inputUploadImagem');
    const inputUploadFundo = document.getElementById('inputUploadFundo');

    let historico = [];
    let passoAtual = -1;

    document.querySelectorAll('#canvasTotem .elemento-livre').forEach(initElemento);

    function initElemento(el) {
        if(el.getAttribute('data-w')) el.style.width = el.getAttribute('data-w') + 'px';
        if(el.getAttribute('data-h')) el.style.height = el.getAttribute('data-h') + 'px';

        el.addEventListener('mousedown', function(e) {
            if (!modoEdicao) return;
            if (el.classList.contains('travado')) return; // IGNORA SE ESTIVER BLOQUEADO (EX: FUNDO)
            if (e.target.closest('.texto-editavel') || e.target.closest('.btn-trocar-img') || e.target.closest('.btn-config-btn') || e.target.closest('.btn-bg-texto') || e.target.closest('.resize-handle') || e.target.closest('.btn-delete-element')) return;

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

        el.addEventListener('click', function(e) {
            if (!modoEdicao && this.hasAttribute('data-url-destino')) { window.location.href = this.getAttribute('data-url-destino'); }
        });
    }

    canvas.addEventListener('mousedown', function(e) {
        const handle = e.target.closest('.resize-handle');
        if (handle && modoEdicao) {
            const el = handle.closest('.elemento-livre');
            if (el.classList.contains('travado')) return;

            e.preventDefault(); e.stopPropagation();
            const startWidth = el.offsetWidth;
            const startHeight = el.offsetHeight;
            const startX = e.clientX;
            const startY = e.clientY;

            function onMouseMove(event) {
                const diffX = event.clientX - startX;
                const diffY = event.clientY - startY;
                const newWidth = Math.max(50, startWidth + diffX);
                const newHeight = Math.max(50, startHeight + diffY);
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
        }
    });

    canvas.addEventListener('click', function(e) {
        if (!modoEdicao) return;

        const btnDelete = e.target.closest('.btn-delete-element');
        if (btnDelete) {
            e.preventDefault(); e.stopPropagation();
            Swal.fire({
                title: 'Apagar?', text: "Vai remover este elemento!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sim'
            }).then((result) => {
                if (result.isConfirmed) {
                    btnDelete.closest('.elemento-livre').remove();
                    registrarMudanca();
                    Toast.fire({ icon: 'success', title: 'Removido!' });
                }
            });
            return;
        }

        const btnBgTexto = e.target.closest('.btn-bg-texto');
        if (btnBgTexto) {
            e.preventDefault(); e.stopPropagation();
            const el = btnBgTexto.closest('.elemento-livre');
            Swal.fire({
                title: 'Fundo do Texto',
                html: `<div style="margin-top: 15px;"><label>Escolha uma cor sólida:</label><input type="color" id="textColorInput" value="#ffffff" style="width: 100%; height: 40px; border: none;"><br><br><button type="button" class="btn btn-outline-dark w-100" id="btnMakeTransparent"><i class="fas fa-eraser"></i> Deixar Transparente</button></div>`,
                showCancelButton: true, confirmButtonText: 'Aplicar',
                didOpen: () => {
                    document.getElementById('btnMakeTransparent').addEventListener('click', () => { el.style.backgroundColor = 'transparent'; el.style.boxShadow = 'none'; registrarMudanca(); Swal.close(); });
                },
                preConfirm: () => document.getElementById('textColorInput').value
            }).then((result) => {
                if (result.isConfirmed) { el.style.backgroundColor = result.value; el.style.boxShadow = '0 4px 10px rgba(0,0,0,0.15)'; registrarMudanca(); }
            });
            return;
        }

        const textEl = e.target.closest('.texto-editavel');
        if (textEl) {
            e.preventDefault(); e.stopPropagation();
            textoSendoEditado = textEl;
            if (window.jQuery && window.jQuery.fn.summernote) { window.jQuery('#summernoteTexto').summernote('code', textEl.innerHTML.trim()); window.jQuery('#modalEdicaoTexto').modal('show'); }
            return;
        }

        const btnCam = e.target.closest('.btn-trocar-img');
        if (btnCam) {
            e.preventDefault(); e.stopPropagation();
            imagemSendoEditada = btnCam.closest('.elemento-livre').querySelector('.img-alvo');
            inputUpload.click();
            return;
        }

        const btnConf = e.target.closest('.btn-config-btn');
        if (btnConf) {
            e.preventDefault(); e.stopPropagation();
            const iconCircle = btnConf.closest('.elemento-livre').querySelector('.icon-circle');
            if (!iconCircle) return;
            const currentSize = iconCircle.style.width ? parseInt(iconCircle.style.width) : 65;
            const currentShape = iconCircle.style.borderRadius || '50%';
            Swal.fire({
                title: 'Formato do Ícone',
                html: `<div style="text-align: left; margin-top: 15px;"><label>Tamanho do Molde (px)</label><input type="range" id="iconSize" min="40" max="150" value="${currentSize}" class="form-control-range" oninput="document.getElementById('sizeVal').innerText = this.value + 'px'"><div id="sizeVal" style="text-align: center; font-weight: bold; margin-bottom: 15px;">${currentSize}px</div><label>Formato do Molde</label><select id="iconShape" class="form-control"><option value="50%" ${currentShape === '50%' ? 'selected' : ''}>Círculo</option><option value="15px" ${currentShape === '15px' ? 'selected' : ''}>Quadrado Arredondado</option><option value="0px" ${currentShape === '0px' ? 'selected' : ''}>Quadrado Seco</option></select></div>`,
                showCancelButton: true, confirmButtonText: 'Aplicar', confirmButtonColor: '#007bff',
                preConfirm: () => ({ size: document.getElementById('iconSize').value, shape: document.getElementById('iconShape').value })
            }).then((result) => {
                if (result.isConfirmed) { iconCircle.style.width = result.value.size + 'px'; iconCircle.style.height = result.value.size + 'px'; iconCircle.style.borderRadius = result.value.shape; registrarMudanca(); }
            });
        }
    });

    if(btnAddTexto) {
        btnAddTexto.addEventListener('click', () => {
            const div = document.createElement('div');
            div.className = 'elemento-livre bloco-texto-livre';
            div.setAttribute('data-id', 'novo_texto_' + Math.floor(Math.random() * 10000));
            div.setAttribute('data-tipo', 'texto'); div.setAttribute('data-x', '30'); div.setAttribute('data-y', '50'); div.setAttribute('data-w', '350'); div.setAttribute('data-h', '100');
            div.style.backgroundColor = 'rgba(255, 255, 255, 0.85)'; div.style.boxShadow = '0 4px 10px rgba(0,0,0,0.15)'; 
            div.innerHTML = `<button type="button" class="btn-delete-element" title="Excluir"><i class="fas fa-trash"></i></button><button type="button" class="btn-bg-texto" title="Alterar Fundo"><i class="fas fa-fill-drip"></i></button><div class="resize-handle"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div><div class="texto-editavel"><h2>Novo Texto</h2></div>`;
            canvas.appendChild(div); initElemento(div); registrarMudanca();
        });
    }

    // BOTÃO LARANJA: CADEADO DO FUNDO DE IMAGEM
    if (btnAjustarFundo) {
        btnAjustarFundo.addEventListener('click', () => {
            const fundoEl = document.getElementById('blocoFundoImagem');
            if (!fundoEl || fundoEl.style.display === 'none') {
                Swal.fire('Atenção', 'O fundo atual não é uma imagem. Envie uma imagem primeiro na Paleta de Cores!', 'warning');
                return;
            }
            if (fundoEl.classList.contains('travado')) {
                fundoEl.classList.remove('travado');
                btnAjustarFundo.innerHTML = '<i class="fas fa-lock-open"></i>';
                btnAjustarFundo.classList.replace('btn-adjust-bg', 'btn-success-bg');
                Toast.fire({ icon: 'info', title: 'Fundo Desbloqueado! Arraste as setas verdes para o redimensionar.' });
            } else {
                fundoEl.classList.add('travado');
                btnAjustarFundo.innerHTML = '<i class="fas fa-lock"></i>';
                btnAjustarFundo.classList.replace('btn-success-bg', 'btn-adjust-bg');
                Toast.fire({ icon: 'success', title: 'Fundo Bloqueado no lugar!' });
            }
        });
    }

    function capturarEstado() {
        const itens = [];
        document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
            const imgEl = el.querySelector('.img-alvo'); const txtEl = el.querySelector('.texto-editavel'); const iconCircle = el.querySelector('.icon-circle');
            itens.push({
                id: el.getAttribute('data-id'), tipo: el.getAttribute('data-tipo'), x: el.getAttribute('data-x'), y: el.getAttribute('data-y'), w: el.getAttribute('data-w'), h: el.getAttribute('data-h'),
                texto: txtEl ? txtEl.innerHTML : null, imgSrc: imgEl ? imgEl.src : null, imgRaw: imgEl ? imgEl.getAttribute('data-raw-src') : null,
                iconShape: iconCircle ? iconCircle.style.borderRadius : null, iconSize: iconCircle ? iconCircle.style.width : null,
                bgCor: el.style.backgroundColor, boxShadow: el.style.boxShadow, htmlCompleto: el.outerHTML
            });
        });
        return JSON.stringify({ fundoTipo: canvas.getAttribute('data-fundo-tipo') || 'css', fundoValor: canvas.getAttribute('data-fundo-valor') || '', elementos: itens });
    }

    function registrarMudanca() {
        const novoEstado = capturarEstado();
        if (passoAtual >= 0 && historico[passoAtual] === novoEstado) return;
        historico = historico.slice(0, passoAtual + 1); historico.push(novoEstado); passoAtual++; atualizarBotoesHistorico();
    }

    function restaurarEstado(jsonEstado) {
        const estado = JSON.parse(jsonEstado);
        if (estado.fundoTipo === 'css' && estado.fundoValor) { canvas.style.setProperty('background', estado.fundoValor, 'important'); } 
        else { canvas.style.setProperty('background', 'transparent', 'important'); }
        canvas.setAttribute('data-fundo-tipo', estado.fundoTipo); canvas.setAttribute('data-fundo-valor', estado.fundoValor);

        document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => el.remove());
        estado.elementos.forEach(item => {
            canvas.insertAdjacentHTML('beforeend', item.htmlCompleto);
            const el = canvas.lastElementChild;
            if (item.bgCor) el.style.backgroundColor = item.bgCor;
            if (item.boxShadow) el.style.boxShadow = item.boxShadow; 
            initElemento(el);
        });
        atualizarBotoesHistorico();
    }

    function atualizarBotoesHistorico() { btnUndo.disabled = (passoAtual <= 0); btnRedo.disabled = (passoAtual >= historico.length - 1); }
    btnUndo.addEventListener('click', () => { if (passoAtual > 0) { passoAtual--; restaurarEstado(historico[passoAtual]); } });
    btnRedo.addEventListener('click', () => { if (passoAtual < historico.length - 1) { passoAtual++; restaurarEstado(historico[passoAtual]); } });
    document.addEventListener('keydown', e => { if (!modoEdicao) return; if (e.ctrlKey && e.key.toLowerCase() === 'z') { e.preventDefault(); btnUndo.click(); } else if (e.ctrlKey && e.key.toLowerCase() === 'y') { e.preventDefault(); btnRedo.click(); } });

    function desativarEdicao() {
        modoEdicao = false;
        canvas.classList.remove('modo-edicao-ativo');
        btnToggle.innerHTML = '<i class="fas fa-pencil-alt"></i>'; btnToggle.style.background = '#ffffff'; btnToggle.style.color = '#333';
        btnSalvar.style.display = 'none'; btnAdd.style.display = 'none'; if (btnAddTexto) btnAddTexto.style.display = 'none';
        if (btnTrocarFundo) btnTrocarFundo.style.display = 'none';
        if (btnAjustarFundo) btnAjustarFundo.style.display = 'none';
        
        // Tranca o fundo automaticamente ao fechar o editor
        const fundoEl = document.getElementById('blocoFundoImagem');
        if(fundoEl) fundoEl.classList.add('travado');
        if(btnAjustarFundo) { btnAjustarFundo.innerHTML = '<i class="fas fa-lock"></i>'; btnAjustarFundo.classList.replace('btn-success-bg', 'btn-adjust-bg'); }
    }

    btnToggle.addEventListener('click', () => {
        modoEdicao = !modoEdicao;
        if (modoEdicao) {
            canvas.classList.add('modo-edicao-ativo');
            btnToggle.innerHTML = '<i class="fas fa-times"></i>'; btnToggle.style.background = '#dc3545'; btnToggle.style.color = '#fff';
            btnSalvar.style.display = 'flex'; btnAdd.style.display = 'flex'; 
            if (btnAddTexto) btnAddTexto.style.display = 'flex';
            if (btnTrocarFundo) btnTrocarFundo.style.display = 'flex';
            if (btnAjustarFundo) btnAjustarFundo.style.display = 'flex'; // Exibe botão laranja
            if (historico.length === 0) registrarMudanca();
            Toast.fire({ icon: 'info', title: 'Modo Edição Ativado!' });
        } else { desativarEdicao(); }
    });

    function inicializarSummernote() {
        if (window.jQuery && window.jQuery.fn.summernote) {
            window.jQuery('#summernoteTexto').summernote({ height: 250, toolbar: [ ['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['fontname', ['fontname']], ['color', ['color']], ['para', ['ul', 'ol', 'paragraph', 'height']], ['insert', ['link']], ['view', ['fullscreen', 'codeview']] ] });
        } else { setTimeout(inicializarSummernote, 500); }
    }
    inicializarSummernote();

    const btnSalvarTextoFormato = document.getElementById('btnSalvarTextoFormato');
    if (btnSalvarTextoFormato) {
        btnSalvarTextoFormato.addEventListener('click', function() {
            if (textoSendoEditado && window.jQuery) {
                textoSendoEditado.innerHTML = window.jQuery('#summernoteTexto').summernote('code');
                registrarMudanca(); Toast.fire({ icon: 'success', title: 'Texto atualizado!' }); window.jQuery('#modalEdicaoTexto').modal('hide');
            }
        });
    }

    const selectDestino = document.getElementById('selectPaginaDestino');
    if (selectDestino) {
        selectDestino.addEventListener('change', function() {
            if (this.value === 'nova') { document.getElementById('divNovaPagina').style.display = 'block'; document.getElementById('inputNovaPagina').setAttribute('required', 'required'); } 
            else { document.getElementById('divNovaPagina').style.display = 'none'; document.getElementById('inputNovaPagina').removeAttribute('required'); document.getElementById('inputNovaPagina').value = ''; }
        });
    }

    if (btnTrocarFundo && inputUploadFundo) {
        btnTrocarFundo.addEventListener('click', () => {
            Swal.fire({
                title: 'Personalizar Fundo',
                html: `<p style="margin-bottom: 20px;">O que deseja colocar no fundo do ecrã?</p><div style="display: flex; justify-content: center; gap: 15px;"><label for="inputUploadFundo" class="btn btn-primary" style="cursor: pointer; padding: 10px 20px; font-weight: bold; margin: 0;" onclick="Swal.close()"><i class="fas fa-image"></i> Imagem</label><button type="button" class="btn btn-info" id="btnSwalGradiente" style="padding: 10px 20px; font-weight: bold; color: #fff; margin: 0;"><i class="fas fa-palette"></i> Gradiente</button></div>`,
                showConfirmButton: false, showCancelButton: true, cancelButtonText: 'Cancelar',
                didOpen: () => {
                    document.getElementById('btnSwalGradiente').addEventListener('click', () => {
                        Swal.close();
                        Swal.fire({
                            title: 'Montar Gradiente',
                            html: `<div style="display:flex;justify-content:space-around;margin-top:10px;"><div><label style="font-size:12px;">Cor 1</label><br><input type="color" id="corBg1" value="#ff7a00" style="width: 50px; height: 40px; border: none;"></div><div><label style="font-size:12px;">Cor 2</label><br><input type="color" id="corBg2" value="#ffc107" style="width: 50px; height: 40px; border: none;"></div><div><label style="font-size:12px;">Cor 3</label><br><input type="color" id="corBg3" value="#8bc34a" style="width: 50px; height: 40px; border: none;"></div><div><label style="font-size:12px;">Cor 4</label><br><input type="color" id="corBg4" value="#03a9f4" style="width: 50px; height: 40px; border: none;"></div></div>`,
                            showCancelButton: true, confirmButtonText: 'Aplicar',
                            preConfirm: () => ({ cor1: document.getElementById('corBg1').value, cor2: document.getElementById('corBg2').value, cor3: document.getElementById('corBg3').value, cor4: document.getElementById('corBg4').value })
                        }).then((r) => {
                            if (r.isConfirmed) {
                                const gradiente = `linear-gradient(135deg, ${r.value.cor1} 0%, ${r.value.cor2} 33%, ${r.value.cor3} 66%, ${r.value.cor4} 100%)`;
                                canvas.style.setProperty('background', gradiente, 'important');
                                canvas.setAttribute('data-fundo-tipo', 'css'); canvas.setAttribute('data-fundo-valor', gradiente);
                                const fundoEl = document.getElementById('blocoFundoImagem'); if(fundoEl) fundoEl.style.display = 'none';
                                registrarMudanca();
                            }
                        });
                    });
                }
            });
        });

        inputUploadFundo.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            const formData = new FormData(); formData.append('imagem', this.files[0]); formData.append('_token', window.TotemEditor.csrfToken);
            Toast.fire({ icon: 'info', title: 'Carregando plano de fundo...' });
            
            fetch(window.TotemEditor.routes.upload, { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.success) {
                    canvas.style.setProperty('background', 'transparent', 'important');
                    canvas.setAttribute('data-fundo-tipo', 'imagem'); canvas.setAttribute('data-fundo-valor', data.caminho_relativo);
                    
                    let fundoEl = document.getElementById('blocoFundoImagem');
                    fundoEl.style.display = 'flex';
                    fundoEl.style.width = '100%'; fundoEl.style.height = '100%'; fundoEl.style.left = '0%'; fundoEl.style.top = '0%';
                    fundoEl.setAttribute('data-w', ''); fundoEl.setAttribute('data-h', ''); fundoEl.setAttribute('data-x', '0'); fundoEl.setAttribute('data-y', '0');
                    
                    const img = fundoEl.querySelector('.img-alvo');
                    img.src = data.url_completa; img.setAttribute('data-raw-src', data.caminho_relativo);
                    
                    // Desbloqueia automaticamente
                    fundoEl.classList.remove('travado');
                    btnAjustarFundo.innerHTML = '<i class="fas fa-lock-open"></i>'; btnAjustarFundo.classList.replace('btn-adjust-bg', 'btn-success-bg');
                    
                    registrarMudanca(); Toast.fire({ icon: 'success', title: 'Fundo inserido! Ajuste o tamanho nas setas verdes.' });
                } else { Toast.fire({ icon: 'error', title: 'Erro: ' + (data.message || 'Falha.') }); }
                this.value = '';
            }).catch(() => Toast.fire({ icon: 'error', title: 'Erro de rede.' }));
        });
    }

    inputUpload.addEventListener('change', function() {
        if (!this.files || !this.files[0] || !imagemSendoEditada) return;
        const formData = new FormData(); formData.append('imagem', this.files[0]); formData.append('_token', window.TotemEditor.csrfToken);
        Toast.fire({ icon: 'info', title: 'A enviar imagem...' });
        fetch(window.TotemEditor.routes.upload, { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if (data.success) {
                imagemSendoEditada.src = data.url_completa; imagemSendoEditada.setAttribute('data-raw-src', data.caminho_relativo);
                registrarMudanca(); Toast.fire({ icon: 'success', title: 'Imagem atualizada!' });
            } else { Toast.fire({ icon: 'error', title: 'Erro: ' + (data.message || 'Falha ao enviar.') }); }
            this.value = '';
        }).catch(() => Toast.fire({ icon: 'error', title: 'Erro de ligação.' }));
    });

    btnSalvar.addEventListener('click', () => {
        const blocosAtualizados = [];
        btnSalvar.disabled = true; btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        document.querySelectorAll('#canvasTotem .elemento-livre').forEach((el, index) => {
            if(el.id === 'blocoFundoImagem') return; // O Fundo salva num bloco separado

            const imgEl = el.querySelector('.img-alvo'); const txtEl = el.querySelector('.texto-editavel'); const iconCircle = el.querySelector('.icon-circle');
            let formatoIcone = '50%'; let tamanhoIcone = 65;
            if (iconCircle) { formatoIcone = iconCircle.style.borderRadius || '50%'; tamanhoIcone = parseInt(iconCircle.style.width) || 65; }
            
            let textoBloco = txtEl ? txtEl.innerHTML.trim() : 'Logo';
            if (el.getAttribute('data-tipo') === 'texto' || el.getAttribute('data-tipo') === 'titulo_pagina') textoBloco = txtEl.innerHTML;
            let corFundo = el.style.backgroundColor; if (corFundo === 'rgba(0, 0, 0, 0)' || corFundo === '') corFundo = 'transparent';

            blocosAtualizados.push({
                id: el.getAttribute('data-id'), tipo: el.getAttribute('data-tipo'), ordem: index + 1,
                dados_conteudo: {
                    texto: textoBloco, titulo: textoBloco, icone: imgEl ? imgEl.getAttribute('data-raw-src') : 'totem/img/botoes/BTN_Historia.png',
                    pagina_destino_id: el.getAttribute('data-destino') || 1, pos_x: parseFloat(el.getAttribute('data-x')) || 10, pos_y: parseFloat(el.getAttribute('data-y')) || 20,
                    largura: parseFloat(el.getAttribute('data-w')) || el.offsetWidth, altura: parseFloat(el.getAttribute('data-h')) || el.offsetHeight,
                    formato_icone: formatoIcone, tamanho_icone: tamanhoIcone, cor_fundo: corFundo 
                }
            });
        });

        const fTipo = canvas.getAttribute('data-fundo-tipo');
        const fValor = canvas.getAttribute('data-fundo-valor');
        if (fTipo === 'imagem') {
            const fundoEl = document.getElementById('blocoFundoImagem');
            blocosAtualizados.push({ id: canvas.getAttribute('data-fundo-id') || 'novo_fundo', tipo: 'fundo', ordem: 0, dados_conteudo: { tipo_fundo: 'imagem', valor_fundo: fValor, pos_x: parseFloat(fundoEl.getAttribute('data-x')) || 0, pos_y: parseFloat(fundoEl.getAttribute('data-y')) || 0, largura: parseFloat(fundoEl.getAttribute('data-w')) || fundoEl.offsetWidth, altura: parseFloat(fundoEl.getAttribute('data-h')) || fundoEl.offsetHeight } });
        } else if (fTipo === 'css' && fValor) {
            blocosAtualizados.push({ id: canvas.getAttribute('data-fundo-id') || 'novo_fundo', tipo: 'fundo', ordem: 0, dados_conteudo: { tipo_fundo: 'css', valor_fundo: fValor } });
        }

        fetch(window.TotemEditor.routes.salvar, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.TotemEditor.csrfToken },
            body: JSON.stringify({ pagina_id: canvas.getAttribute('data-pagina-id'), blocos: blocosAtualizados })
        }).then(async res => {
            const data = await res.json(); if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao guardar.'); return data;
        }).then(() => {
            desativarEdicao(); Swal.fire({ title: 'Guardado!', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => window.location.reload());
        }).catch(err => {
            btnSalvar.disabled = false; btnSalvar.innerHTML = '<i class="fas fa-check"></i>'; Swal.fire({ icon: 'error', title: 'Ups...', text: err.message });
        });
    });

    if (btnPublicar) {
        btnPublicar.addEventListener('click', () => {
            Swal.fire({ title: 'Sincronizar?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#6f42c1', confirmButtonText: 'Sincronizar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const icone = btnPublicar.innerHTML; btnPublicar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btnPublicar.disabled = true;
                    fetch(window.TotemEditor.routes.publicar, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.TotemEditor.csrfToken } }).then(res => res.json()).then(data => { btnPublicar.innerHTML = icone; btnPublicar.disabled = false; if (data.success) Swal.fire('Publicado!', data.message, 'success'); else Swal.fire('Falha', data.message, 'error'); });
                }
            });
        });
    }
});