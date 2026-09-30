@extends('layouts.template')

@section('title', 'Espelho do Totem - Editor de Página')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
@stop

@section('content')
<link rel="stylesheet" href="{{ asset('totem/css/style.css') }}">
@vite(['resources/css/editor.css', 'resources/js/editor.js'])
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$paginaAtual = $pagina ?? $paginaPrincipal ?? null;

$blocoLogo = null;
$blocoTitulo = null;
$blocoFundo = null;
$botoesNavegacao = [];
$listaPaginas = isset($todasPaginas) ? $todasPaginas : [];

if ($paginaAtual && $paginaAtual->conteudos) {
    $blocoLogo = $paginaAtual->conteudos->where('conteudo_tipo_componente', 'logo')->first();
    $blocoFundo = $paginaAtual->conteudos->where('conteudo_tipo_componente', 'fundo')->first();
    $blocoTitulo = $paginaAtual->conteudos->where('conteudo_tipo_componente', 'titulo_pagina')->first();
    $botoesNavegacao = $paginaAtual->conteudos->whereNotIn('conteudo_tipo_componente', ['logo', 'fundo', 'titulo_pagina'])->all();
}

// Logo e Título
$dadosLogo = $blocoLogo ? (is_string($blocoLogo->conteudo_dados_conteudo) ? json_decode($blocoLogo->conteudo_dados_conteudo, true) : $blocoLogo->conteudo_dados_conteudo) : [];
$logoX = (isset($dadosLogo['pos_x']) && floatval($dadosLogo['pos_x']) > 0) ? number_format(floatval($dadosLogo['pos_x']), 2, '.', '') : '8.00';
$logoY = (isset($dadosLogo['pos_y']) && floatval($dadosLogo['pos_y']) > 0) ? number_format(floatval($dadosLogo['pos_y']), 2, '.', '') : '18.00';
$logoUrl = !empty($dadosLogo['icone']) ? $dadosLogo['icone'] : 'totem/img/logo-vertical-longa.png';
$logoLargura = (isset($dadosLogo['largura']) && floatval($dadosLogo['largura']) > 0) ? floatval($dadosLogo['largura']) : 240;
$logoAltura = (isset($dadosLogo['altura']) && floatval($dadosLogo['altura']) > 0) ? floatval($dadosLogo['altura']) . 'px' : 'auto';

$dadosTitulo = $blocoTitulo ? (is_string($blocoTitulo->conteudo_dados_conteudo) ? json_decode($blocoTitulo->conteudo_dados_conteudo, true) : $blocoTitulo->conteudo_dados_conteudo) : [];
$tituloX = (isset($dadosTitulo['pos_x']) && floatval($dadosTitulo['pos_x']) > 0) ? number_format(floatval($dadosTitulo['pos_x']), 2, '.', '') : '5.00'; 
$tituloY = (isset($dadosTitulo['pos_y']) && floatval($dadosTitulo['pos_y']) > 0) ? number_format(floatval($dadosTitulo['pos_y']), 2, '.', '') : '15.00'; 
$tituloLargura = (isset($dadosTitulo['largura']) && floatval($dadosTitulo['largura']) > 0) ? floatval($dadosTitulo['largura']) : 400; 
$tituloAltura = (isset($dadosTitulo['altura']) && floatval($dadosTitulo['altura']) > 0) ? floatval($dadosTitulo['altura']) . 'px' : 'auto';
$nomeDaPagina = $paginaAtual ? $paginaAtual->pagina_titulo : 'Título da Página';
$textoTitulo = isset($dadosTitulo['texto']) ? $dadosTitulo['texto'] : "<h1 style='text-align: center; color: #333333; font-weight: bold;'>{$nomeDaPagina}</h1>";
$corFundoTitulo = isset($dadosTitulo['cor_fundo']) ? $dadosTitulo['cor_fundo'] : 'transparent';
$sombraTitulo = ($corFundoTitulo === 'transparent' || $corFundoTitulo === 'rgba(0, 0, 0, 0)') ? 'none' : '0 4px 10px rgba(0,0,0,0.15)';

