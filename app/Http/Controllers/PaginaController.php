<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaginaController extends Controller
{
    public function index(): View
    {
        $paginas = $this->paginasOrdenadas()->get();

        $resumo = [
            'todas' => $paginas->count(),
            'publicadas' => $paginas->whereNotNull('published_at')->count(),
            'rascunhos' => $paginas->whereNull('published_at')->count(),
            'inativas' => $paginas->where('pagina_ativo', false)->count(),
        ];

        return view('gestao-conteudo-index', compact('paginas', 'resumo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pagina_titulo' => ['required', 'string', 'max:100'],
            'pagina_tipo_layout' => ['required', 'in:home,interna,padrao'],
        ]);

        $pagina = Pagina::create($validated);

        return redirect()->route('gestao-conteudo.edit', $pagina)
            ->with('success', 'Página criada com sucesso!');
    }

    public function edit($id): View
    {
        $pagina = Pagina::with('conteudos')->findOrFail($id);

        return $this->renderEditor($pagina);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $pagina = Pagina::findOrFail($id);

        $validated = $request->validate([
            'pagina_titulo' => ['required', 'string', 'max:100'],
            'pagina_tipo_layout' => ['required', 'in:home,interna,padrao'],
            'pagina_ativo' => ['boolean'],
        ]);

        $pagina->update($validated);

        return redirect()->route('gestao-conteudo.index')
            ->with('success', 'Página atualizada!');
    }

    public function destroy($id): RedirectResponse
    {
        $pagina = Pagina::findOrFail($id);

        $deleted = DB::transaction(function () use ($pagina): bool {
            $total = Pagina::query()
                ->lockForUpdate()
                ->get(['id'])
                ->count();

            if ($total <= 1) {
                return false;
            }

            $pagina->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()->route('gestao-conteudo.index')
                ->with('error', 'A última página não pode ser excluída. Crie outra página primeiro.');
        }

        return redirect()->route('gestao-conteudo.index')
            ->with('success', 'Página excluída com sucesso.');
    }

    private function renderEditor(Pagina $pagina): View
    {
        $paginas = $this->paginasOrdenadas()->get();

        return view('gestao-conteudo-grapes', compact('pagina', 'paginas'));
    }

    private function paginasOrdenadas(): Builder
    {
        return Pagina::query()
            ->orderByRaw("CASE WHEN pagina_tipo_layout = 'home' THEN 0 ELSE 1 END")
            ->orderBy('grupo_slide')
            ->orderBy('ordem_slide')
            ->orderBy('pagina_titulo');
    }
}
