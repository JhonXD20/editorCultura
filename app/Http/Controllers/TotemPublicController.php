<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagina;

class TotemPublicController extends Controller
{
    public function exibir($id)
    {
        // Carrega a página atual com os seus respetivos conteúdos
        $pagina = Pagina::with(['conteudos' => function ($query) {
            $query->orderBy('conteudo_ordem_exibicao', 'asc');
        }])->findOrFail($id);

        $proximaPaginaId = null;
        $paginaAnteriorId = null;

        // Se a página pertence a um grupo de slides, calculamos quem é o próximo e o anterior
        if (!empty($pagina->grupo_slide)) {
            // Próximo slide (mesmo grupo, ordem maior mais próxima)
            $proxima = Pagina::where('grupo_slide', $pagina->grupo_slide)
                ->where('ordem_slide', '>', $pagina->ordem_slide)
                ->orderBy('ordem_slide', 'asc')
                ->first();

            if ($proxima) {
                $proximaPaginaId = $proxima->id;
            }

            // Slide anterior (mesmo grupo, ordem menor mais próxima)
            $anterior = Pagina::where('grupo_slide', $pagina->grupo_slide)
                ->where('ordem_slide', '<', $pagina->ordem_slide)
                ->orderBy('ordem_slide', 'desc')
                ->first();

            if ($anterior) {
                $paginaAnteriorId = $anterior->id;
            }
        }

        return view('totem-publico', compact('pagina', 'proximaPaginaId', 'paginaAnteriorId'));
    }
}
