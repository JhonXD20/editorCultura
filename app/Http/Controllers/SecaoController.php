<?php

namespace App\Http\Controllers;

use App\Models\Secao;
use App\Support\Audit;
use App\Support\SectionPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SecaoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:100', 'unique:secoes,nome']]);
        DB::transaction(function () use ($data): void {
            $section = Secao::create($data);
            Audit::record('section.created', $section);
        });

        return back()->with('success', 'Seção criada. Adicione as páginas para montar a sequência.');
    }

    public function update(Request $request, Secao $secao): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100', Rule::unique('secoes', 'nome')->ignore($secao)],
            'versao' => ['required', 'integer', 'min:1'],
        ]);
        DB::transaction(function () use ($data, $secao): void {
            SectionPages::lock();
            $secao->refresh();
            abort_unless($secao->versao === (int) $data['versao'], 409, 'A seção foi alterada. Atualize a página antes de salvar.');
            $secao->fill(['nome' => $data['nome']]);
            $secao->versao++;
            $secao->save();
            Audit::record('section.updated', $secao);
        });

        return back()->with('success', 'Seção atualizada.');
    }

    public function reorder(Request $request, Secao $secao): RedirectResponse
    {
        $data = $request->validate([
            'versao' => ['required', 'integer', 'min:1'],
            'paginas' => ['required', 'array', 'min:1'],
            'paginas.*' => ['required', 'integer', 'distinct'],
        ]);
        DB::transaction(function () use ($secao, $data): void {
            SectionPages::lock();
            $secao->refresh();
            abort_unless($secao->versao === (int) $data['versao'], 409, 'A seção foi alterada. Atualize a página antes de salvar.');
            $pages = $secao->paginas()->lockForUpdate()->get();
            $ids = array_map('intval', $data['paginas']);
            $expected = $pages->modelKeys();
            sort($expected);
            $actual = $ids;
            sort($actual);
            abort_unless($actual === $expected, 422, 'Envie todas as páginas da seção, sem incluir páginas de outras seções.');
            foreach ($ids as $index => $id) {
                // Reordering does not modify draft or publication timestamps/content.
                $pages->find($id)->timestamps = false;
                $pages->find($id)->update(['ordem_slide' => $index + 1]);
            }
            $secao->versao++;
            $secao->save();
            Audit::record('section.reordered', $secao, ['paginas' => $ids]);
        });

        return back()->with('success', 'Ordem salva. A sequência será atualizada nos totens.');
    }
}
