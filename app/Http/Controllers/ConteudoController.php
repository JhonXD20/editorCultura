<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pagina;
use App\Models\Conteudo;

class ConteudoController extends Controller
{
    // Cria tanto botões na Home quanto Textos e Imagens nas Subpáginas
    public function store(Request $request)
    {
        $totalItens = DB::table('conteudos')->where('pagina_id', $request->pagina_id)->count();
        $tipo = $request->input('tipo_componente', 'botao_navegacao');

        $destinoId = $request->pagina_destino_id;

        // SE O USUÁRIO ESCOLHEU "CRIAR NOVA PÁGINA"
        if ($destinoId === 'nova') {
            // Cria a página no banco de dados e pega o ID dela gerado na hora
            $destinoId = DB::table('paginas')->insertGetId([
                'pagina_titulo' => $request->input('nova_pagina_titulo'),
                'pagina_tipo_layout' => 'interna', // Define que é uma página de conteúdo padrão
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Prepara os dados de acordo com o tipo
        if ($tipo === 'texto') {
            $dados = [
                'texto' => $request->input('texto', 'Novo bloco de texto. Clique no lápis para editar.'),
                'pos_x' => 10,
                'pos_y' => 25,
                'largura' => 80
            ];
        } elseif ($tipo === 'imagem') {
            $dados = [
                'url' => $request->input('icone', 'totem/img/logo-vertical-longa.png'),
                'pos_x' => 30,
                'pos_y' => 35,
                'largura' => 300,
                'altura' => 300
            ];
        } else {
            $dados = [
                'titulo' => $request->titulo,
                'icone' => $request->icone ?: 'totem/img/botoes/BTN_Historia.png',
                'pagina_destino_id' => $destinoId, // Usa o ID existente ou o ID que acabou de ser criado!
                'pos_x' => 45,
                'pos_y' => 30,
                'largura' => 175,
                'altura' => 150
            ];
        }

        // Cria o botão ou conteúdo na tela atual
        DB::table('conteudos')->insert([
            'pagina_id' => $request->pagina_id,
            'conteudo_tipo_componente' => $tipo,
            'conteudo_ordem_exibicao' => $totalItens + 1,
            'conteudo_dados_conteudo' => json_encode($dados),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Elemento adicionado com sucesso!');
    }

    public function uploadImagem(Request $request)
    {
        if ($request->hasFile('imagem')) {
            $arquivo = $request->file('imagem');
            $nomeArquivo = time() . '_' . preg_replace('/\s+/', '_', $arquivo->getClientOriginalName());

            $pastaDestino = public_path('totem/img/uploads');
            if (!file_exists($pastaDestino)) {
                mkdir($pastaDestino, 0755, true);
            }

            $arquivo->move($pastaDestino, $nomeArquivo);
            $caminhoRelativo = 'totem/img/uploads/' . $nomeArquivo;

            return response()->json([
                'success' => true,
                'caminho_relativo' => $caminhoRelativo,
                'url_completa' => asset($caminhoRelativo)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Nenhum arquivo enviado.'], 400);
    }

    // Salva coordenadas X/Y de qualquer página (Home ou Subpáginas)
    public function salvarEmMassa(Request $request)
    {
        try {
            $paginaId = $request->input('pagina_id');

            if ($request->filled('pagina_titulo')) {
                DB::table('paginas')
                    ->where('id', $paginaId)
                    ->update([
                        'pagina_titulo' => $request->input('pagina_titulo'),
                        'updated_at' => now()
                    ]);
            }

            $blocos = $request->input('blocos', []);

            foreach ($blocos as $blocoData) {
                $tipo = $blocoData['tipo'] ?? '';
                $novosDadosJson = json_encode($blocoData['dados_conteudo']);

                // Elementos fixos de layout (Logo na Home ou Título na Subpágina)
                if (in_array($blocoData['id'], ['novo_logo', 'novo_titulo', 'novo_fundo']) || in_array($tipo, ['logo', 'titulo_pagina', 'fundo'])) {
                    $registroFixo = DB::table('conteudos')
                        ->where('pagina_id', $paginaId)
                        ->where('conteudo_tipo_componente', $tipo)
                        ->first();

                    if ($registroFixo) {
                        DB::table('conteudos')
                            ->where('id', $registroFixo->id)
                            ->update([
                                'conteudo_dados_conteudo' => $novosDadosJson,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('conteudos')->insert([
                            'pagina_id' => $paginaId,
                            'conteudo_tipo_componente' => $tipo,
                            'conteudo_ordem_exibicao' => 0,
                            'conteudo_dados_conteudo' => $novosDadosJson,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    continue;
                }

                // Atualiza blocos normais (Botões, Textos, Imagens)
                DB::table('conteudos')
                    ->where('id', $blocoData['id'])
                    ->update([
                        'conteudo_ordem_exibicao' => $blocoData['ordem'] ?? 1,
                        'conteudo_dados_conteudo' => $novosDadosJson,
                        'updated_at' => now()
                    ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Envia o sinal para os totens atualizarem suas telas
    public function publicar(Request $request)
    {
        try {
            // Atualiza a coluna 'updated_at' de todos os totens cadastrados.
            // O software do totem pode ler essa data a cada X segundos para saber se deve dar "F5" na tela.
            DB::table('totens')->update(['updated_at' => now()]);

            // DICA: Se você estiver usando WebSockets (como Pusher ou Laravel Reverb), 
            // você também pode disparar um evento em tempo real aqui:
            // event(new \App\Events\AtualizarTelaTotem());

            return response()->json([
                'success' => true,
                'message' => 'Sincronização enviada! As telas dos totens serão atualizadas em instantes.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar com os totens: ' . $e->getMessage()
            ], 500);
        }
    }
}
