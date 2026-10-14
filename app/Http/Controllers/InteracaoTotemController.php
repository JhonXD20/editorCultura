<?php

namespace App\Http\Controllers;

use App\Models\InteracaoTotem;
use App\Models\Totem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InteracaoTotemController extends Controller
{
    public function store(Request $request)
    {
        $dados = $request->validate([
            'pagina_id' => [
                'required',
                'integer',
                Rule::exists('paginas', 'id')
                    ->where('pagina_ativo', true),
            ],

            'tipo' => [
                'required',
                Rule::in(['toque', 'tempo_pagina']),
            ],

            'duracao_segundos' => [
                'nullable',
                'required_if:tipo,tempo_pagina',
                'integer',
                'min:1',
                'max:86400',
            ],
        ]);

        // Identificação obtida da sessão do equipamento,
        // nunca de um totem_id enviado pelo navegador.
        $totemId = $request->session()->get(
            'totem_ativo_id'
        );

        if (
            $totemId &&
            ! Totem::whereKey($totemId)->exists()
        ) {
            $request->session()->forget('totem_ativo_id');
            $totemId = null;
        }

        InteracaoTotem::create([
            'totem_id' => $totemId,
            'pagina_id' => $dados['pagina_id'],
            'tipo' => $dados['tipo'],

            'duracao_segundos' => $dados['tipo'] === 'tempo_pagina'
                ? $dados['duracao_segundos']
                : null,
        ]);

        return response()->noContent();
    }

    /**
     * Atualiza o ultimo contato do equipamento associado a sessao.
     */
    public function ping(Request $request)
    {
        $totemId = $request->session()->get('totem_ativo_id');

        if ($totemId) {
            Totem::whereKey($totemId)->update([
                'totem_ping' => now(),
            ]);
        }

        return response()->noContent();
    }
}
