<?php

namespace App\Http\Controllers;

use App\Models\Totem;
use Illuminate\Http\Request;

class TotemController extends Controller
{
    /**
     * Exibe a página de monitoramento.
     */
    public function index()
    {
        // Busca todos os equipamentos cadastrados
        $totens = Totem::orderBy('id', 'asc')->get();

        // Total de dispositivos
        $totalTotens = $totens->count();

        // Totens online
        $totalOnline = $totens->filter(function ($totem) {
            return $totem->status_monitoramento === 'online';
        })->count();

        // Totens offline
        $totalOffline = $totens->filter(function ($totem) {
            return $totem->status_monitoramento === 'offline';
        })->count();

        // Informações para os filtros
        $resumo = [
            'todos' => $totalTotens,
            'online' => $totalOnline,
            'offline' => $totalOffline,
        ];

        return view('totens', compact(
            'totens',
            'resumo'
        ));
    }

    /**
     * Cadastra um novo totem.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'totem_nome_identificacao' => [
                'required',
                'string',
                'max:100'
            ],

            'totem_localizacao' => [
                'nullable',
                'string',
                'max:100'
            ],
        ]);

        Totem::create($validated);

        return redirect()
            ->route('admin.totens.index')
            ->with(
                'success',
                'Totem registrado com sucesso!'
            );
    }
}