// Fundo - Lógica do "Cadeado"
$dadosFundo = $blocoFundo ? (is_string($blocoFundo->conteudo_dados_conteudo) ? json_decode($blocoFundo->conteudo_dados_conteudo, true) : $blocoFundo->conteudo_dados_conteudo) : [];
$fundoTipo = $dadosFundo['tipo_fundo'] ?? 'css'; 
$fundoValor = $dadosFundo['valor_fundo'] ?? '#ffffff'; 
$fundoX = (isset($dadosFundo['pos_x'])) ? number_format(floatval($dadosFundo['pos_x']), 2, '.', '') : '0.00';
$fundoY = (isset($dadosFundo['pos_y'])) ? number_format(floatval($dadosFundo['pos_y']), 2, '.', '') : '0.00';
$fundoW = (isset($dadosFundo['largura']) && floatval($dadosFundo['largura']) > 0) ? floatval($dadosFundo['largura']) : '';
$fundoH = (isset($dadosFundo['altura']) && floatval($dadosFundo['altura']) > 0) ? floatval($dadosFundo['altura']) : '';

$styleFundoCss = ($fundoTipo === 'css') ? "background: {$fundoValor} !important;" : "background: transparent !important;";

$contadorBtn = 0;
?>

<div class="card mb-0">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title text-primary"><i class="fas fa-desktop"></i> A Editar: <strong>{{ $paginaAtual->pagina_titulo ?? 'Nova Página' }}</strong></h3>
    </div>
    <div class="card-body p-0 totem-preview-wrapper" id="canvasTotem" data-pagina-id="{{ $paginaAtual->id ?? 1 }}"
         data-fundo-id="{{ $blocoFundo->id ?? 'novo_fundo' }}" data-fundo-tipo="{{ $fundoTipo }}" data-fundo-valor="{{ $fundoValor }}" style="{{ $styleFundoCss }}">

        <input type="file" id="inputUploadImagem" accept="image/*" style="display: none;">
        <input type="file" id="inputUploadFundo" accept="image/*" style="display: none;">

        <div class="floating-controls-left">
            <button type="button" class="btn-floating" id="btnUndo" disabled title="Desfazer (Ctrl+Z)"><i class="fas fa-undo"></i></button>
            <button type="button" class="btn-floating" id="btnRedo" disabled title="Refazer (Ctrl+Y)"><i class="fas fa-redo"></i></button>
        </div>

        <div class="floating-controls">
            <a href="{{ url()->previous() }}" class="btn-floating" title="Voltar à página anterior" style="background: #e9ecef; color: #333;"><i class="fas fa-arrow-left"></i></a>
            
            <button type="button" class="btn-floating btn-add-text" id="btnAddTextoLivre" title="Adicionar Texto Livre"><i class="fas fa-font"></i></button>
            
            {{-- BOTÃO LARANJA DE CADEADO PARA O FUNDO --}}
            <button type="button" class="btn-floating btn-adjust-bg" id="btnAjustarFundo" title="Bloquear / Desbloquear Fundo"><i class="fas fa-lock"></i></button>
            
            <button type="button" class="btn-floating btn-change-bg" id="btnTrocarFundo" title="Alterar Fundo (Gradiente ou Imagem)"><i class="fas fa-palette"></i></button>
            <button type="button" class="btn-floating btn-publish" id="btnPublicarTotem" title="Publicar nos Totens"><i class="fas fa-paper-plane"></i></button>
            <button type="button" class="btn-floating btn-add-block" id="btnAddBotao" data-toggle="modal" data-target="#modalEdicao" title="Adicionar Botão"><i class="fas fa-plus"></i></button>
            <button type="button" class="btn-floating btn-save-mode" id="btnSalvarCanvas" title="Salvar Posições"><i class="fas fa-check"></i></button>
            <button type="button" class="btn-floating" id="btnToggleEdicao" title="Editar Tela"><i class="fas fa-pencil-alt"></i></button>
        </div>

        {{-- O BLOCO DO FUNDO DE IMAGEM --}}
        <div class="elemento-livre fundo-imagem-livre travado" id="blocoFundoImagem"
             data-x="{{ $fundoX }}" data-y="{{ $fundoY }}" data-w="{{ $fundoW }}" data-h="{{ $fundoH }}"
             style="display: {{ $fundoTipo === 'imagem' ? 'flex' : 'none' }}; left: {{ $fundoX }}%; top: {{ $fundoY }}%; {{ $fundoW ? "width: {$fundoW}px;" : 'width: 100%;' }} {{ $fundoH ? "height: {$fundoH}px;" : 'height: 100%;' }}">
             
             <div class="resize-handle" title="Redimensionar Fundo"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
             <img src="{{ $fundoTipo === 'imagem' ? asset($fundoValor) : '' }}" data-raw-src="{{ $fundoTipo === 'imagem' ? $fundoValor : '' }}" class="img-alvo" draggable="false" style="width:100%; height:100%; object-fit:fill;">
        </div>

        @if($blocoLogo)
        <div class="elemento-livre logo-livre" data-id="{{ $blocoLogo->id }}" data-tipo="logo"
            data-x="{{ $logoX }}" data-y="{{ $logoY }}" data-w="{{ $logoLargura }}" data-h="{{ $logoAltura !== 'auto' ? floatval($dadosLogo['altura']) : '' }}"
            style="left: {{ $logoX }}%; top: {{ $logoY }}%; width: {{ $logoLargura }}px; height: {{ $logoAltura }};">
            <button type="button" class="btn-delete-element" title="Excluir Logo"><i class="fas fa-trash"></i></button>
            <button type="button" class="btn-trocar-img" title="Trocar Logo"><i class="fas fa-camera"></i></button>
            <div class="resize-handle"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
            <img src="{{ asset($logoUrl) }}" data-raw-src="{{ $logoUrl }}" class="img-alvo" draggable="false" alt="Logo">
        </div>
        @endif

        <div class="elemento-livre bloco-texto-livre" data-id="{{ $blocoTitulo->id ?? 'novo_titulo' }}" data-tipo="titulo_pagina"
            data-x="{{ $tituloX }}" data-y="{{ $tituloY }}" data-w="{{ $tituloLargura }}" data-h="{{ $tituloAltura !== 'auto' ? floatval($dadosTitulo['altura']) : '' }}"
            style="left: {{ $tituloX }}%; top: {{ $tituloY }}%; width: {{ $tituloLargura }}px; height: {{ $tituloAltura }}; background-color: {{ $corFundoTitulo }}; box-shadow: {{ $sombraTitulo }};">
            
            <button type="button" class="btn-delete-element" title="Excluir Título"><i class="fas fa-trash"></i></button>
            <button type="button" class="btn-bg-texto" title="Alterar Cor de Fundo"><i class="fas fa-fill-drip"></i></button>
            <div class="resize-handle"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
            <div class="texto-editavel">{!! $textoTitulo !!}</div>
        </div>

        <?php foreach ($botoesNavegacao as $bloco): ?>
        <?php
            $raw = $bloco->conteudo_dados_conteudo;
            $dados = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
            $tipoElemento = $bloco->conteudo_tipo_componente;
            
            $coluna = $contadorBtn % 3;
            $linha = floor($contadorBtn / 3);
            $defaultX = 38 + ($coluna * 19);
            $defaultY = 20 + ($linha * 30);
            
            $posX = number_format((isset($dados['pos_x']) && floatval($dados['pos_x']) > 0) ? floatval($dados['pos_x']) : $defaultX, 2, '.', '');
            $posY = number_format((isset($dados['pos_y']) && floatval($dados['pos_y']) > 0) ? floatval($dados['pos_y']) : $defaultY, 2, '.', '');
            
            if($tipoElemento === 'botao_navegacao') {
                $contadorBtn++;
                $iconeUrl = !empty($dados['icone']) ? $dados['icone'] : 'totem/img/botoes/BTN_Historia.png';
                $tituloBtn = isset($dados['titulo']) ? $dados['titulo'] : 'BOTÃO';
                $destinoId = isset($dados['pagina_destino_id']) ? $dados['pagina_destino_id'] : 1;
                $btnLargura = (isset($dados['largura']) && floatval($dados['largura']) > 0) ? floatval($dados['largura']) : 175;
                $btnAltura = (isset($dados['altura']) && floatval($dados['altura']) > 0) ? floatval($dados['altura']) . 'px' : '150px';
                $tamanhoIcone = isset($dados['tamanho_icone']) ? $dados['tamanho_icone'] : '65';
                $formatoIcone = isset($dados['formato_icone']) ? $dados['formato_icone'] : '50%';
        ?>
            <div class="card-botao-totem elemento-livre" data-id="{{ $bloco->id }}" data-tipo="botao_navegacao"
                data-destino="{{ $destinoId }}" data-url-destino="{{ route('gestao-conteudo.edit', $destinoId) }}"
                data-x="{{ $posX }}" data-y="{{ $posY }}" data-w="{{ $btnLargura }}" data-h="{{ (float) $btnAltura }}"
                style="left: {{ $posX }}%; top: {{ $posY }}%; width: {{ $btnLargura }}px; height: {{ $btnAltura }};">
                
                <button type="button" class="btn-delete-element" title="Excluir Botão"><i class="fas fa-trash"></i></button>
                <button type="button" class="btn-config-btn" title="Configurar Ícone"><i class="fas fa-cog"></i></button>
                <button type="button" class="btn-trocar-img" title="Trocar Ícone"><i class="fas fa-camera"></i></button>
                <div class="resize-handle"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
                <div class="nav-icon"><div class="icon-circle" style="width: {{ $tamanhoIcone }}px; height: {{ $tamanhoIcone }}px; border-radius: {{ $formatoIcone }};"><img src="{{ asset($iconeUrl) }}" data-raw-src="{{ $iconeUrl }}" class="img-alvo" draggable="false" alt="Ícone"></div></div>
                <div class="texto-editavel titulo-botao">{!! $tituloBtn !!}</div>
            </div>
        <?php 
            } 
            elseif($tipoElemento === 'texto') {
                $textoLivre = isset($dados['texto']) ? $dados['texto'] : 'Texto Livre';
                $txtLargura = (isset($dados['largura']) && floatval($dados['largura']) > 0) ? floatval($dados['largura']) : 350;
                $txtAltura = (isset($dados['altura']) && floatval($dados['altura']) > 0) ? floatval($dados['altura']) . 'px' : '100px';
                
                $corFundoTexto = isset($dados['cor_fundo']) ? $dados['cor_fundo'] : 'rgba(255, 255, 255, 0.85)';
                $sombraTexto = ($corFundoTexto === 'transparent' || $corFundoTexto === 'rgba(0, 0, 0, 0)') ? 'none' : '0 4px 10px rgba(0,0,0,0.15)';
        ?>
            <div class="elemento-livre bloco-texto-livre" data-id="{{ $bloco->id }}" data-tipo="texto"
                data-x="{{ $posX }}" data-y="{{ $posY }}" data-w="{{ $txtLargura }}" data-h="{{ (float) $txtAltura }}"
                style="left: {{ $posX }}%; top: {{ $posY }}%; width: {{ $txtLargura }}px; height: {{ $txtAltura }}; background-color: {{ $corFundoTexto }}; box-shadow: {{ $sombraTexto }};">
                
                <button type="button" class="btn-delete-element" title="Excluir Texto"><i class="fas fa-trash"></i></button>
                <button type="button" class="btn-bg-texto" title="Alterar Cor de Fundo"><i class="fas fa-fill-drip"></i></button>
                <div class="resize-handle"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
                <div class="texto-editavel">{!! $textoLivre !!}</div>
            </div>
        <?php } ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($paginaAtual): ?>
