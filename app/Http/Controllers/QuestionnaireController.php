<?php

namespace App\Http\Controllers;

use App\Models\Totem;
use App\Support\EventTime;
use App\Support\VisitorQuestionnaire;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionnaireController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var Totem $totem */
        $totem = $request->attributes->get('totem');
        abort_unless($totem->questionario_ativo, 403, 'A avaliação está desativada neste equipamento.');
        $data = $request->validate([
            'uuid' => ['required', 'uuid'],
            'version' => ['required', Rule::in([VisitorQuestionnaire::VERSION])],
            'participate' => ['required', 'accepted'],
            'occurred_at' => ['required', 'string', 'max:40', 'date'],
            'answers' => ['required', 'array:q1,q2,q3,q4,q5', 'min:1'],
            'answers.*' => ['required', Rule::in(array_keys(VisitorQuestionnaire::OPTIONS))],
        ]);
        [$occurred, $trusted] = EventTime::resolve($data['occurred_at']);
        $answers = [];
        foreach ($data['answers'] as $key => $value) {
            $answers[$key] = $value === 'na' ? 0 : (int) $value;
        }
        $inserted = DB::table('respostas_questionario')->insertOrIgnore($answers + [
            'uuid' => $data['uuid'], 'totem_id' => $totem->id,
            'versao_instrumento' => $data['version'], 'ocorrido_em' => $occurred,
            'horario_validado' => $trusted, 'created_at' => now(),
        ]);
        abort_unless($inserted || DB::table('respostas_questionario')->where('uuid', $data['uuid'])->where('totem_id', $totem->id)->exists(), 409);

        return response()->json(['acknowledged' => $data['uuid']], $inserted ? 201 : 200);
    }

    public function index(Request $request): View
    {
        $this->authorize('administer');
        $data = $request->validate(['totem_id' => ['nullable', 'integer', 'exists:totens,id']]);
        $totemId = $data['totem_id'] ?? null;
        $query = DB::table('respostas_questionario')->where('versao_instrumento', VisitorQuestionnaire::VERSION)
            ->when($totemId !== null, fn ($q) => $q->where('totem_id', $totemId));
        $total = (clone $query)->count();
        $results = [];
        foreach (VisitorQuestionnaire::QUESTIONS as $key => $label) {
            $counts = (clone $query)->select($key)->selectRaw('COUNT(*) AS total')->groupBy($key)->get();
            $distribution = [];
            foreach ($counts as $count) {
                $distribution[$count->$key === null ? 'skip' : (string) $count->$key] = (int) $count->total;
            }
            $results[] = ['label' => $label, 'distribution' => $distribution];
        }

        return view('questionnaires.index', [
            'total' => $total, 'results' => $results, 'totemId' => $totemId,
            'totens' => Totem::orderBy('totem_nome_identificacao')->get(),
        ]);
    }
}
