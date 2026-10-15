<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Http\Request;

class TotemPublicController extends Controller
{
    /**
     * Inicializa o navegador de um equipamento.
     *
     * Esta rota é protegida por URL assinada.
     */
    public function iniciar(
        Request $request,
        Totem $totem,
        Pagina $pagina
    ) {
        abort_unless($pagina->pagina_ativo, 404);

        $request->session()->put(
            'totem_ativo_id',
            $totem->id
        );

        return redirect()->route(
            'totem.exibir',
            $pagina->id
        );
    }

    /**
     * Exibe uma página do totem.
     */
    public function exibir(Request $request, $id)
    {
        $pagina = Pagina::with([
            'conteudos' => function ($query) {
                $query->orderBy(
                    'conteudo_ordem_exibicao',
                    'asc'
                );
            },
        ])
            ->where('pagina_ativo', true)
            ->findOrFail($id);

        $totemId = $request->session()->get('totem_ativo_id');

        $totem = $totemId ? Totem::find($totemId) : null;

        if ($totem) {
            Totem::whereKey($totem->id)->update([
                'totem_ping' => now(),
            ]);
        } elseif ($totemId) {
            $request->session()->forget('totem_ativo_id');
        }

        $proximaPaginaId = null;
        $paginaAnteriorId = null;

        if (! empty($pagina->grupo_slide)) {

            $proxima = Pagina::where(
                'grupo_slide',
                $pagina->grupo_slide
            )
                ->where('pagina_ativo', true)
                ->where(
                    'ordem_slide',
                    '>',
                    $pagina->ordem_slide
                )
                ->orderBy('ordem_slide', 'asc')
                ->first();

            if ($proxima) {
                $proximaPaginaId = $proxima->id;
            }

            $anterior = Pagina::where(
                'grupo_slide',
                $pagina->grupo_slide
            )
                ->where('pagina_ativo', true)
                ->where(
                    'ordem_slide',
                    '<',
                    $pagina->ordem_slide
                )
                ->orderBy('ordem_slide', 'desc')
                ->first();

            if ($anterior) {
                $paginaAnteriorId = $anterior->id;
            }
        }

        $paginaInicialId = ($totem
            ? $totem->paginaInicialDisponivel()
            : Totem::paginaInicialPadrao())?->id;
        $orientacao = $totem?->totem_orientacao ?? 'auto';
        $inatividadeSegundos = $totem?->totem_inatividade_segundos ?? 0;

        return view('totem-publico', compact(
            'pagina',
            'proximaPaginaId',
            'paginaAnteriorId',
            'paginaInicialId',
            'orientacao',
            'inatividadeSegundos'
        ));
    }
}