<div class="modal fade" id="modalEdicao" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content text-dark">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Novo Botão</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
            <form action="{{ route('conteudos.store') }}" method="POST">
                @csrf <input type="hidden" name="pagina_id" value="{{ $paginaAtual->id }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group"><label>Título</label><input type="text" name="titulo" class="form-control" required></div>
                        <div class="col-md-6 form-group">
                            <label>Destino</label>
                            <select name="pagina_destino_id" id="selectPaginaDestino" class="form-control" required>
                                <option value="" disabled selected>Selecione...</option>
                                <option value="nova" class="font-weight-bold text-success">➕ [ Criar Nova Página ]</option>
                                <optgroup label="Existentes"><?php foreach ($listaPaginas as $pag): ?><option value="{{ $pag->id }}">{{ $pag->pagina_titulo }}</option><?php endforeach; ?></optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="row" id="divNovaPagina" style="display: none;"><div class="col-md-12 form-group"><label class="text-success"><i class="fas fa-file-alt"></i> Nome Nova Página</label><input type="text" name="nova_pagina_titulo" id="inputNovaPagina" class="form-control border-success"></div></div>
                    <div class="form-group"><label>Ícone Inicial</label><input type="text" name="icone" class="form-control" value="totem/img/botoes/BTN_Historia.png"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Adicionar</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalEdicaoTexto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content text-dark"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Formatação</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body p-0"><textarea id="summernoteTexto"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnSalvarTextoFormato"><i class="fas fa-save"></i> Aplicar</button></div></div></div>
</div>

<script>
    window.TotemEditor = { routes: { upload: '{{ route("conteudos.upload-imagem") }}', salvar: '{{ route("conteudos.salvar-em-massa") }}', publicar: '{{ route("conteudos.publicar") }}' }, csrfToken: '{{ csrf_token() }}' };
</script>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
@stop