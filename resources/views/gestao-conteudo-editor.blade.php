@extends('layouts.template')

@section('title', 'Editando: ' . $pagina->pagina_titulo)

@section('content')
    <style>
        /* Wrapper escuro ao redor da tela do Totem */
        .editor-workspace {
            position: relative !important;
            background: #22252a !important;
            padding: 20px;
            border-radius: 6px;
        }

        /* O Canvas da Subpágina (Template Padrão editável) */
        #canvasTotem {
            position: relative !important;
            background: #ffffff !important;
            height: calc(100vh - 160px) !important;
            min-height: 540px !important;
            width: 100% !important;
            max-width: 1100px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            overflow-y: auto !important;
            overflow-x: hidden !important;
            user-select: none !important;
            -webkit-user-drag: none !important;
        }

        /* Botão fixo de voltar para a Home */
        .btn-voltar-home {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 50;
            background: #333;
            color: #fff !important;
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none !important;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }
        .btn-voltar-home:hover { background: #ff7a00; }

        /* Elementos livres na tela de conteúdo */
        #canvasTotem .elemento-livre {
            position: absolute !important;
            margin: 0 !important;
            transform: none !important;
            z-index: 10;
            touch-action: none;
        }

        /* Estilo do Título Livre */
        .bloco-titulo-pagina {
            width: 70%;
            text-align: center;
        }
        .bloco-titulo-pagina h2 {
            color: #ff7a00;
            font-weight: 900;
            font-size: 28px;
            margin: 0;
        }

        /* Estilo do Bloco de Texto Livre */
        .bloco-texto-totem {
            width: 80%;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 8px;
        }
        .bloco-texto-totem .texto-editavel {
            font-size: 1.15rem;
            line-height: 1.6;
            text-align: justify;
            color: #333;
        }

        /* Estilo do Bloco de Imagem Livre */
        .bloco-imagem-totem {
            width: 320px;
            text-align: center;
        }
        .bloco-imagem-totem img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            pointer-events: none !important;
            -webkit-user-drag: none !important;
        }

        /* Controles Flutuantes Direitos */
        .floating-controls {
            position: absolute !important;
            top: 30px !important;
            right: 35px !important;
            z-index: 9999 !important;
            display: flex !important;
            gap: 12px;
        }

        /* Controles Flutuantes Esquerdos (Undo / Redo) */
        .floating-controls-left {
            position: absolute !important;
            top: 30px !important;
            left: 35px !important;
            z-index: 9999 !important;
            display: none;
            gap: 12px;
        }
        .modo-edicao-ativo ~ .floating-controls-left,
        .workspace-edicao-ativa .floating-controls-left {
            display: flex !important;
        }

        .btn-floating {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #333;
            background: #ffffff;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.35);
            transition: transform 0.2s;
        }
        .btn-floating:hover:not(:disabled) { transform: scale(1.1); }
        .btn-floating:disabled { opacity: 0.45; cursor: not-allowed; }

        .btn-save-mode { background: #28a745 !important; color: #fff !important; display: none; }
        .btn-add-block { background: #17a2b8 !important; color: #fff !important; display: none; }

        /* Quando o Modo Edição está Ativo */
        .modo-edicao-ativo .elemento-livre {
            outline: 3px dashed #007bff !important;
            cursor: grab !important;
        }
        .modo-edicao-ativo .elemento-livre:active {
            cursor: grabbing !important;
            outline: 3px solid #ff7a00 !important;
            z-index: 1000 !important;
        }

        /* Botão de Câmera para Trocar Imagem */
        .btn-trocar-img {
            display: none;
            position: absolute;
            top: -12px;
            right: -12px;
            background: #ff7a00;
            color: #fff;
            border: 2px solid #fff;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
            z-index: 30;
        }
        .modo-edicao-ativo .btn-trocar-img { display: flex !important; }

        /* Texto Editável no Modo Edição */
        .modo-edicao-ativo .texto-editavel[contenteditable="true"] {
            outline: 2px dashed #ff7a00 !important;
            padding: 6px;
            border-radius: 4px;
            background: #fffdf5 !important;
            cursor: text !important;
        }
    </style>

    <?php
        $blocoTitulo = null;
        $blocosConteudo = [];

        if (isset($pagina) &&$pagina->conteudos) {
            $blocoTitulo =$pagina->conteudos->where('conteudo_tipo_componente', 'titulo_pagina')->first();
            $blocosConteudo =$pagina->conteudos->where('conteudo_tipo_componente', '!=', 'titulo_pagina')->all();
        }

        $dadosTitulo = [];
        if ($blocoTitulo) {
            $rawTit =$blocoTitulo->conteudo_dados_conteudo;
            $dadosTitulo = is_string($rawTit) ? (json_decode($rawTit, true) ?: []) : (is_array($rawTit) ?$rawTit : []);
        }

        $tituloX = (isset($dadosTitulo['pos_x']) && floatval($dadosTitulo['pos_x']) > 0) ? number_format(floatval($dadosTitulo['pos_x']), 2, '.', '') : '15.00';$tituloY = (isset($dadosTitulo['pos_y']) && floatval($dadosTitulo['pos_y']) > 0) ? number_format(floatval($dadosTitulo['pos_y']), 2, '.', '') : '6.00';$contadorBloco = 0;
    ?>

    <div class="card mb-0">
        <div class="card-body editor-workspace" id="workspaceWrapper">
            
            <input type="file" id="inputUploadImagem" accept="image/*" style="display: none;">

            {{-- Botões Undo / Redo (Esquerda) --}}
            <div class="floating-controls-left">
                <button type="button" class="btn-floating" id="btnUndo" title="Desfazer (Ctrl+Z)" disabled>
                    <i class="fas fa-undo"></i>
                </button>
                <button type="button" class="btn-floating" id="btnRedo" title="Refazer (Ctrl+Y)" disabled>
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            {{-- Botões de Controle (Direita) --}}
            <div class="floating-controls">
                <button type="button" class="btn-floating btn-add-block" id="btnAddBloco" data-toggle="modal" data-target="#modalNovoBloco" title="Adicionar Texto ou Imagem">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn-floating btn-save-mode" id="btnSalvarCanvas" title="Salvar Alterações">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="btn-floating" id="btnToggleEdicao" title="Editar Página">
                    <i class="fas fa-pencil-alt"></i>
                </button>
            </div>

            {{-- TELA DO TOTEM (CANVAS LIVRE) --}}
            <div id="canvasTotem" data-pagina-id="{{ $pagina->id }}">
                
                <a href="{{ route('gestao-conteudo.index') }}" class="btn-voltar-home" id="btnVoltarMenu">
                    <i class="fas fa-arrow-left"></i> Voltar ao Menu
                </a>

                {{-- 1. TÍTULO DA PÁGINA (Arrastável e Editável) --}}
                <div class="elemento-livre bloco-titulo-pagina"
                     data-id="{{ $blocoTitulo->id ?? 'novo_titulo' }}"
                     data-tipo="titulo_pagina"
                     data-x="{{ $tituloX }}"
                     data-y="{{ $tituloY }}"
                     style="left: {{ $tituloX }}%; top: {{$tituloY }}%;">
                    <h2 class="texto-editavel" id="tituloPrincipalTexto">{{ mb_strtoupper($pagina->pagina_titulo) }}</h2>
                </div>

                {{-- 2. BLOCOS DE TEXTO E IMAGEM DO BANCO --}}
                <?php foreach ($blocosConteudo as$bloco): ?>
                    <?php
                        $raw =$bloco->conteudo_dados_conteudo;
                        $dados = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ?$raw : []);

                        $defaultX = 10;
                        $defaultY = 20 + ($contadorBloco * 22);$contadorBloco++;

                        $valX = (isset($dados['pos_x']) && floatval($dados['pos_x']) > 0) ? floatval($dados['pos_x']) :$defaultX;
                        $valY = (isset($dados['pos_y']) && floatval($dados['pos_y']) > 0) ? floatval($dados['pos_y']) :$defaultY;

                        $posX = number_format($valX, 2, '.', '');
                        $posY = number_format($valY, 2, '.', '');
                        $tipoComp =$bloco->conteudo_tipo_componente;
                    ?>

                    <?php if ($tipoComp === 'texto'): ?>
                        <div class="elemento-livre bloco-texto-totem"
                             data-id="{{ $bloco->id }}"
                             data-tipo="texto"
                             data-x="{{ $posX }}"
                             data-y="{{ $posY }}"
                             style="left: {{ $posX }}%; top: {{$posY }}%;">
                            <div class="texto-editavel">{!! nl2br(e($dados['texto'] ?? 'Digite seu texto aqui...')) !!}</div>
                        </div>

                    <?php elseif ($tipoComp === 'imagem'): ?>
                        <?php $imgUrl = $dados['url'] ?? ($dados['icone'] ?? 'totem/img/logo-vertical-longa.png'); ?>
                        <div class="elemento-livre bloco-imagem-totem"
                             data-id="{{ $bloco->id }}"
                             data-tipo="imagem"
                             data-x="{{ $posX }}"
                             data-y="{{ $posY }}"
                             style="left: {{ $posX }}%; top: {{$posY }}%;">
                            
                            <button type="button" class="btn-trocar-img" title="Trocar Imagem">
                                <i class="fas fa-camera"></i>
                            </button>

                            <img src="{{ asset($imgUrl) }}" 
                                 data-raw-src="{{ $imgUrl }}" 
                                 class="img-alvo" 
                                 draggable="false" 
                                 alt="Imagem Conteúdo">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    {{-- Modal para Adicionar Novo Texto ou Imagem na Subpágina --}}
    <div class="modal fade" id="modalNovoBloco" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content text-dark">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Adicionar Conteúdo na Página</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('conteudos.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="pagina_id" value="{{ $pagina->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>O que você deseja adicionar?</label>
                            <select name="tipo_componente" id="selectTipoBloco" class="form-control" required>
                                <option value="texto">Parágrafo / Caixa de Texto</option>
                                <option value="imagem">Foto / Imagem</option>
                            </select>
                        </div>

                        <div class="form-group" id="campoTextoInicial">
                            <label>Texto Inicial</label>
                            <textarea name="texto" class="form-control" rows="4" placeholder="Digite o texto...">Novo parágrafo de conteúdo.</textarea>
                        </div>

                        <div class="form-group" id="campoImagemInicial" style="display: none;">
                            <label>Caminho da Imagem Inicial (Você poderá trocar clicando na câmera)</label>
                            <input type="text" name="icone" class="form-control" value="totem/img/logo-vertical-longa.png">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Inserir na Tela</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let modoEdicao = false;
        let imagemSendoEditada = null;

        const workspace = document.getElementById('workspaceWrapper');
        const canvas = document.getElementById('canvasTotem');
        const btnToggle = document.getElementById('btnToggleEdicao');
        const btnSalvar = document.getElementById('btnSalvarCanvas');
        const btnAdd = document.getElementById('btnAddBloco');
        const btnUndo = document.getElementById('btnUndo');
        const btnRedo = document.getElementById('btnRedo');
        const inputUpload = document.getElementById('inputUploadImagem');
        const selectTipoBloco = document.getElementById('selectTipoBloco');

        // Alterna campos no Modal entre Texto e Imagem
        selectTipoBloco.addEventListener('change', function() {
            if (this.value === 'texto') {
                document.getElementById('campoTextoInicial').style.display = 'block';
                document.getElementById('campoImagemInicial').style.display = 'none';
            } else {
                document.getElementById('campoTextoInicial').style.display = 'none';
                document.getElementById('campoImagemInicial').style.display = 'block';
            }
        });

        let historico = [];
        let passoAtual = -1;

        // Posiciona todos os elementos usando data-x e data-y
        document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
            el.style.left = el.getAttribute('data-x') + '%';
            el.style.top = el.getAttribute('data-y') + '%';
        });

        function capturarEstado() {
            const estado = [];
            document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
                const imgEl = el.querySelector('.img-alvo');
                const txtEl = el.querySelector('.texto-editavel');
                estado.push({
                    id: el.getAttribute('data-id'),
                    x: el.getAttribute('data-x'),
                    y: el.getAttribute('data-y'),
                    texto: txtEl ? txtEl.innerHTML : null,
                    imgSrc: imgEl ? imgEl.src : null,
                    imgRaw: imgEl ? imgEl.getAttribute('data-raw-src') : null
                });
            });
            return JSON.stringify(estado);
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
            const itens = JSON.parse(jsonEstado);
            itens.forEach(item => {
                const el = document.querySelector(`#canvasTotem .elemento-livre[data-id="${item.id}"]`);
                if (el) {
                    el.setAttribute('data-x', item.x);
                    el.setAttribute('data-y', item.y);
                    el.style.left = item.x + '%';
                    el.style.top = item.y + '%';

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

        btnUndo.addEventListener('click', () => {
            if (passoAtual > 0) {
                passoAtual--;
                restaurarEstado(historico[passoAtual]);
            }
        });

        btnRedo.addEventListener('click', () => {
            if (passoAtual < historico.length - 1) {
                passoAtual++;
                restaurarEstado(historico[passoAtual]);
            }
        });

        document.addEventListener('keydown', e => {
            if (!modoEdicao) return;
            if (e.ctrlKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                btnUndo.click();
            } else if (e.ctrlKey && e.key.toLowerCase() === 'y') {
                e.preventDefault();
                btnRedo.click();
            }
        });

        // Liga / Desliga Modo Edição
        btnToggle.addEventListener('click', () => {
            modoEdicao = !modoEdicao;

            if (modoEdicao) {
                canvas.classList.add('modo-edicao-ativo');
                workspace.classList.add('workspace-edicao-ativa');
                btnToggle.innerHTML = '<i class="fas fa-times"></i>';
                btnToggle.style.background = '#dc3545';
                btnToggle.style.color = '#fff';
                btnSalvar.style.display = 'flex';
                btnAdd.style.display = 'flex';
                document.getElementById('btnVoltarMenu').style.display = 'none'; // Esconde botão voltar durante edição

                document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => {
                    el.setAttribute('contenteditable', 'true');
                });

                if (historico.length === 0) {
                    registrarMudanca();
                }
            } else {
                desativarEdicao();
            }
        });

        function desativarEdicao() {
            modoEdicao = false;
            canvas.classList.remove('modo-edicao-ativo');
            workspace.classList.remove('workspace-edicao-ativa');
            btnToggle.innerHTML = '<i class="fas fa-pencil-alt"></i>';
            btnToggle.style.background = '#ffffff';
            btnToggle.style.color = '#333';
            btnSalvar.style.display = 'none';
            btnAdd.style.display = 'none';
            document.getElementById('btnVoltarMenu').style.display = 'inline-block';

            document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => {
                el.removeAttribute('contenteditable');
            });
        }

        document.querySelectorAll('#canvasTotem .texto-editavel').forEach(el => {
            el.addEventListener('blur', () => {
                if (modoEdicao) registrarMudanca();
            });
        });

        // Arrastar livremente pelo Canvas
        document.querySelectorAll('#canvasTotem .elemento-livre').forEach(el => {
            el.addEventListener('mousedown', function(e) {
                if (!modoEdicao) return;
                // Se clicou na câmera ou deu duplo clique para digitar texto, permite interagir
                if (e.target.closest('.btn-trocar-img') || document.activeElement === e.target) return;

                let moveu = false;
                const rectCanvas = canvas.getBoundingClientRect();
                const rectEl = el.getBoundingClientRect();
                const shiftX = e.clientX - rectEl.left;
                const shiftY = e.clientY - rectEl.top;

                function onMouseMove(event) {
                    moveu = true;
                    let newLeft = ((event.clientX - rectCanvas.left - shiftX) / rectCanvas.width) * 100;
                    let newTop = ((event.clientY - rectCanvas.top - shiftY) / rectCanvas.height) * 100;

                    newLeft = Math.max(1, Math.min(newLeft, 85));
                    newTop = Math.max(1, Math.min(newTop, 88));

                    const strX = newLeft.toFixed(2);
                    const strY = newTop.toFixed(2);

                    el.setAttribute('data-x', strX);
                    el.setAttribute('data-y', strY);
                    el.style.left = strX + '%';
                    el.style.top = strY + '%';
                }

                function onMouseUp() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    if (moveu) {
                        registrarMudanca();
                    }
                }

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });

        // Upload de Imagem
        document.querySelectorAll('#canvasTotem .btn-trocar-img').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                imagemSendoEditada = this.closest('.elemento-livre').querySelector('.img-alvo');
                inputUpload.click();
            });
        });

        inputUpload.addEventListener('change', function() {
            if (!this.files || !this.files[0] || !imagemSendoEditada) return;

            const formData = new FormData();
            formData.append('imagem', this.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("conteudos.upload-imagem") }}', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    imagemSendoEditada.src = data.url_completa;
                    imagemSendoEditada.setAttribute('data-raw-src', data.caminho_relativo);
                    registrarMudanca();
                } else {
                    alert('Erro ao enviar imagem.');
                }
            })
            .catch(() => alert('Erro no upload da imagem.'));
        });

        // Salvar Posições, Textos e Imagens no Banco
        btnSalvar.addEventListener('click', () => {
            const blocosAtualizados = [];
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const tituloPaginaTxt = document.getElementById('tituloPrincipalTexto').innerText.trim();

            document.querySelectorAll('#canvasTotem .elemento-livre').forEach((el, index) => {
                const id = el.getAttribute('data-id');
                const tipo = el.getAttribute('data-tipo');
                const posX = parseFloat(el.getAttribute('data-x')) || 10;
                const posY = parseFloat(el.getAttribute('data-y')) || 15;

                const imgEl = el.querySelector('.img-alvo');
                const txtEl = el.querySelector('.texto-editavel');

                let dados = { pos_x: posX, pos_y: posY };

                if (tipo === 'texto' || tipo === 'titulo_pagina') {
                    dados.texto = txtEl ? txtEl.innerText.trim() : '';
                } else if (tipo === 'imagem') {
                    dados.url = imgEl ? imgEl.getAttribute('data-raw-src') : '';
                }

                blocosAtualizados.push({
                    id: id,
                    tipo: tipo,
                    ordem: index + 1,
                    dados_conteudo: dados
                });
            });

            fetch('{{ route("conteudos.salvar-em-massa") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    pagina_id: canvas.getAttribute('data-pagina-id'),
                    pagina_titulo: tituloPaginaTxt,
                    blocos: blocosAtualizados
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Erro ao salvar.');
                }
                return data;
            })
            .then(() => {
                desativarEdicao();
                window.location.reload();
            })
            .catch(err => {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="fas fa-check"></i>';
                alert('Erro ao salvar: ' + err.message);
            });
        });
    });
    </script>
@stop