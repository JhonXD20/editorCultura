@extends('layouts.template')
@section('page_title', 'Avaliações - TotemSync')
@section('header_title', 'Avaliações dos visitantes')
@section('header_subtitle', 'Respostas opcionais, apresentadas de forma agregada.')
@section('conteudo')
<div class="card"><div class="card-body">
    <form method="GET" action="{{ route('questionnaires.index') }}" class="form-inline mb-3">
        <label for="questionnaire-totem" class="mr-2">Equipamento</label>
        <select name="totem_id" id="questionnaire-totem" class="form-control mr-2"><option value="">Todos</option>
            @foreach($totens as $totem)<option value="{{ $totem->id }}" @selected((string)$totemId === (string)$totem->id)>{{ $totem->totem_nome_identificacao }}</option>@endforeach
        </select><button class="btn btn-primary" type="submit">Filtrar</button>
    </form>
    <p><strong>{{ $total }}</strong> avaliação(ões) recebida(s). Instrumento versão {{ \App\Support\VisitorQuestionnaire::VERSION }}.</p>
    <p class="text-muted">Escala: 1 discordo totalmente; 2 discordo; 3 nem concordo nem discordo; 4 concordo; 5 concordo totalmente. NA: não consigo avaliar. Sem resposta: questão pulada. Respostas mantidas por até 24 meses após o recebimento.</p>
    <div class="table-responsive"><table class="table table-striped"><caption>Avaliações por afirmação; não representam visitantes únicos.</caption>
        <thead><tr><th scope="col">Afirmação</th>@foreach([1,2,3,4,5,'NA','Sem resposta'] as $option)<th scope="col">{{ $option }}</th>@endforeach</tr></thead>
        <tbody>@foreach($results as $result)<tr><th scope="row">{{ $result['label'] }}</th>@foreach(['1','2','3','4','5','0','skip'] as $key)<td>{{ $result['distribution'][$key] ?? 0 }}</td>@endforeach</tr>@endforeach</tbody>
    </table></div>
    @if($total === 0)<p>Nenhuma avaliação recebida para este filtro. Ative a avaliação nas configurações do equipamento após definir o procedimento de aplicação.</p>@endif
</div></div>
@endsection
