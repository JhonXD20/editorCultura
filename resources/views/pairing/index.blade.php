@extends('layouts.template')
@section('page_title', 'Parear dispositivo - TotemSync')
@section('header_title', 'Preparar este dispositivo')
@section('header_subtitle', 'Selecione o totem que será exibido neste navegador.')
@section('conteudo')
<div class="card card-outline card-primary"><form method="POST" action="{{ route('pairing.store') }}">@csrf
    <div class="card-body"><label for="pairingTotem">Equipamento</label><select class="form-control" id="pairingTotem" name="totem_id" required>
        @foreach($totens as $totem)<option value="{{ $totem->id }}">{{ $totem->totem_nome_identificacao }}</option>@endforeach
    </select><p class="text-muted mt-3">Ao confirmar, sua sessão de usuário será encerrada e o player ficará associado a este equipamento.</p></div>
    <div class="card-footer"><button class="btn btn-primary" type="submit">Parear e abrir player</button></div>
</form></div>
@stop
