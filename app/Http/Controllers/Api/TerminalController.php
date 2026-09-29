<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Models\Totem;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    // Rota que o totem acessa ao ligar para baixar todo o acervo histórico
    public function syncAcervo()
    {
        $paginas = Pagina::with('conteudos')
            ->where('totem_ativo', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $paginas
        ]);
    }

    // Rota que o totem chama de 5 em 5 minutos para avisar que está funcionando
    public function ping(Request $request, $id)
    {
        $totem = Totem::find($id);

        if ($totem) {
            $totem->update(['totem_ping' => now()]);
            return response()->json(['status' => 'online']);
        }

        return response()->json(['status' => 'not_found'], 404);
    }
}
