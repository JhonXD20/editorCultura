@extends('layouts.template')

@section('title', 'Espelho do Totem - Início')

@section('content')
{{-- Carrega o estilo original --}}
<link rel="stylesheet" href="{{ asset('totem/css/style.css') }}">

{{-- Carrega os novos arquivos do Editor via Vite --}}
@vite(['resources/css/editor.css', 'resources/js/editor.js'])

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Preparação das Variáveis
$blocoLogo = null;
$botoesNavegacao = [];
$blocoFundo = null;
$listaPaginas = isset($todasPaginas) ? $todasPaginas : [];

if (isset($paginaPrincipal) && $paginaPrincipal->conteudos) {
    $blocoLogo = $paginaPrincipal->conteudos->where('conteudo_tipo_componente', 'logo')->first();
    $blocoFundo = $paginaPrincipal->conteudos->where('conteudo_tipo_componente', 'fundo')->first();
    $botoesNavegacao = $paginaPrincipal->conteudos->where('conteudo_tipo_componente', '!=', 'logo')->where('conteudo_tipo_componente', '!=', 'fundo')->all();
}

// Resgata os dados da Logo
$dadosLogo = [];
if ($blocoLogo) {
    $rawLogo = $blocoLogo->conteudo_dados_conteudo;
    $dadosLogo = is_string($rawLogo) ? (json_decode($rawLogo, true) ?: []) : (is_array($rawLogo) ? $rawLogo : []);
}

$logoX = (isset($dadosLogo['pos_x']) && floatval($dadosLogo['pos_x']) > 0) ? number_format(floatval($dadosLogo['pos_x']), 2, '.', '') : '8.00';
$logoY = (isset($dadosLogo['pos_y']) && floatval($dadosLogo['pos_y']) > 0) ? number_format(floatval($dadosLogo['pos_y']), 2, '.', '') : '18.00';
$logoUrl = !empty($dadosLogo['icone']) ? $dadosLogo['icone'] : 'totem/img/logo-vertical-longa.png';
$logoLargura = (isset($dadosLogo['largura']) && floatval($dadosLogo['largura']) > 0) ? floatval($dadosLogo['largura']) : 240;
$logoAltura = (isset($dadosLogo['altura']) && floatval($dadosLogo['altura']) > 0) ? floatval($dadosLogo['altura']) . 'px' : 'auto';

// Resgata os dados do Fundo da Tela (AGORA SUPORTA GRADIENTE)
$dadosFundo = [];
if ($blocoFundo) {
    $rawFundo = $blocoFundo->conteudo_dados_conteudo;
    $dadosFundo = is_string($rawFundo) ? (json_decode($rawFundo, true) ?: []) : (is_array($rawFundo) ? $rawFundo : []);
}

$fundoTipo = $dadosFundo['tipo_fundo'] ?? 'css'; // Pode ser 'imagem' ou 'css'
$fundoValor = $dadosFundo['valor_fundo'] ?? 'linear-gradient(135deg, #ff7a00 0%, #ffc107 40%, #8bc34a 70%, #03a9f4 100%)';

$styleFundo = '';
if ($fundoTipo === 'imagem' && $fundoValor) {
    $styleFundo = "background: url(" . asset($fundoValor) . ") center/cover no-repeat !important;";
} else {
    $styleFundo = "background: {$fundoValor} !important;";
}

$contadorBtn = 0;
?>

