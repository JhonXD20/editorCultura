<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Secao;
use App\Support\Audit;
use App\Support\MediaReferences;
use App\Support\SectionPages;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaginaController extends Controller
{
    public function index(): View
    {
        $this->authorize('edit-content');
        $paginas = $this->paginasOrdenadas()->get();
        $secoes = Secao::with('paginas')->orderBy('nome')->get();
        $resumo = [
            'todas' => $paginas->count(),
            'publicadas' => $paginas->whereNotNull('pagina_publicada_em')->count(),
            'rascunhos' => $paginas->whereNull('pagina_publicada_em')->count(),
            'inativas' => $paginas->where('pagina_ativo', false)->count(),
        ];

        return view('gestao-conteudo-index', compact('paginas', 'resumo', 'secoes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('edit-content');
        $validated = $request->validate([
            'pagina_titulo' => ['required', 'string', 'max:100'],
            'pagina_tipo_layout' => ['required', 'in:home,interna'],
            'secao_id' => ['nullable', 'integer', 'exists:secoes,id'],
        ]);
        $pagina = DB::transaction(function () use ($validated): Pagina {
            SectionPages::lock();
            Pagina::query()->lockForUpdate()->get(['id']);
            if (! Pagina::exists()) {
                $validated['pagina_tipo_layout'] = 'home';
            }
            if ($validated['pagina_tipo_layout'] === 'home') {
                $this->authorize('administer');
                abort_if(! empty($validated['secao_id']), 422, 'A página inicial deve ficar fora das seções.');
                Pagina::where('pagina_tipo_layout', 'home')->update(['pagina_tipo_layout' => 'interna']);
            }
            $sectionId = ! empty($validated['secao_id']) ? (int) $validated['secao_id'] : null;
            unset($validated['secao_id']);
            $page = new Pagina($validated);
            SectionPages::move($page, $sectionId);
            $page->save();
            Audit::record('page.created', $page);

            return $page;
        });

        return redirect()->route('gestao-conteudo.edit', $pagina)->with('success', 'Página criada com sucesso!');
    }

    public function edit(Pagina $gestao_conteudo): View
    {
        $this->authorize('edit-content');
        $gestao_conteudo->load('conteudos');

        return $this->renderEditor($gestao_conteudo);
    }

    public function update(Request $request, Pagina $gestao_conteudo): RedirectResponse
    {
        $this->authorize('update', $gestao_conteudo);
        $validated = $request->validate([
            'pagina_titulo' => ['required', 'string', 'max:100'],
            'pagina_tipo_layout' => ['required', 'in:home,interna'],
            'pagina_ativo' => ['required', 'boolean'],
            'secao_id' => ['sometimes', 'nullable', 'integer', 'exists:secoes,id'],
        ]);

        DB::transaction(function () use ($gestao_conteudo, $validated): void {
            SectionPages::lock();
            Pagina::query()->lockForUpdate()->get(['id']);
            $gestao_conteudo->refresh();
            if (array_key_exists('secao_id', $validated)) {
                $sectionId = $validated['secao_id'] ? (int) $validated['secao_id'] : null;
                if ($sectionId !== $gestao_conteudo->secao_id) {
                    $this->authorize('administer');
                }
                abort_if($validated['pagina_tipo_layout'] === 'home' && $sectionId !== null, 422, 'A página inicial deve ficar fora das seções.');
                SectionPages::move($gestao_conteudo, $sectionId);
                unset($validated['secao_id']);
            }
            if ($validated['pagina_tipo_layout'] !== $gestao_conteudo->pagina_tipo_layout || (bool) $validated['pagina_ativo'] !== $gestao_conteudo->pagina_ativo) {
                $this->authorize('administer');
            }
            if ($gestao_conteudo->pagina_tipo_layout === 'home' && $validated['pagina_tipo_layout'] !== 'home') {
                abort(422, 'Use a ação Definir como inicial na página substituta.');
            }
            if ($validated['pagina_tipo_layout'] === 'home' && ! $validated['pagina_ativo']) {
                abort(422, 'A página inicial precisa estar ativa.');
            }
            if ($validated['pagina_tipo_layout'] === 'home') {
                Pagina::where('pagina_tipo_layout', 'home')->where('id', '!=', $gestao_conteudo->id)
                    ->update(['pagina_tipo_layout' => 'interna']);
            }
            $gestao_conteudo->update($validated);
            Audit::record('page.updated', $gestao_conteudo);
        });

        return redirect()->route('gestao-conteudo.index')->with('success', 'Página atualizada!');
    }

    public function destroy(Pagina $gestao_conteudo): RedirectResponse
    {
        $this->authorize('delete', $gestao_conteudo);
        $deleted = DB::transaction(function () use ($gestao_conteudo): bool {
            SectionPages::lock();
            Pagina::query()->lockForUpdate()->get(['id']);
            $gestao_conteudo->refresh();
            if (! Pagina::where('id', '!=', $gestao_conteudo->id)->exists() || $gestao_conteudo->pagina_tipo_layout === 'home') {
                return false;
            }
            Audit::record('page.deleted', $gestao_conteudo, ['title' => $gestao_conteudo->pagina_titulo]);
            Secao::where('id', $gestao_conteudo->secao_id)->increment('versao');
            $gestao_conteudo->delete();

            return true;
        });

        return redirect()->route('gestao-conteudo.index')->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Página excluída com sucesso.' : 'A página inicial ou a última página não pode ser excluída.'
        );
    }

    public function duplicate(Pagina $pagina): RedirectResponse
    {
        $this->authorize('edit-content');
        $copy = DB::transaction(function () use ($pagina): Pagina {
            SectionPages::lock();
            $pagina->refresh();
            $copy = $pagina->replicate(['pagina_html_publicado', 'pagina_css_publicado', 'pagina_publicada_em', 'revisao_publicada_id', 'marcador_pagina_inicial']);
            $copy->pagina_titulo = mb_substr($pagina->pagina_titulo.' (cópia)', 0, 100);
            $copy->pagina_tipo_layout = 'interna';
            $copy->pagina_estado_editorial = 'draft';
            $copy->pagina_versao_publicacao = 0;
            $copy->secao_id = null;
            SectionPages::move($copy, $pagina->secao_id);
            $copy->save();
            MediaReferences::sync($copy);
            Audit::record('page.duplicated', $copy, ['source_page_id' => $pagina->id]);

            return $copy;
        });

        return redirect()->route('gestao-conteudo.edit', $copy)->with('success', 'Página duplicada como rascunho.');
    }

    public function setHome(Pagina $pagina): RedirectResponse
    {
        $this->authorize('administer');
        DB::transaction(function () use ($pagina): void {
            SectionPages::lock();
            Pagina::query()->lockForUpdate()->get(['id']);
            $pagina->refresh();
            abort_unless($pagina->pagina_ativo, 422, 'A página inicial precisa estar ativa.');
            Pagina::where('pagina_tipo_layout', 'home')->update(['pagina_tipo_layout' => 'interna']);
            SectionPages::move($pagina, null);
            $pagina->update(['pagina_tipo_layout' => 'home']);
            Audit::record('page.home_selected', $pagina);
        });

        return back()->with('success', 'Página inicial alterada.');
    }

    private function renderEditor(Pagina $pagina): View
    {
        $paginas = $this->paginasOrdenadas()->get();
        $secoes = Secao::orderBy('nome')->get();

        return view('gestao-conteudo-grapes', compact('pagina', 'paginas', 'secoes'));
    }

    private function paginasOrdenadas(): Builder
    {
        return Pagina::query()->with('secao')->orderByRaw("CASE WHEN pagina_tipo_layout = 'home' THEN 0 ELSE 1 END")
            ->orderBy('secao_id')->orderBy('ordem_slide')->orderBy('id');
    }
}
