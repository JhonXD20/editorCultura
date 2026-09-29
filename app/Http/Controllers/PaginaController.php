<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use Illuminate\Http\Request;

class PaginaController extends Controller
{
    public function index()
    {
        $paginaPrincipal = Pagina::with('conteudos')->where('pagina_tipo_layout', 'home')->first();
        // Busca todas as outras páginas para aparecerem no `<select>` do modal
        $todasPaginas = Pagina::where('pagina_tipo_layout', '!=', 'home')->get();

        return view('gestao-conteudo', compact('paginaPrincipal', 'todasPaginas'));
    }

    public function create()
    {
        return view('gestao-conteudo.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pagina_titulo' => 'required|max:100',
            'pagina_tipo_layout' => 'required|max:45'
        ]);

        Pagina::create($validated);

        // Redireciona usando o nome exato gerado pelo seu Route::resource
        return redirect()->route('gestao-conteudo.index')
            ->with('success', 'Página criada com sucesso!');
    }

    // O parâmetro reflete o que você definiu em ->parameters()
    public function edit($id)
    {
        // Busca a subpágina específica e o miolo dela
        $pagina = Pagina::with('conteudos')->findOrFail($id);

        return view('gestao-conteudo-editor', compact('pagina'));
    }

    public function update(Request $request, $id)
    {
        $pagina = Pagina::findOrFail($id);

        $validated = $request->validate([
            'pagina_titulo' => 'required|max:100',
            'pagina_tipo_layout' => 'required|max:45',
            'pagina_ativo' => 'boolean'
        ]);

        $pagina->update($validated);

        return redirect()->route('gestao-conteudo.index')
            ->with('success', 'Página atualizada!');
    }

    public function destroy($id)
    {
        $pagina = Pagina::findOrFail($id);
        $pagina->delete();

        return redirect()->route('gestao-conteudo.index')
            ->with('success', 'Página excluída.');
    }
}
