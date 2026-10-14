<?php

namespace App\Http\Controllers;

use App\Models\Conteudo;
use App\Models\Pagina;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConteudoController extends Controller
{
    private const COMPONENT_TYPES = [
        'botao_navegacao',
        'botao',
        'texto',
        'titulo_pagina',
        'imagem',
        'logo',
        'fundo',
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pagina_id' => ['required', 'integer', 'exists:paginas,id'],
            'tipo_componente' => ['nullable', Rule::in(self::COMPONENT_TYPES)],
            'titulo' => ['nullable', 'string', 'max:500'],
            'texto' => ['nullable', 'string', 'max:50000'],
            'icone' => ['nullable', 'string', 'max:2048'],
            'pagina_destino_id' => [
                'nullable',
                function (string $attribute, mixed $value, $fail): void {
                    if ($value === 'nova') {
                        return;
                    }

                    if (! ctype_digit((string) $value) || ! Pagina::whereKey($value)->exists()) {
                        $fail('A pagina de destino selecionada e invalida.');
                    }
                },
            ],
            'nova_pagina_titulo' => [
                'nullable',
                'required_if:pagina_destino_id,nova',
                'string',
                'max:100',
            ],
            'grupo_slide' => ['nullable', 'string', 'max:100'],
        ]);

        $tipo = $validated['tipo_componente'] ?? 'botao_navegacao';

        if (in_array($tipo, ['botao', 'botao_navegacao'], true)) {
            validator($validated, [
                'titulo' => ['required', 'string', 'max:500'],
                'pagina_destino_id' => ['required'],
            ])->validate();
        }

        DB::transaction(function () use ($validated, $tipo): void {
            $pagina = Pagina::query()
                ->lockForUpdate()
                ->findOrFail($validated['pagina_id']);

            $destinoId = $validated['pagina_destino_id'] ?? null;

            if ($destinoId === 'nova') {
                $grupo = $validated['grupo_slide'] ?? null;
                $ordem = 0;

                if ($grupo) {
                    $ordem = (int) Pagina::query()
                        ->where('grupo_slide', $grupo)
                        ->lockForUpdate()
                        ->max('ordem_slide') + 1;
                }

                $destinoId = Pagina::create([
                    'pagina_titulo' => $validated['nova_pagina_titulo'],
                    'pagina_tipo_layout' => 'interna',
                    'grupo_slide' => $grupo,
                    'ordem_slide' => $ordem,
                ])->id;
            }

            $dados = match ($tipo) {
                'texto', 'titulo_pagina' => [
                    'texto' => HtmlSanitizer::clean(
                        $validated['texto'] ?? 'Novo bloco de texto. Clique no lapis para editar.'
                    ),
                    'pos_x' => 10,
                    'pos_y' => 25,
                    'largura' => 350,
                    'altura' => 100,
                ],
                'imagem', 'logo' => [
                    'icone' => $validated['icone'] ?? 'totem/img/logo-vertical-longa.png',
                    'pos_x' => 30,
                    'pos_y' => 35,
                    'largura' => 300,
                    'altura' => 300,
                ],
                default => [
                    'titulo' => HtmlSanitizer::clean($validated['titulo'] ?? ''),
                    'icone' => $validated['icone'] ?? 'totem/img/botoes/BTN_Historia.png',
                    'pagina_destino_id' => (int) $destinoId,
                    'pos_x' => 45,
                    'pos_y' => 30,
                    'largura' => 175,
                    'altura' => 150,
                ],
            };

            $ordem = (int) $pagina->conteudos()
                ->lockForUpdate()
                ->max('conteudo_ordem_exibicao') + 1;

            $pagina->conteudos()->create([
                'conteudo_tipo_componente' => $tipo,
                'conteudo_ordem_exibicao' => $ordem,
                'conteudo_dados_conteudo' => $dados,
            ]);
        });

        return redirect()->back()->with('success', 'Elemento adicionado com sucesso!');
    }

    public function uploadImagem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'imagem' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:10240',
            ],
        ]);

        $arquivo = $validated['imagem'];
        $nomeArquivo = Str::uuid().'.'.strtolower($arquivo->extension());
        $pastaDestino = public_path('totem/img/uploads');

        if (! is_dir($pastaDestino) && ! mkdir($pastaDestino, 0755, true) && ! is_dir($pastaDestino)) {
            Log::error('Nao foi possivel criar a pasta de uploads do editor.');

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel armazenar a imagem.',
            ], 500);
        }

        $arquivo->move($pastaDestino, $nomeArquivo);
        $caminhoRelativo = 'totem/img/uploads/'.$nomeArquivo;

        return response()->json([
            'success' => true,
            'caminho_relativo' => $caminhoRelativo,
            'url_completa' => asset($caminhoRelativo),
        ]);
    }

    public function salvarEmMassa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pagina_id' => ['required', 'integer', 'exists:paginas,id'],
            'blocos' => ['present', 'array', 'max:500'],
            'blocos.*.id' => ['required'],
            'blocos.*.tipo' => ['required', Rule::in(self::COMPONENT_TYPES)],
            'blocos.*.ordem' => ['required', 'integer', 'min:0', 'max:10000'],
            'blocos.*.dados_conteudo' => ['required', 'array'],
            'blocos.*.dados_conteudo.texto' => ['nullable', 'string', 'max:50000'],
            'blocos.*.dados_conteudo.titulo' => ['nullable', 'string', 'max:50000'],
            'blocos.*.dados_conteudo.icone' => ['nullable', 'string', 'max:2048'],
            'blocos.*.dados_conteudo.valor_fundo' => ['nullable', 'string', 'max:4096'],
            'blocos.*.dados_conteudo.tipo_fundo' => ['nullable', Rule::in(['css', 'imagem'])],
            'blocos.*.dados_conteudo.pagina_destino_id' => ['nullable', 'integer'],
            'blocos.*.dados_conteudo.pos_x' => ['nullable', 'numeric', 'between:-1000,1000'],
            'blocos.*.dados_conteudo.pos_y' => ['nullable', 'numeric', 'between:-1000,1000'],
            'blocos.*.dados_conteudo.largura' => ['nullable', 'numeric', 'between:1,10000'],
            'blocos.*.dados_conteudo.altura' => ['nullable', 'numeric', 'between:0,10000'],
            'blocos.*.dados_conteudo.largura_canvas' => ['nullable', 'numeric', 'between:320,10000'],
            'blocos.*.dados_conteudo.altura_canvas' => ['nullable', 'numeric', 'between:240,10000'],
            'blocos.*.dados_conteudo.tamanho_icone' => ['nullable'],
            'blocos.*.dados_conteudo.formato_icone' => ['nullable', 'string', 'max:30'],
            'blocos.*.dados_conteudo.cor_fundo' => ['nullable', 'string', 'max:100'],
        ]);

        $destinos = collect($validated['blocos'])
            ->pluck('dados_conteudo.pagina_destino_id')
            ->filter()
            ->unique()
            ->values();

        if ($destinos->isNotEmpty() && Pagina::whereKey($destinos)->count() !== $destinos->count()) {
            throw ValidationException::withMessages([
                'blocos' => 'Um dos botoes aponta para uma pagina inexistente.',
            ]);
        }

        try {
            DB::transaction(function () use ($validated): void {
                $paginaId = (int) $validated['pagina_id'];
                Pagina::query()->lockForUpdate()->findOrFail($paginaId);

                $idsParaManter = [];
                $idsRecebidos = [];

                foreach ($validated['blocos'] as $bloco) {
                    $id = $bloco['id'];
                    $tipo = $bloco['tipo'];
                    $dados = $this->normalizarDados($tipo, $bloco['dados_conteudo']);
                    $ordem = (int) $bloco['ordem'];

                    if (in_array((string) $id, ['novo_logo', 'novo_titulo', 'novo_fundo'], true)
                        || in_array($tipo, ['logo', 'titulo_pagina', 'fundo'], true)) {
                        $conteudo = Conteudo::query()
                            ->where('pagina_id', $paginaId)
                            ->where('conteudo_tipo_componente', $tipo)
                            ->lockForUpdate()
                            ->first();

                        if ($conteudo) {
                            $conteudo->update([
                                'conteudo_dados_conteudo' => $dados,
                                'conteudo_ordem_exibicao' => $ordem,
                            ]);
                        } else {
                            $conteudo = Conteudo::create([
                                'pagina_id' => $paginaId,
                                'conteudo_tipo_componente' => $tipo,
                                'conteudo_dados_conteudo' => $dados,
                                'conteudo_ordem_exibicao' => $ordem,
                            ]);
                        }

                        $idsParaManter[] = $conteudo->id;

                        continue;
                    }

                    if (is_string($id) && str_starts_with($id, 'novo_texto_')) {
                        $conteudo = Conteudo::create([
                            'pagina_id' => $paginaId,
                            'conteudo_tipo_componente' => $tipo,
                            'conteudo_dados_conteudo' => $dados,
                            'conteudo_ordem_exibicao' => $ordem,
                        ]);

                        $idsParaManter[] = $conteudo->id;

                        continue;
                    }

                    if (! ctype_digit((string) $id) || in_array((int) $id, $idsRecebidos, true)) {
                        throw ValidationException::withMessages([
                            'blocos' => 'A lista de componentes contem um identificador invalido ou duplicado.',
                        ]);
                    }

                    $idsRecebidos[] = (int) $id;

                    $atualizados = Conteudo::query()
                        ->whereKey((int) $id)
                        ->where('pagina_id', $paginaId)
                        ->update([
                            'conteudo_tipo_componente' => $tipo,
                            'conteudo_dados_conteudo' => $dados,
                            'conteudo_ordem_exibicao' => $ordem,
                        ]);

                    if ($atualizados !== 1) {
                        throw ValidationException::withMessages([
                            'blocos' => 'Um componente nao pertence a pagina editada.',
                        ]);
                    }

                    $idsParaManter[] = (int) $id;
                }

                Conteudo::query()
                    ->where('pagina_id', $paginaId)
                    ->when(
                        $idsParaManter !== [],
                        fn ($query) => $query->whereNotIn('id', $idsParaManter)
                    )
                    ->delete();
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Falha ao salvar o canvas do totem.', [
                'pagina_id' => $validated['pagina_id'],
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel salvar os conteudos. Tente novamente.',
            ], 500);
        }

        return response()->json(['success' => true]);
    }

    public function publicar(Request $request): JsonResponse
    {
        $request->validate([
            'pagina_id' => ['required', 'integer', 'exists:paginas,id'],
        ]);

        DB::table('totens')->update(['updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Sincronizacao enviada! As telas dos totens serao atualizadas em instantes.',
        ]);
    }

    private function normalizarDados(string $tipo, array $dados): array
    {
        $dados = Arr::only($dados, [
            'texto', 'titulo', 'icone', 'pagina_destino_id', 'pos_x',
            'pos_y', 'largura', 'altura', 'formato_icone', 'tamanho_icone',
            'cor_fundo', 'tipo_fundo', 'valor_fundo', 'largura_canvas',
            'altura_canvas',
        ]);

        if (array_key_exists('texto', $dados)) {
            $dados['texto'] = HtmlSanitizer::clean((string) $dados['texto']);
        }

        if (array_key_exists('titulo', $dados)) {
            $dados['titulo'] = HtmlSanitizer::clean((string) $dados['titulo']);
        }

        foreach (['pos_x', 'pos_y', 'largura', 'altura', 'largura_canvas', 'altura_canvas'] as $key) {
            if (array_key_exists($key, $dados)) {
                $dados[$key] = (float) $dados[$key];
            }
        }

        if (isset($dados['pagina_destino_id'])) {
            $dados['pagina_destino_id'] = (int) $dados['pagina_destino_id'];
        }

        if (isset($dados['cor_fundo']) && ! $this->corSegura($dados['cor_fundo'])) {
            $dados['cor_fundo'] = 'transparent';
        }

        if (isset($dados['formato_icone'])
            && ! preg_match('/^\d+(?:\.\d+)?(?:px|%)$/', $dados['formato_icone'])) {
            $dados['formato_icone'] = '50%';
        }

        if ($tipo === 'fundo' && ($dados['tipo_fundo'] ?? null) === 'css') {
            $valor = (string) ($dados['valor_fundo'] ?? '');

            if (preg_match('/(?:url\s*\(|expression|javascript:|@import|behavior)/i', $valor)) {
                $dados['valor_fundo'] = '#ffffff';
            }
        }

        return $dados;
    }

    private function corSegura(string $color): bool
    {
        return preg_match(
            '/^(?:transparent|#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|hsla?\([0-9.,%\s]+\))$/i',
            trim($color)
        ) === 1;
    }
}
