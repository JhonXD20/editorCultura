<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Totem;
use App\Support\PlayerManifest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TotemPublicController extends Controller
{
    public function exibir(Request $request, Pagina $pagina): View
    {
        abort_unless($pagina->pagina_ativo && $pagina->pagina_publicada_em, 404);

        return $this->render($pagina, $request->attributes->get('totem'));
    }

    public function preview(Pagina $pagina): View
    {
        $this->authorize('edit-content');

        return $this->render($pagina, null, true);
    }

    private function render(Pagina $pagina, ?Totem $totem, bool $preview = false): View
    {
        $pagina->load(['conteudos' => fn ($query) => $query->orderBy('conteudo_ordem_exibicao')]);

        if ($totem) {
            Totem::withoutTimestamps(fn () => $totem->update(['totem_ping' => now()]));
        }

        $sequence = $pagina->secao_id !== null || filled($pagina->grupo_slide);
        $siblings = $sequence
            ? Pagina::query()->when($pagina->secao_id !== null,
                fn ($query) => $query->where('secao_id', $pagina->secao_id),
                fn ($query) => $query->whereNull('secao_id')->where('grupo_slide', $pagina->grupo_slide))
                ->where('pagina_ativo', true)->where('pagina_tipo_layout', '!=', 'home')
                ->whereNotNull('pagina_publicada_em')->orderBy('ordem_slide')->orderBy('id')->get(['id', 'ordem_slide'])
            : collect();
        $position = $siblings->search(fn (Pagina $item) => $item->id === $pagina->id);
        $paginaAnteriorId = $position !== false && $position > 0 ? $siblings[$position - 1]->id : null;
        $proximaPaginaId = $position !== false && $position < $siblings->count() - 1 ? $siblings[$position + 1]->id : null;
        $navegacaoSecao = $pagina->secao_id !== null && $position !== false;
        if ($navegacaoSecao && $siblings->count() > 1 && $proximaPaginaId === null) {
            $proximaPaginaId = $siblings->first()->id;
        }
        $entradasSecoes = Pagina::whereNotNull('secao_id')->where('pagina_tipo_layout', '!=', 'home')
            ->where('pagina_ativo', true)->whereNotNull('pagina_publicada_em')
            ->orderBy('ordem_slide')->orderBy('id')->get(['id', 'secao_id'])
            ->groupBy('secao_id')->map(fn ($pages) => $pages->first()->id);
        $paginaInicialId = ($totem ? $totem->paginaInicialDisponivel() : Totem::paginaInicialPadrao())?->id;
        $orientacao = $totem ? $totem->totem_orientacao : 'auto';
        $inatividadeSegundos = $preview ? 0 : ($totem ? $totem->totem_inatividade_segundos : 0);
        $manifestVersion = $totem ? PlayerManifest::make($totem)['version'] : 'preview';

        return view('totem-publico', compact(
            'pagina', 'proximaPaginaId', 'paginaAnteriorId', 'paginaInicialId',
            'orientacao', 'inatividadeSegundos', 'preview', 'manifestVersion', 'navegacaoSecao', 'entradasSecoes'
        ));
    }
}