<div class="card mb-0">
    <div class="card-body p-0 totem-preview-wrapper" id="canvasTotem" data-pagina-id="{{ $paginaPrincipal->id ?? 1 }}"
         data-fundo-id="{{ $blocoFundo->id ?? 'novo_fundo' }}" 
         data-fundo-tipo="{{ $fundoTipo }}" 
         data-fundo-valor="{{ $fundoValor }}"
         style="{{ $styleFundo }}">

        <input type="file" id="inputUploadImagem" accept="image/*" style="display: none;">
        <input type="file" id="inputUploadFundo" accept="image/*" style="display: none;">

        {{-- Undo / Redo --}}
        <div class="floating-controls-left">
            <button type="button" class="btn-floating" id="btnUndo" title="Desfazer (Ctrl+Z)" disabled><i class="fas fa-undo"></i></button>
            <button type="button" class="btn-floating" id="btnRedo" title="Refazer (Ctrl+Y)" disabled><i class="fas fa-redo"></i></button>
        </div>

        {{-- Controles Direitos --}}
        <div class="floating-controls">
            {{-- Botão de fundo alterado para um ícone de Paleta de Cores --}}
            <button type="button" class="btn-floating btn-change-bg" id="btnTrocarFundo" title="Alterar Fundo (Cor ou Imagem)"><i class="fas fa-palette"></i></button>
            <button type="button" class="btn-floating btn-publish" id="btnPublicarTotem" title="Enviar alterações para os Totens"><i class="fas fa-paper-plane"></i></button>
            <button type="button" class="btn-floating btn-add-block" id="btnAddBotao" data-toggle="modal" data-target="#modalEdicao" title="Adicionar Novo Botão"><i class="fas fa-plus"></i></button>
            <button type="button" class="btn-floating btn-save-mode" id="btnSalvarCanvas" title="Salvar Posições e Tamanhos"><i class="fas fa-check"></i></button>
            <button type="button" class="btn-floating" id="btnToggleEdicao" title="Editar Tela"><i class="fas fa-pencil-alt"></i></button>
        </div>

        {{-- 1. LOGO DO TOTEM --}}
        <div class="elemento-livre logo-livre" data-id="{{ $blocoLogo->id ?? 'novo_logo' }}" data-tipo="logo"
            data-x="{{ $logoX }}" data-y="{{ $logoY }}" data-w="{{ $logoLargura }}"
            data-h="{{ $logoAltura !== 'auto' ? floatval($dadosLogo['altura']) : '' }}"
            style="left: {{ $logoX }}%; top: {{ $logoY }}%; width: {{ $logoLargura }}px; height: {{ $logoAltura }};">

            <button type="button" class="btn-trocar-img" title="Trocar Imagem da Logo"><i class="fas fa-camera"></i></button>
            <div class="resize-handle" title="Arraste para redimensionar L/A"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>
            <img src="{{ asset($logoUrl) }}" data-raw-src="{{ $logoUrl }}" class="img-alvo" draggable="false" alt="Logo Memorial">
        </div>

        {{-- 2. BOTÕES DE NAVEGAÇÃO --}}
        <?php foreach ($botoesNavegacao as $bloco): ?>
        <?php
            $raw = $bloco->conteudo_dados_conteudo;
            $dados = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);

            $coluna = $contadorBtn % 3;
            $linha = floor($contadorBtn / 3);
            $defaultX = 38 + ($coluna * 19);
            $defaultY = 20 + ($linha * 30);
            $contadorBtn++;

            $posX = number_format((isset($dados['pos_x']) && floatval($dados['pos_x']) > 0) ? floatval($dados['pos_x']) : $defaultX, 2, '.', '');
            $posY = number_format((isset($dados['pos_y']) && floatval($dados['pos_y']) > 0) ? floatval($dados['pos_y']) : $defaultY, 2, '.', '');

            $iconeUrl = !empty($dados['icone']) ? $dados['icone'] : 'totem/img/botoes/BTN_Historia.png';
            $tituloBtn = isset($dados['titulo']) ? $dados['titulo'] : 'HISTÓRIA';
            $destinoId = isset($dados['pagina_destino_id']) ? $dados['pagina_destino_id'] : 1;

            $btnLargura = (isset($dados['largura']) && floatval($dados['largura']) > 0) ? floatval($dados['largura']) : 175;
            $btnAltura = (isset($dados['altura']) && floatval($dados['altura']) > 0) ? floatval($dados['altura']) . 'px' : '150px';
        ?>

        <div class="card-botao-totem elemento-livre" data-id="{{ $bloco->id }}" data-tipo="botao_navegacao"
            data-destino="{{ $destinoId }}" data-url-destino="{{ route('gestao-conteudo.edit', $destinoId) }}"
            data-x="{{ $posX }}" data-y="{{ $posY }}" data-w="{{ $btnLargura }}" data-h="{{ (float) $btnAltura }}"
            style="left: {{ $posX }}%; top: {{ $posY }}%; width: {{ $btnLargura }}px; height: {{ $btnAltura }};">

            <button type="button" class="btn-trocar-img" title="Trocar Ícone"><i class="fas fa-camera"></i></button>
            <div class="resize-handle" title="Arraste para redimensionar L/A"><i class="fas fa-expand-arrows-alt" style="transform: rotate(-45deg);"></i></div>

            <div class="nav-icon">
                <div class="icon-circle"><img src="{{ asset($iconeUrl) }}" data-raw-src="{{ $iconeUrl }}" class="img-alvo" draggable="false" alt="Ícone"></div>
            </div>
            <h3 class="texto-editavel">{!! $tituloBtn !!}</h3>
        </div>
        <?php endforeach; ?>

    </div>
</div>

{{-- Modal para Adicionar Novo Botão --}}
<?php if (isset($paginaPrincipal)): ?>
<div class="modal fade" id="modalEdicao" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content text-dark">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Adicionar Novo Item na Tela</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('conteudos.store') }}" method="POST">
                @csrf
                <input type="hidden" name="pagina_id" value="{{ $paginaPrincipal->id }}">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Título do Botão</label>
                            <input type="text" name="titulo" class="form-control" placeholder="Ex: HERÓIS DO TOCANTINS" required>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Página de Destino</label>
                            <select name="pagina_destino_id" id="selectPaginaDestino" class="form-control" required>
                                <option value="" disabled selected>Selecione...</option>
                                <option value="nova" class="font-weight-bold text-success">➕ [ Criar Nova Página ]</option>
                                <optgroup label="Páginas Existentes">
                                    <?php foreach ($listaPaginas as $pag): ?>
                                        <option value="{{ $pag->id }}">{{ $pag->pagina_titulo }}</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="divNovaPagina" style="display: none;">
                        <div class="col-md-12 form-group">
                            <label class="text-success"><i class="fas fa-file-alt"></i> Nome da Nova Página</label>
                            <input type="text" name="nova_pagina_titulo" id="inputNovaPagina" class="form-control border-success" placeholder="Ex: Galeria de Fotos dos Prefeitos">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Caminho do Ícone Inicial</label>
                        <input type="text" name="icone" class="form-control" value="totem/img/botoes/BTN_Historia.png">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Adicionar à Tela</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

{{-- Exportando Rotas do Laravel --}}
<script>
    window.TotemEditor = {
        routes: {
            upload: '{{ route("conteudos.upload-imagem") }}',
            salvar: '{{ route("conteudos.salvar-em-massa") }}',
            publicar: '{{ route("conteudos.publicar") }}'
        },
        csrfToken: '{{ csrf_token() }}'
    };
</script>
@stop