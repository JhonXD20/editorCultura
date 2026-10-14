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

        if ($totemId) {
            Totem::whereKey($totemId)->update([
                'totem_ping' => now(),
            ]);
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

        $paginaInicialId = Pagina::query()
            ->where('pagina_ativo', true)
            ->where('pagina_tipo_layout', 'home')
            ->value('id');

        return view('totem-publico', compact(
            'pagina',
            'proximaPaginaId',
            'paginaAnteriorId',
            'paginaInicialId'
        ));
    }
}
