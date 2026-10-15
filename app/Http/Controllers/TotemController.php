<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class TotemController extends Controller
{
    /**
     * Lista os totens cadastrados.
     */
    public function index()
    {
        $totens = Totem::orderBy('id')->get();
        $paginas = Pagina::where('pagina_ativo', true)
            ->orderBy('pagina_titulo')->get(['id', 'pagina_titulo']);

        $resumo = [
            'todos' => $totens->count(),

            'online' => $totens->filter(
                fn($totem) =>
                $totem->status_monitoramento === 'online'
            )->count(),

            'offline' => $totens->filter(
                fn($totem) =>
                $totem->status_monitoramento === 'offline'
            )->count(),
        ];

        return view('totens', compact(
            'totens',
            'resumo',
            'paginas'
        ));
    }

    /**
     * Retorna um retrato leve do monitoramento para atualizacao em segundo plano.
     */
    public function status(): JsonResponse
    {
        $totens = Totem::orderBy('id')->get();

        $dados = $totens->map(function (Totem $totem) {
            $status = $totem->status_monitoramento;
            $ping = $totem->totem_ping;

            return [
                'id' => $totem->id,
                'nome' => $totem->totem_nome_identificacao,
                'localizacao' => $totem->totem_localizacao
                    ?: 'Localização não informada',
                'status' => $status,
                'ultimo_contato' => [
                    'relativo' => $ping?->diffForHumans() ?? 'Nunca',
                    'data' => $ping?->format('d/m/Y H:i:s') ?? 'Nunca',
                    'iso' => $ping?->toIso8601String(),
                ],
                'urls' => [
                    'buscar' => route('admin.totens.show', $totem),
                    'editar' => route('admin.totens.update', $totem),
                    'excluir' => route('admin.totens.destroy', $totem),
                    'acesso' => route('admin.totens.acesso', $totem),
                ],
            ];
        });

        return response()->json([
            'gerado_em' => now()->toIso8601String(),
            'resumo' => [
                'todos' => $dados->count(),
                'online' => $dados->where('status', 'online')->count(),
                'offline' => $dados->where('status', 'offline')->count(),
            ],
            'totens' => $dados->values(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Cadastra um totem.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag(
            'formTotem',
            $this->regrasValidacao()
        );

        Totem::create($validated);

        return redirect()
            ->route('admin.totens.index')
            ->with(
                'success',
                'Totem cadastrado com sucesso!'
            );
    }

    /**
     * Busca os dados de um totem no banco.
     *
     * O JavaScript utiliza este método
     * para preencher o modal de edição.
     */
    public function show(Totem $totem)
    {
        return response()->json([
            'id' => $totem->id,

            'totem_nome_identificacao' =>
            $totem->totem_nome_identificacao,

            'totem_localizacao' =>
            $totem->totem_localizacao,
            'pagina_inicial_id' => $totem->pagina_inicial_id,
            'totem_orientacao' => $totem->totem_orientacao,
            'totem_inatividade_segundos' => $totem->totem_inatividade_segundos,
        ]);
    }

    public function acesso(Totem $totem): JsonResponse
    {
        $pagina = $totem->paginaInicialDisponivel();

        if (! $pagina) {
            return response()->json([
                'message' => 'Ative uma página antes de preparar o tablet.',
            ], 422);
        }

        $expiraEm = now()->addMinutes(30);

        return response()->json([
            'url' => URL::temporarySignedRoute('totem.iniciar', $expiraEm, [
                'totem' => $totem,
                'pagina' => $pagina,
            ]),
            'expira_em' => $expiraEm->toIso8601String(),
            'pagina' => $pagina->pagina_titulo,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Atualiza um totem existente.
     */
    public function update(Request $request, Totem $totem)
    {
        $validated = $request->validateWithBag(
            'formTotem',
            $this->regrasValidacao()
        );

        $totem->update($validated);

        return redirect()
            ->route('admin.totens.index')
            ->with(
                'success',
                'Totem atualizado com sucesso!'
            );
    }

    /**
     * Exclui um totem.
     */
    public function destroy(Totem $totem)
    {
        $totem->delete();

        return redirect()
            ->route('admin.totens.index')
            ->with(
                'success',
                'Totem excluído com sucesso!'
            );
    }

    private function regrasValidacao(): array
    {
        return [
            'totem_nome_identificacao' => ['required', 'string', 'max:100'],
            'totem_localizacao' => ['nullable', 'string', 'max:100'],
            'pagina_inicial_id' => [
                'nullable', 'integer',
                Rule::exists('paginas', 'id')->where('pagina_ativo', true),
            ],
            'totem_orientacao' => ['sometimes', 'required', Rule::in(['landscape', 'portrait', 'auto'])],
            'totem_inatividade_segundos' => [
                'sometimes', 'required', 'integer', Rule::in([0, 30, 60, 120, 180, 300, 600]),
            ],
        ];
    }
}
