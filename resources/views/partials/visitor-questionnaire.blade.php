<button type="button" class="totem-nav-button" id="openQuestionnaire">Avaliar experiência</button>
<dialog id="visitorQuestionnaire" aria-labelledby="questionnaireTitle" data-endpoint="{{ route('device.questionnaire') }}" data-version="{{ \App\Support\VisitorQuestionnaire::VERSION }}">
    <form id="questionnaireForm">
        <button type="button" id="closeQuestionnaire" class="questionnaire-close">Sair da avaliação</button>
        <h2 id="questionnaireTitle">Como foi sua experiência?</h2>
        <p>Participar é opcional. As respostas serão usadas para avaliar esta interface e apresentadas de forma agregada. Não solicitamos nome ou contato. Você pode pular questões ou sair a qualquer momento.</p>
        <p>1: discordo totalmente · 2: discordo · 3: nem concordo nem discordo · 4: concordo · 5: concordo totalmente · NA: não consigo avaliar.</p>
        @foreach(\App\Support\VisitorQuestionnaire::QUESTIONS as $key => $question)
            <fieldset><legend>{{ $question }}</legend><div class="questionnaire-options">
                @foreach(\App\Support\VisitorQuestionnaire::OPTIONS as $value => $label)
                    <label><input type="radio" name="{{ $key }}" value="{{ $value }}" aria-label="{{ $label }}"> {{ $value === 'na' ? 'NA' : $value }}</label>
                @endforeach
            </div></fieldset>
        @endforeach
        <label class="questionnaire-consent"><input type="checkbox" name="participate" required> Quero enviar voluntariamente minhas respostas.</label>
        <p id="questionnaireStatus" role="status" aria-live="polite"></p>
        <div class="questionnaire-actions"><button type="button" id="skipQuestionnaire">Pular e voltar</button><button type="submit">Enviar avaliação</button></div>
    </form>
</dialog>
