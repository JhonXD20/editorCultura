<?php

namespace Database\Seeders;

use App\Models\Conteudo;
use App\Models\InteracaoTotem;
use App\Models\Pagina;
use App\Models\Totem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('O cenário demonstrativo é bloqueado em produção.');

            return;
        }

        User::updateOrCreate(
            ['email' => 'demo@editorcultura.test'],
            [
                'name' => 'Administrador Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $pages = collect([
            ['title' => 'Início da experiência', 'layout' => 'home', 'active' => true, 'published' => true],
            ['title' => 'Nossa história', 'layout' => 'interna', 'active' => true, 'published' => true],
            ['title' => 'Acervo em destaque', 'layout' => 'interna', 'active' => true, 'published' => true],
            ['title' => 'Agenda cultural', 'layout' => 'interna', 'active' => true, 'published' => false],
            ['title' => 'Exposição encerrada', 'layout' => 'interna', 'active' => false, 'published' => true],
        ])->map(function (array $definition, int $index): Pagina {
            $published = $definition['published'];
            $title = $definition['title'];

            return Pagina::updateOrCreate(
                ['pagina_titulo' => '[DEMO] '.$title],
                [
                    'pagina_tipo_layout' => $definition['layout'],
                    'pagina_ativo' => $definition['active'],
                    'grupo_slide' => 'Demonstração',
                    'ordem_slide' => $index + 1,
                    'editor_project_data' => $this->projectData($title),
                    'published_html' => $published ? $this->publishedHtml($title) : null,
                    'published_css' => $published ? $this->publishedCss() : null,
                    'editor_version' => 'grapesjs-0.23',
                    'published_at' => $published ? now()->subDays($index) : null,
                ],
            );
        });

        foreach ($pages as $index => $page) {
            Conteudo::updateOrCreate(
                [
                    'pagina_id' => $page->id,
                    'conteudo_tipo_componente' => 'texto',
                    'conteudo_ordem_exibicao' => 1,
                ],
                [
                    'conteudo_dados_conteudo' => [
                        'texto' => '<h2>'.$page->pagina_titulo.'</h2><p>Conteúdo fictício para validação visual do projeto.</p>',
                        'x' => 90,
                        'y' => 180,
                        'largura' => 720,
                        'altura' => 180,
                    ],
                ],
            );
        }

        $totemDefinitions = [
            ['Entrada principal', 'Recepção', 25],
            ['Galeria histórica', 'Piso térreo', 52],
            ['Acervo digital', 'Sala multimídia', 80],
            ['Auditório', 'Bloco cultural', 420],
            ['Reserva técnica', 'Área administrativa', 1800],
            ['Unidade itinerante', 'Em manutenção', null],
        ];

        $totems = collect($totemDefinitions)->map(function (array $definition) use ($pages): Totem {
            [$name, $location, $secondsAgo] = $definition;

            return Totem::updateOrCreate(
                ['totem_nome_identificacao' => '[DEMO] Totem '.$name],
                [
                    'totem_localizacao' => $location,
                    'pagina_inicial_id' => $pages->first()->id,
                    'totem_orientacao' => 'landscape',
                    'totem_inatividade_segundos' => 120,
                    'totem_ping' => $secondsAgo === null ? null : now()->subSeconds($secondsAgo),
                ],
            );
        });

        InteracaoTotem::whereIn('pagina_id', $pages->pluck('id'))->delete();

        $rows = [];
        $activePages = $pages->where('pagina_ativo', true)->values();

        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $touches = 18 + (($daysAgo * 7) % 19);

            for ($index = 0; $index < $touches; $index++) {
                $moment = $day->copy()->addHours(8 + ($index % 10))->addMinutes(($index * 7) % 60);

                if ($moment->isFuture()) {
                    $moment = now()->subMinute();
                }

                $rows[] = [
                    'totem_id' => $totems[$index % $totems->count()]->id,
                    'pagina_id' => $activePages[$index % $activePages->count()]->id,
                    'tipo' => 'toque',
                    'duracao_segundos' => null,
                    'created_at' => $moment,
                    'updated_at' => $moment,
                ];

                if ($index % 4 === 0) {
                    $rows[] = [
                        'totem_id' => $totems[$index % $totems->count()]->id,
                        'pagina_id' => $activePages[$index % $activePages->count()]->id,
                        'tipo' => 'tempo_pagina',
                        'duracao_segundos' => 35 + (($index + $daysAgo) % 95),
                        'created_at' => $moment,
                        'updated_at' => $moment,
                    ];
                }
            }
        }

        DB::table('interacoes_totem')->insert($rows);

        $this->command?->info('Cenário demonstrativo criado/atualizado.');
        $this->command?->line('Login: demo@editorcultura.test | Senha: password');
        $this->command?->line($pages->count().' páginas, '.$totems->count().' totens e '.count($rows).' interações.');
    }

    private function projectData(string $title): array
    {
        return [
            'pages' => [[
                'id' => 'pagina-principal',
                'component' => [
                    'tagName' => 'main',
                    'attributes' => ['data-cultura-responsive-version' => '2'],
                    'components' => [[
                        'tagName' => 'h1',
                        'content' => $title,
                    ]],
                ],
            ]],
            'styles' => [],
            'assets' => [],
        ];
    }

    private function publishedHtml(string $title): string
    {
        return '<main class="demo-page" data-cultura-responsive-version="2">'
            .'<span class="demo-kicker">TOTEMSYNC</span>'
            .'<h1>'.e($title).'</h1>'
            .'<p>Uma página fictícia preparada para testes e demonstrações.</p>'
            .'</main>';
    }

    private function publishedCss(): string
    {
        return '.demo-page{min-height:768px;padding:96px;background:#f4f0e8;color:#20252a}'
            .'.demo-kicker{color:#a36d29;font-weight:700;letter-spacing:.18em}'
            .'.demo-page h1{max-width:850px;margin:24px 0;font-size:64px}'
            .'.demo-page p{max-width:650px;font-size:22px;line-height:1.6}'
            .'@media (max-width: 992px){.demo-page{padding:56px}.demo-page h1{font-size:46px}}';
    }
}
