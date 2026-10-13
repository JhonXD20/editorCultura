
@extends('layouts.template')

@section('title', 'Gestão de Conteúdos - Totem')

@section('css')
<link
    href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css"
    rel="stylesheet"
>
@stop

@section('content')

@vite([
    'resources/css/totem.css',
    'resources/css/editor.css',
    'resources/js/editor.js'
])

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@include('partials.editor-totem', [
    'pagina' => $paginaPrincipal ?? null,
    'todasPaginas' => $todasPaginas ?? []
])

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
@stop
