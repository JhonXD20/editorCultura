<?php

namespace App\Http\Controllers;

use App\Models\Totem;
use Illuminate\Http\Request;

class TotemController extends Controller
{
    /**
     * Lista os totens cadastrados.
     */
    public function index()
    {
        $totens = Totem::orderBy('id')->get();

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
            'resumo'
        ));
    }

    /**
     * Cadastra um totem.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag(
            'formTotem',
            [
                'totem_nome_identificacao' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'totem_localizacao' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
            ]
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
        ]);
    }

    /**
     * Atualiza um totem existente.
     */
    public function update(Request $request, Totem $totem)
    {
        $validated = $request->validateWithBag(
            'formTotem',
            [
                'totem_nome_identificacao' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'totem_localizacao' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
            ]
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
}
